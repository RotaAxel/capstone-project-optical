<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'user'])
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($pq) =>
                $pq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('sku', 'like', "%{$request->search}%")
            ));

        $perPage = min((int)($request->per_page ?? 20), 100);
        return response()->json($query->latest()->paginate($perPage));
    }

    public function summary(Request $request)
    {
        $counts = StockMovement::when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($pq) =>
                $pq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('sku', 'like', "%{$request->search}%")
            ))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return response()->json($counts);
    }

    public function show(StockMovement $stockMovement)
    {
        return response()->json($stockMovement->load(['product', 'user']));
    }
}
