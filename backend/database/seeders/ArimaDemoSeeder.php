<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads the four ARIMA demo datasets as real products with real sales, so
 * they show up on the Analytics page after "Run Analytics".
 *
 *   php artisan db:seed --class=ArimaDemoSeeder
 *
 * A dataset whose SKU already exists is left untouched unless you answer
 * "yes" to the replace prompt — replacing deletes that product's demo sales,
 * analytics results and stock movements, then inserts it again.
 *
 * How weekly sales become daily sales: each week's total is recorded as ONE
 * sale on the Wednesday of that week (00:00). Every other day of the week has
 * zero sales for the product.
 *
 * Dates are relative to the day the seeder runs: every dataset ends on the
 * most recent week of the analytics engine's 208-week window, i.e. the week
 * starting now()->subWeeks(208)->startOfWeek() + 207 weeks.
 */
class ArimaDemoSeeder extends Seeder
{
    /** Same window AnalyticsController uses ($weekWindow). */
    private const WINDOW_WEEKS = 208;

    private const COST_PRICE     = 600.00;
    private const SELLING_PRICE  = 1500.00;
    private const STOCK_QUANTITY = 150;
    private const REORDER_POINT  = 10;
    private const REORDER_QTY    = 30;

    public function run(): void
    {
        $cashierId = DB::table('users')->whereIn('role', ['admin', 'receptionist'])->orderBy('id')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if (!$cashierId) {
            $this->command?->error('ArimaDemoSeeder needs at least one user to act as cashier. Run the main seeder first.');
            return;
        }

        $categoryId = DB::table('product_categories')->where('type', 'frame')->orderBy('id')->value('id')
            ?? DB::table('product_categories')->insertGetId([
                'name' => 'Frames', 'type' => 'frame', 'created_at' => now(), 'updated_at' => now(),
            ]);

        $existing = DB::table('products')->whereIn('sku', array_column($this->datasets(), 'sku'))->pluck('id', 'sku');
        $replace  = $existing->isNotEmpty() && $this->command?->confirm(
            $existing->count() . ' demo dataset(s) already exist (' . $existing->keys()->implode(', ') . '). Delete and re-create them?',
            false
        );

        DB::transaction(function () use ($cashierId, $categoryId, $existing, $replace) {
            foreach ($this->datasets() as $dataset) {
                if ($existing->has($dataset['sku'])) {
                    if (!$replace) {
                        $this->command?->warn($dataset['sku'] . ' already exists — skipped.');
                        continue;
                    }
                    $this->removeDataset($existing[$dataset['sku']]);
                }

                $this->seedDataset($dataset, $cashierId, $categoryId);
            }
        });
    }

    /**
     * The raw data: weekly unit sales from oldest week to newest.
     * Datasets 1–3 cover the full 208-week window; Dataset 4 covers only the
     * most recent 50 weeks (a recently launched product).
     */
    private function datasets(): array
    {
        return [
            [
                'sku'    => 'ARIMA-DEMO-TREND',
                'name'   => 'ARIMA Demo — Linear Trend',
                'weekly' => [
                    10, 11, 11, 12, 12, 12, 12, 12, 12, 11, 11, 10, 10, 9, 9, 9, 9, 9, 9, 10, 10, 11, 12, 12, 13, 13,
                    13, 13, 13, 13, 12, 12, 11, 11, 10, 10, 10, 10, 10, 10, 11, 12, 12, 13, 13, 14, 14, 14, 14, 14, 14, 13,
                    13, 12, 12, 11, 11, 11, 11, 11, 11, 12, 13, 13, 14, 14, 15, 15, 15, 15, 15, 15, 14, 14, 13, 13, 12, 12,
                    12, 12, 12, 13, 13, 14, 14, 15, 16, 16, 16, 16, 16, 16, 16, 15, 15, 14, 14, 13, 13, 13, 13, 13, 14, 14,
                    15, 15, 16, 17, 17, 17, 17, 17, 17, 17, 16, 16, 15, 15, 14, 14, 14, 14, 14, 15, 15, 16, 17, 17, 18, 18,
                    18, 19, 18, 18, 18, 17, 17, 16, 16, 15, 15, 15, 15, 15, 16, 16, 17, 18, 18, 19, 19, 19, 20, 20, 19, 19,
                    18, 18, 17, 17, 16, 16, 16, 16, 16, 17, 17, 18, 19, 19, 20, 20, 21, 21, 21, 20, 20, 19, 19, 18, 18, 17,
                    17, 17, 17, 18, 18, 18, 19, 20, 20, 21, 21, 22, 22, 22, 21, 21, 20, 20, 19, 19, 19, 18, 18, 18, 19, 19,
                ],
            ],
            [
                'sku'    => 'ARIMA-DEMO-STABLE',
                'name'   => 'ARIMA Demo — Stationary Demand',
                'weekly' => [
                    22, 25, 25, 23, 18, 16, 16, 18, 21, 22, 20, 17, 16, 17, 21, 25, 26, 24, 20, 18, 18, 20, 23, 23, 20, 16,
                    14, 15, 19, 23, 24, 22, 19, 18, 19, 22, 25, 25, 22, 17, 15, 15, 18, 21, 22, 20, 17, 16, 18, 22, 25, 26,
                    23, 19, 17, 17, 20, 22, 22, 19, 16, 14, 16, 20, 24, 25, 23, 20, 18, 19, 22, 24, 24, 20, 16, 14, 15, 19,
                    22, 23, 21, 18, 17, 19, 23, 26, 25, 22, 18, 16, 17, 20, 22, 22, 19, 16, 15, 17, 21, 25, 25, 23, 19, 18,
                    19, 22, 24, 23, 19, 16, 14, 16, 19, 23, 23, 21, 18, 18, 20, 23, 26, 25, 21, 17, 15, 16, 19, 22, 22, 19,
                    16, 16, 18, 22, 25, 25, 22, 19, 17, 19, 21, 23, 22, 19, 15, 14, 16, 20, 23, 24, 21, 19, 18, 20, 23, 25,
                    24, 20, 16, 15, 16, 20, 22, 22, 19, 17, 16, 19, 23, 26, 25, 22, 18, 17, 18, 21, 23, 21, 18, 15, 14, 17,
                    21, 24, 24, 22, 19, 18, 20, 23, 25, 23, 19, 16, 14, 16, 20, 22, 22, 19, 17, 17, 20, 24, 26, 25, 21, 17,
                ],
            ],
            [
                'sku'    => 'ARIMA-DEMO-SHIFT',
                'name'   => 'ARIMA Demo — Level Shift',
                'weekly' => [
                    5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6,
                    6, 5, 5, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5,
                    4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4,
                    4, 5, 6, 6, 5, 5, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6,
                    6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6,
                    5, 4, 4, 5, 5, 6, 6, 5, 4, 4, 4, 5, 6, 6, 6, 5, 4, 4, 4, 5, 16, 16, 16, 15, 14, 14,
                    14, 15, 16, 16, 16, 15, 14, 14, 14, 15, 16, 16, 16, 15, 14, 14, 14, 15, 16, 16, 16, 15, 14, 14, 14, 15,
                    16, 16, 16, 15, 14, 14, 15, 15, 16, 16, 15, 14, 14, 14, 15, 16, 16, 16, 15, 14, 14, 14, 15, 16, 16, 16,
                ],
            ],
            [
                'sku'    => 'ARIMA-DEMO-RECENT',
                'name'   => 'ARIMA Demo — Recently Launched',
                'weekly' => [
                    8, 9, 9, 10, 10, 9, 9, 8, 8, 7, 8, 8, 9, 10, 10, 11, 11, 11, 10, 10, 9, 9, 9, 9, 10, 10,
                    11, 12, 12, 12, 12, 11, 11, 10, 10, 10, 10, 11, 12, 13, 13, 14, 13, 13, 12, 12, 11, 11, 11, 12,
                ],
            ],
        ];
    }

    private function seedDataset(array $dataset, int $cashierId, int $categoryId): void
    {
        $weekly = $dataset['weekly'];
        $weeks  = count($weekly);

        // A shorter dataset is right-aligned so it ends on the same week as the others.
        $offset      = self::WINDOW_WEEKS - $weeks;
        $windowStart = now()->subWeeks(self::WINDOW_WEEKS)->startOfWeek();

        $productId = DB::table('products')->insertGetId([
            'sku'              => $dataset['sku'],
            'name'             => $dataset['name'],
            'category_id'      => $categoryId,
            'cost_price'       => self::COST_PRICE,
            'selling_price'    => self::SELLING_PRICE,
            'stock_quantity'   => self::STOCK_QUANTITY,
            'reorder_point'    => self::REORDER_POINT,
            'reorder_quantity' => self::REORDER_QTY,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        foreach ($weekly as $i => $qty) {
            if ($qty <= 0) continue;

            $week   = $offset + $i;
            $date   = $windowStart->copy()->addWeeks($week)->addDays(2); // Wednesday
            $amount = $qty * self::SELLING_PRICE;

            $saleId = DB::table('sales')->insertGetId([
                'receipt_number' => sprintf('%s-W%03d', $dataset['sku'], $week),
                'cashier_id'     => $cashierId,
                'subtotal'       => $amount,
                'total_amount'   => $amount,
                'amount_paid'    => $amount,
                'payment_method' => 'cash',
                'status'         => 'completed',
                'created_at'     => $date,
                'updated_at'     => $date,
            ]);

            DB::table('sale_items')->insert([
                'sale_id'    => $saleId,
                'product_id' => $productId,
                'quantity'   => $qty,
                'unit_price' => self::SELLING_PRICE,
                'discount'   => 0,
                'subtotal'   => $amount,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        $this->command?->info(sprintf(
            '%s: %d weeks, %d units (%s to %s)',
            $dataset['sku'], $weeks, array_sum($weekly),
            $windowStart->copy()->addWeeks($offset)->toDateString(),
            $windowStart->copy()->addWeeks(self::WINDOW_WEEKS)->subDay()->toDateString()
        ));
    }

    /** Deletes one demo product together with its sales, analytics results and stock movements. */
    private function removeDataset(int $productId): void
    {
        $saleIds = DB::table('sale_items')->where('product_id', $productId)->pluck('sale_id');

        DB::table('sale_items')->where('product_id', $productId)->delete();
        // Only demo receipts — never a real sale that happened to include this product.
        DB::table('sales')->whereIn('id', $saleIds)->where('receipt_number', 'like', 'ARIMA-%')->delete();
        DB::table('analytics_logs')->where('product_id', $productId)->delete();
        DB::table('stock_movements')->where('product_id', $productId)->delete();
        DB::table('products')->where('id', $productId)->delete();
    }
}
