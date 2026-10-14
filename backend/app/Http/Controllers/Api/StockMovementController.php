<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $query = StockMovement::with(['product', 'user'])
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->month, fn($q) => $this->inMonth($q, $request->month))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($pq) =>
                $pq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('sku', 'like', "%{$request->search}%")
            ));

        $perPage = min((int)($request->per_page ?? 20), 100);
        return response()->json($query->latest()->paginate($perPage));
    }

    public function summary(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $counts = StockMovement::when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->month, fn($q) => $this->inMonth($q, $request->month))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($pq) =>
                $pq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('sku', 'like', "%{$request->search}%")
            ))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return response()->json($counts);
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
