<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * One row per product, totaling every movement that matches the filters.
     * A new stock-in or sale for a product adds to that product's existing row
     * instead of creating a new one.
     */
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $query = $this->filtered(StockMovement::query(), $request)
            ->with('product')
            ->selectRaw("
                product_id,
                SUM(CASE WHEN type IN ('stock_in', 'return') THEN ABS(quantity) ELSE 0 END) AS stock_in_qty,
                SUM(CASE WHEN type = 'sale' THEN ABS(quantity) ELSE 0 END)                  AS stock_out_qty,
                SUM(CASE
                        WHEN type = 'adjustment'          THEN quantity
                        WHEN type IN ('damage', 'loss')   THEN -ABS(quantity)
                        ELSE 0
                    END)                                                                     AS adjustment_qty,
                COUNT(*)        AS movement_count,
                MAX(created_at) AS last_movement_at,
                MAX(id)         AS last_movement_id
            ")
            ->groupBy('product_id')
            ->orderByDesc('last_movement_at');

        $perPage = min((int) ($request->per_page ?? 20), 100);
        $page    = $query->paginate($perPage);

        // The latest movement of each product gives the running total stock
        // (quantity_after) plus the last reference, reason and recorded-by user.
        $latest = StockMovement::with('user')
            ->whereIn('id', $page->getCollection()->pluck('last_movement_id'))
            ->get()
            ->keyBy('id');

        $page->getCollection()->transform(function ($row) use ($latest) {
            $last = $latest->get($row->last_movement_id);
            return [
                'product_id'       => $row->product_id,
                'product'          => $row->product,
                'stock_in_qty'     => (int) $row->stock_in_qty,
                'stock_out_qty'    => (int) $row->stock_out_qty,
                'adjustment_qty'   => (int) $row->adjustment_qty,
                'movement_count'   => (int) $row->movement_count,
                'last_movement_at' => $last?->created_at ?? $row->last_movement_at,
                'quantity_after'   => $last?->quantity_after,
                'reference_number' => $last?->reference_number,
                'notes'            => $last?->notes,
                'user'             => $last?->user,
            ];
        });

        return response()->json($page);
    }

    public function summary(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $counts = $this->filtered(StockMovement::query(), $request)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return response()->json($counts);
    }

    /** Filters shared by the list and the summary. */
    private function filtered($query, Request $request)
    {
        return $query
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->month, fn($q) => $this->inMonth($q, $request->month))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($pq) =>
                $pq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('sku', 'like', "%{$request->search}%")
            ));
    }

    /** Limits a query to one calendar month ("YYYY-MM") — used for the owner's monthly adjustment review. */
    private function inMonth($query, string $month)
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        return $query->whereBetween('created_at', [$start, $start->copy()->endOfMonth()]);
    }

    public function show(StockMovement $stockMovement)
    {
        return response()->json($stockMovement->load(['product', 'user']));
    }
}