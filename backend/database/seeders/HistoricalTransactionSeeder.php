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
        DB::table('sale_items')->truncate();
        DB::table('sales')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $recep = User::where('role', 'receptionist')->first()
               ?? User::where('role', 'admin')->first();

        $patients = Patient::pluck('id')->toArray();
        if (empty($patients)) {
            $this->command->error('No patients found. Run DatabaseSeeder first.');
            return;
        }

        // ── Product pools (same as before) ────────────────────────────────────
        $corefast = Product::whereIn('sku', [
            'FR-001','FR-002','FR-003','FR-004','FR-005',
            'LN-001','LN-002','LN-003','LN-004',
        ])->get();

        $extFrames = Product::where('sku', 'like', 'EF-%')->inRandomOrder()->limit(30)->get();
        $extSV     = Product::where('sku', 'like', 'SV-%')->inRandomOrder()->limit(20)->get();
        $extProg   = Product::where('sku', 'like', 'PG-%')->inRandomOrder()->limit(15)->get();

        // Core products placed FIRST — they receive the highest weights
        $fastPool = $corefast->merge($extFrames)->merge($extSV)->merge($extProg)->values();

        $coreSlow = Product::whereIn('sku', ['CL-001','CL-002','SG-001','SG-002'])->get();
        $extCL    = Product::where('sku', 'like', 'CL-%')
                        ->whereNotIn('sku', ['CL-001','CL-002'])
                        ->inRandomOrder()->limit(15)->get();
        $extSG    = Product::where('sku', 'like', 'SG-%')
                        ->whereNotIn('sku', ['SG-001','SG-002'])
                        ->inRandomOrder()->limit(15)->get();

        $slowPool = $coreSlow->merge($extCL)->merge($extSG)->values();
        $accPool  = Product::whereIn('sku', ['AC-001','AC-002','AC-003'])->get()->values();

        if ($fastPool->isEmpty()) {
            $this->command->error('No products found. Run DatabaseSeeder first.');
            return;
        }

        // ── Pareto weighted selection tables ─────────────────────────────────
        // Fast pool: exponential decay 100 × 0.88^rank
        //   → rank 0 gets weight 100, rank 5 ≈ 53, rank 10 ≈ 28, rank 20 ≈ 8
        //   → top 9 core products capture ~55% of all fast-moving volume
        [$fastCum, $totalFast] = $this->buildWeights($fastPool, 0.88);

        // Slow pool: gentler decay 100 × 0.92^rank
        [$slowCum, $totalSlow] = $this->buildWeights($slowPool, 0.92);

        // ── Year-based volume multipliers (COVID impact + recovery) ───────────
        $yearVolume = [
            2021 => 0.45,
            2022 => 0.70,
            2023 => 1.00,
            2024 => 1.10,
            2025 => 1.18,
            2026 => 1.25,
        ];

        $payMethods = ['cash','cash','cash','cash','gcash','gcash','card','maya'];
        $saleCount  = 0;
        $itemCount  = 0;
        $receiptNo  = 0;

        $start     = Carbon::create(2021, 1, 1)->startOfDay();
        $end       = Carbon::now()->startOfDay();
        $totalDays = (int) $start->diffInDays($end);

        $this->command->info("Seeding {$totalDays} days (2021-01-01 → today) with Pareto demand…");

        for ($d = 0; $d <= $totalDays; $d++) {
            $date  = $start->copy()->addDays($d);
            $year  = (int) $date->year;
            $month = (int) $date->month;
            $dow   = (int) $date->dayOfWeek;

            if ($dow === 0) continue;

            $yearMult = $yearVolume[$year] ?? 1.0;

            $seasonal = match (true) {
                in_array($month, [12, 1])  => 1.5,
                in_array($month, [6, 7])   => 1.3,
                in_array($month, [3, 4])   => 1.15,
                in_array($month, [10, 11]) => 1.1,
                default                    => 1.0,
            };

            if ($dow === 6) $seasonal *= 1.25;

            $totalMult = $yearMult * $seasonal;

            // ── Fast-moving sales ─────────────────────────────────────────────
            $dailyFast = (int) round(mt_rand(4, 7) * $totalMult);

            for ($s = 0; $s < $dailyFast; $s++) {
                $saleDate  = $date->copy()->setHour(mt_rand(8, 18))->setMinute(mt_rand(0, 59));
                $patientId = $patients[array_rand($patients)];
                $product   = $this->weightedPick($fastPool, $fastCum, $totalFast);
                $qty       = mt_rand(1, 2);
                $subtotal  = $product->selling_price * $qty;

                $receiptNo++;
                $saleCount++;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad($receiptNo, 6, '0', STR_PAD_LEFT),
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

                // 35% chance of a second item in the same sale (frame + lens bundle)
                if (mt_rand(1, 100) <= 25) {
                    $product2 = $this->weightedPick($fastPool, $fastCum, $totalFast);
                    if ($product2->id !== $product->id) {
                        $sub2 = $product2->selling_price;
                        SaleItem::create([
                            'sale_id'    => $sale->id,
                            'product_id' => $product2->id,
                            'quantity'   => 1,
                            'unit_price' => $product2->selling_price,
                            'discount'   => 0,
                            'subtotal'   => $sub2,
                            'created_at' => $saleDate,
                            'updated_at' => $saleDate,
                        ]);
                        $itemCount++;
                    }
                }
            }

            // ── Slow-moving sales (contact lenses, sunglasses) ────────────────
            $slowThreshold = $year <= 2022 ? 5 : 3;
            if (!$slowPool->isEmpty() && $d % $slowThreshold === 0) {
                $saleDate  = $date->copy()->setHour(mt_rand(9, 17))->setMinute(mt_rand(0, 59));
                $patientId = $patients[array_rand($patients)];
                $product   = $this->weightedPick($slowPool, $slowCum, $totalSlow);
                $subtotal  = $product->selling_price;

                $receiptNo++;
                $saleCount++;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad($receiptNo, 6, '0', STR_PAD_LEFT),
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
                    'quantity'   => 1,
                    'unit_price' => $product->selling_price,
                    'discount'   => 0,
                    'subtotal'   => $subtotal,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
                $itemCount++;
            }

            // ── Accessory sales (once a week roughly) ─────────────────────────
            if (!$accPool->isEmpty() && $d % 7 === 0) {
                $saleDate  = $date->copy()->setHour(mt_rand(10, 16))->setMinute(mt_rand(0, 59));
                $patientId = $patients[array_rand($patients)];
                $product   = $accPool->random();
                $qty       = mt_rand(1, 3);
                $subtotal  = $product->selling_price * $qty;

                $receiptNo++;
                $saleCount++;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad($receiptNo, 6, '0', STR_PAD_LEFT),
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

        $this->command->info("✓ Inserted {$saleCount} sales with {$itemCount} line items (2021-01-01 → today).");
    }

    /**
     * Build a cumulative-weight array for weighted random selection.
     * Weight for rank i = max(1, round(100 × decay^i)).
     * Returns [cumulative_weights[], total_weight].
     */
    private function buildWeights(\Illuminate\Support\Collection $pool, float $decay): array
    {
        $cum   = [];
        $total = 0;
        foreach ($pool as $i => $_) {
            $total += max(1, (int) round(100 * pow($decay, $i)));
            $cum[]  = $total;
        }
        return [$cum, $total];
    }

    /**
     * Weighted random pick via binary search on the cumulative-weight array.
     * O(log n) per call.
     */
    private function weightedPick(\Illuminate\Support\Collection $pool, array $cum, int $total): object
    {
        $rand = mt_rand(1, $total);
        $lo   = 0;
        $hi   = count($cum) - 1;
        while ($lo < $hi) {
            $mid = (int)(($lo + $hi) / 2);
            if ($cum[$mid] < $rand) $lo = $mid + 1;
            else                    $hi = $mid;
        }
        return $pool[$lo];
    }
}