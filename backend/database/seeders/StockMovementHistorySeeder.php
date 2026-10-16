<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Rebuilds the whole stock ledger from the sales that exist, then sets every
 * product's stock_quantity to where its ledger ends. Run it AFTER all sales
 * are seeded (HistoricalTransactionSeeder calls it automatically).
 *
 * Re-runnable on its own:  php artisan db:seed --class=StockMovementHistorySeeder
 * Note: it clears ALL stock movements first, including ones made in the app.
 */
class StockMovementHistorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('stock_movements')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Load all products ───────────────────────────────────────────────
        $products = DB::table('products')
            ->whereNull('deleted_at')
            ->select('id', 'sku', 'cost_price', 'reorder_point', 'reorder_quantity')
            ->get()
            ->keyBy('id');

        // ── Load every sale line joined with its sale date & cashier ────────
        $saleLines = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->whereNull('s.deleted_at')
            ->whereNull('si.deleted_at')
            ->select(
                'si.product_id',
                'si.quantity',
                's.receipt_number',
                's.cashier_id',
                's.created_at as sale_date'
            )
            ->orderBy('si.product_id')
            ->orderBy('s.created_at')
            ->get()
            ->groupBy('product_id');

        // ── Users for stock-in / adjustments ───────────────────────────────
        $invUsers = DB::table('users')
            ->whereIn('role', ['admin', 'inventory_staff'])
            ->pluck('id')->toArray();

        if (empty($invUsers)) $invUsers = [1];

        $batch      = [];
        $batchSize  = 500;
        $total      = 0;
        $finalStock = [];   // product_id → stock after its last movement

        foreach ($products as $productId => $product) {
            $lines  = $saleLines->get($productId, collect());
            [$events, $finalStock[$productId]] = $this->buildHistory($product, $lines, $invUsers);

            foreach ($events as $ev) {
                $batch[] = $ev;
                if (count($batch) >= $batchSize) {
                    DB::table('stock_movements')->insert($batch);
                    $total += count($batch);
                    $batch  = [];
                }
            }
        }

        if (!empty($batch)) {
            DB::table('stock_movements')->insert($batch);
            $total += count($batch);
        }

        // The ledger is the source of truth: each product's stock is exactly
        // stock-ins − sales − damage/loss/adjustments, so the Stock Movements
        // page always balances.
        foreach ($finalStock as $productId => $qty) {
            DB::table('products')->where('id', $productId)->update(['stock_quantity' => $qty]);
        }

        $this->command->info("✓ Generated {$total} stock movements matched to actual transactions; product stock synced to the ledger.");
    }

    // ────────────────────────────────────────────────────────────────────────

    private function buildHistory(object $product, $lines, array $invUsers): array
    {
        $reorderPt  = max(5,  (int)($product->reorder_point    ?? 10));
        $reorderQty = max(15, (int)($product->reorder_quantity  ?? 20));
        $costPrice  = (float)($product->cost_price ?? 100);

        $events  = [];
        $balance = 0;
        $poSeq   = 1;

        // ── Determine earliest date for this product ────────────────────────
        $firstSaleDate = $lines->isNotEmpty()
            ? Carbon::parse($lines->first()->sale_date)
            : Carbon::create(2022, 6, 1);

        // Initial delivery: 2 weeks before the first sale (sales go back to 2021),
        // or mid-Jan 2022 for products that have never sold. It must come before
        // every other movement so stock never starts below zero.
        $initDate = $lines->isNotEmpty()
            ? (clone $firstSaleDate)->subDays(14)
            : Carbon::create(2022, 1, 15);

        // Seed enough stock to cover the first sales cycle (generous buffer)
        $initQty  = max($reorderQty * 3, 30);
        $balance += $initQty;

        $events[] = $this->stockIn(
            $product->id, $invUsers,
            $initQty, 0, $initQty,
            round($costPrice, 2),
            $this->poRef($product->sku, $initDate->year, $poSeq++),
            'Initial stock delivery',
            $initDate
        );

        // ── Add periodic organic restocks from 2022 up to first sale ────────
        // (shows purchasing activity before the period covered by sales)
        $organicDate = (clone $initDate)->addDays(rand(45, 75));
        while ($organicDate->lt($firstSaleDate) && $organicDate->lt(Carbon::create(2022, 7, 1))) {
            if ($balance <= $reorderPt * 2) {
                $qty     = $reorderQty * rand(2, 3);
                $before  = $balance;
                $balance += $qty;
                $events[] = $this->stockIn(
                    $product->id, $invUsers,
                    $qty, $before, $balance,
                    round($costPrice * (1 + rand(-3, 8) / 100), 2),
                    $this->poRef($product->sku, $organicDate->year, $poSeq++),
                    'Scheduled replenishment',
                    clone $organicDate
                );
            }
            $organicDate->addDays(rand(45, 90));
        }

        // ── Process actual sale lines chronologically ────────────────────────
        $prevDate    = clone $initDate;
        $lastDmgDays = 999;

        foreach ($lines as $line) {
            $saleDate = Carbon::parse($line->sale_date);
            $qty      = (int)$line->quantity;
            $cashier  = (int)$line->cashier_id;
            $receipt  = $line->receipt_number;

            // Gap since previous event — use for organic restocks & adjustments
            $gapDays = max(0, $prevDate->diffInDays($saleDate));

            // If there's a big gap (60+ days) add an organic restock mid-gap
            if ($gapDays >= 60) {
                $midDate = (clone $prevDate)->addDays((int)($gapDays / 2));
                if ($midDate->lt($saleDate)) {
                    $orgQty  = $reorderQty * rand(1, 2);
                    $before  = $balance;
                    $balance += $orgQty;
                    $events[] = $this->stockIn(
                        $product->id, $invUsers,
                        $orgQty, $before, $balance,
                        round($costPrice * (1 + rand(-3, 8) / 100), 2),
                        $this->poRef($product->sku, $midDate->year, $poSeq++),
                        'Scheduled replenishment',
                        $midDate
                    );
                }
            }

            // Restock if balance is insufficient to cover this sale
            if ($balance < $qty || $balance <= $reorderPt) {
                $needed  = max($reorderQty * rand(2, 4), $qty - $balance + $reorderQty);
                $before  = $balance;
                $balance += $needed;

                // Arrive 1-3 days before the sale
                $restockDate = (clone $saleDate)->subDays(rand(1, 3));
                if ($restockDate->lte($prevDate)) {
                    $restockDate = (clone $prevDate)->addHours(rand(2, 8));
                }

                $events[] = $this->stockIn(
                    $product->id, $invUsers,
                    $needed, $before, $balance,
                    round($costPrice * (1 + rand(-3, 8) / 100), 2),
                    $this->poRef($product->sku, $restockDate->year, $poSeq++),
                    'Replenishment order received',
                    $restockDate
                );
            }

            // The actual sale movement — uses real receipt + cashier
            $saleBefore = $balance;
            $balance   -= $qty;
            $events[]   = $this->row(
                $product->id, $cashier,
                'sale', $qty, $saleBefore, $balance,
                null, $receipt, null,
                clone $saleDate
            );

            // Occasional damage/loss (~1% per sale, max once every 90 days)
            $lastDmgDays += $gapDays + 1;
            if ($balance > 1 && $lastDmgDays >= 90 && rand(1, 100) === 1) {
                $dmgDate = (clone $saleDate)->addDays(rand(1, 4));
                $type    = rand(0, 1) ? 'damage' : 'loss';
                $before  = $balance;
                $balance -= 1;
                $events[] = $this->row(
                    $product->id, $invUsers[array_rand($invUsers)],
                    $type, 1, $before, $balance,
                    null, null,
                    $type === 'damage' ? 'Item damaged during handling' : 'Inventory discrepancy noted',
                    $dmgDate
                );
                $lastDmgDays = 0;
            }

            // Occasional physical count adjustment (~0.5% per sale, always a small reduction)
            if ($balance > 2 && rand(1, 200) === 1) {
                $adjDate = (clone $saleDate)->addDays(rand(2, 7));
                $adjQty  = rand(1, min(2, $balance - 1));
                $before  = $balance;
                $balance -= $adjQty;
                $events[] = $this->row(
                    $product->id, $invUsers[array_rand($invUsers)],
                    'adjustment', $adjQty, $before, $balance,
                    null, null,
                    'Physical count — stock adjustment',
                    $adjDate
                );
            }

            $prevDate = clone $saleDate;
        }

        // ── Final restock if still active ───────────────────────────────────
        // Ensures product shows a recent delivery even if last sale was a while ago
        $recentCutoff = Carbon::now()->subDays(90);
        if ($prevDate->lt($recentCutoff) && $balance <= $reorderPt) {
            $qty     = $reorderQty * rand(2, 3);
            $before  = $balance;
            $balance += $qty;
            $recentDate = Carbon::now()->subDays(rand(14, 60));
            $events[] = $this->stockIn(
                $product->id, $invUsers,
                $qty, $before, $balance,
                round($costPrice * (1 + rand(-2, 6) / 100), 2),
                $this->poRef($product->sku, $recentDate->year, $poSeq++),
                'Replenishment order received',
                $recentDate
            );
        }

        // ── Sort all events chronologically, recompute running balance ───────
        usort($events, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

        // Sorting can move a damage/adjustment ahead of a restock, which would
        // briefly push stock below zero. Rather than clamping (which breaks the
        // totals), top up the opening delivery by the shortfall.
        $running = 0;
        $lowest  = 0;
        foreach ($events as $ev) {
            $running += $this->delta($ev);
            $lowest   = min($lowest, $running);
        }
        if ($lowest < 0) {
            foreach ($events as &$ev) {
                if ($ev['type'] === 'stock_in') { $ev['quantity'] += -$lowest; break; }
            }
            unset($ev);
        }

        // Walk forward once more — no clamping, so before/after always chain exactly.
        $running = 0;
        foreach ($events as &$ev) {
            $ev['quantity_before'] = $running;
            $running += $this->delta($ev);
            $ev['quantity_after']  = $running;
        }
        unset($ev);

        return [$events, $running];
    }

    private function delta(array $ev): int
    {
        return match ($ev['type']) {
            'stock_in', 'return'                   => $ev['quantity'],
            'sale', 'damage', 'loss', 'adjustment' => -$ev['quantity'],
            default                                => 0,
        };
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function stockIn(int $productId, array $invUsers, int $qty, int $before, int $after, float $cost, string $ref, string $notes, Carbon $date): array
    {
        return $this->row($productId, $invUsers[array_rand($invUsers)], 'stock_in', $qty, $before, $after, $cost, $ref, $notes, $date);
    }

    private function row(int $productId, int $userId, string $type, int $qty, int $before, int $after, ?float $unitCost, ?string $ref, ?string $notes, Carbon $date): array
    {
        $ts = $date->format('Y-m-d H:i:s');
        return [
            'product_id'       => $productId,
            'user_id'          => $userId,
            'type'             => $type,
            'quantity'         => abs($qty),
            'quantity_before'  => max(0, $before),
            'quantity_after'   => max(0, $after),
            'unit_cost'        => $unitCost,
            'reference_number' => $ref,
            'notes'            => $notes,
            'created_at'       => $ts,
            'updated_at'       => $ts,
        ];
    }

    private function poRef(string $sku, int $year, int $seq): string
    {
        return 'PO-' . strtoupper(substr($sku, 0, 3)) . '-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}