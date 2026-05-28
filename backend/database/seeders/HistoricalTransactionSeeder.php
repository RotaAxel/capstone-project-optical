<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalTransactionSeeder extends Seeder
{
    public function run(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $recep = User::where('role', 'receptionist')->first()
               ?? User::where('role', 'admin')->first();

        $patients = Patient::pluck('id')->toArray();

        if (empty($patients)) {
            $this->command->error('No patients found. Run DatabaseSeeder first.');
            return;
        }

        // ── Product pools ─────────────────────────────────────────────────────
        // Fast movers: core frames + lenses + extended catalog items
        $corefast = Product::whereIn('sku', [
            'FR-001','FR-002','FR-003','FR-004','FR-005',
            'LN-001','LN-002','LN-003','LN-004',
        ])->get();

        $extFrames = Product::where('sku', 'like', 'EF-%')
            ->inRandomOrder()->limit(30)->get();
        $extSV     = Product::where('sku', 'like', 'SV-%')
            ->inRandomOrder()->limit(20)->get();
        $extProg   = Product::where('sku', 'like', 'PG-%')
            ->inRandomOrder()->limit(15)->get();

        $fastPool = $corefast->merge($extFrames)->merge($extSV)->merge($extProg)->values();

        // Slow movers: contact lenses, sunglasses
        $coreSlow = Product::whereIn('sku', ['CL-001','CL-002','SG-001','SG-002'])->get();
        $extCL    = Product::where('sku', 'like', 'CL-%')
            ->whereNotIn('sku', ['CL-001','CL-002'])
            ->inRandomOrder()->limit(15)->get();
        $extSG    = Product::where('sku', 'like', 'SG-%')
            ->whereNotIn('sku', ['SG-001','SG-002'])
            ->inRandomOrder()->limit(15)->get();

        $slowPool = $coreSlow->merge($extCL)->merge($extSG)->values();

        // Accessories (non-moving — very rare sales)
        $accPool = Product::whereIn('sku', ['AC-001','AC-002','AC-003'])->get()->values();

        if ($fastPool->isEmpty()) {
            $this->command->error('No products found. Run DatabaseSeeder first.');
            return;
        }

        // ── Receipt number offset ─────────────────────────────────────────────
        $receiptNo = (int) DB::table('sales')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $payMethods = ['cash','cash','cash','cash','gcash','gcash','card','maya'];
        $saleCount  = 0;
        $itemCount  = 0;

        // ── 365-day loop ──────────────────────────────────────────────────────
        for ($day = 365; $day >= 1; $day--) {
            $date  = Carbon::now()->subDays($day)->startOfDay();
            $month = (int) $date->month;
            $dow   = (int) $date->dayOfWeek; // 0 = Sun, 6 = Sat

            // Clinic closed on Sundays
            if ($dow === 0) continue;

            // Seasonal demand multiplier
            $seasonal = match (true) {
                in_array($month, [12, 1])  => 1.5,  // Christmas / New Year rush
                in_array($month, [6, 7])   => 1.3,  // Back-to-school
                in_array($month, [3, 4])   => 1.15, // Summer / pre-summer
                in_array($month, [10, 11]) => 1.1,  // Pre-Christmas ramp
                default                    => 1.0,
            };

            // Saturday boost (more walk-ins)
            if ($dow === 6) $seasonal *= 1.25;

            // ── Fast-moving sales (main volume) ───────────────────────────────
            $dailyFast = (int) round(mt_rand(7, 10) * $seasonal);

            for ($s = 0; $s < $dailyFast; $s++) {
                $saleDate  = $date->copy()->setHour(mt_rand(8, 18))->setMinute(mt_rand(0, 59));
                $patientId = $patients[array_rand($patients)];
                $product   = $fastPool[mt_rand(0, $fastPool->count() - 1)];
                $qty       = mt_rand(1, 2);
                $subtotal  = $product->selling_price * $qty;

                $receiptNo++;
                $saleCount++;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad($receiptNo, 5, '0', STR_PAD_LEFT),
                    'patient_id'      => $patientId,
                    'cashier_id'      => $recep->id,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount'      => 0,
                    'total_amount'    => $subtotal,
                    'amount_paid'     => $subtotal,
                    'change_amount'   => 0,
                    'payment_method'  => $payMethods[array_rand($payMethods)],
                    'status'          => 'completed',
                    'created_at'      => $saleDate,
                    'updated_at'      => $saleDate,
                ]);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $product->selling_price,
                    'discount'   => 0,
                    'subtotal'   => $subtotal,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
                $itemCount++;

                // 35% chance of a second item in same sale (frame + lens bundle)
                if (mt_rand(1, 100) <= 35 && $fastPool->count() > 1) {
                    $product2 = $fastPool->where('id', '!=', $product->id)->random();
                    $qty2     = 1;
                    $sub2     = $product2->selling_price;

                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $product2->id,
                        'quantity'   => $qty2,
                        'unit_price' => $product2->selling_price,
                        'discount'   => 0,
                        'subtotal'   => $sub2,
                        'created_at' => $saleDate,
                        'updated_at' => $saleDate,
                    ]);
                    $itemCount++;
                }
            }

            // ── Slow-moving sales (every 2-3 days) ────────────────────────────
            if (!$slowPool->isEmpty() && $day % 3 === 0) {
                $saleDate  = $date->copy()->setHour(mt_rand(9, 17))->setMinute(mt_rand(0, 59));
                $patientId = $patients[array_rand($patients)];
                $product   = $slowPool->random();
                $qty       = 1;
                $subtotal  = $product->selling_price;

                $receiptNo++;
                $saleCount++;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad($receiptNo, 5, '0', STR_PAD_LEFT),
                    'patient_id'      => $patientId,
                    'cashier_id'      => $recep->id,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount'      => 0,
                    'total_amount'    => $subtotal,
                    'amount_paid'     => $subtotal,
                    'change_amount'   => 0,
                    'payment_method'  => 'cash',
                    'status'          => 'completed',
                    'created_at'      => $saleDate,
                    'updated_at'      => $saleDate,
                ]);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $product->selling_price,
                    'discount'   => 0,
                    'subtotal'   => $subtotal,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
                $itemCount++;
            }

            // ── Accessory sales (very rare — once a week roughly) ─────────────
            if (!$accPool->isEmpty() && $day % 7 === 0) {
                $saleDate  = $date->copy()->setHour(mt_rand(10, 16))->setMinute(mt_rand(0, 59));
                $patientId = $patients[array_rand($patients)];
                $product   = $accPool->random();
                $qty       = mt_rand(1, 3);
                $subtotal  = $product->selling_price * $qty;

                $receiptNo++;
                $saleCount++;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad($receiptNo, 5, '0', STR_PAD_LEFT),
                    'patient_id'      => $patientId,
                    'cashier_id'      => $recep->id,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount'      => 0,
                    'total_amount'    => $subtotal,
                    'amount_paid'     => $subtotal,
                    'change_amount'   => 0,
                    'payment_method'  => 'cash',
                    'status'          => 'completed',
                    'created_at'      => $saleDate,
                    'updated_at'      => $saleDate,
                ]);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $product->selling_price,
                    'discount'   => 0,
                    'subtotal'   => $subtotal,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
                $itemCount++;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("✓ Inserted {$saleCount} historical sales with {$itemCount} line items over 365 days.");
    }
}
