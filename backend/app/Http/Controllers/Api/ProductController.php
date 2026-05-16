<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplier'])
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('brand', 'like', "%{$request->search}%");
            }))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->low_stock, fn($q) => $q->whereColumn('stock_quantity', '<=', 'reorder_point'));

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:200',
            'category_id'      => 'required|exists:product_categories,id',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'description'      => 'nullable|string',
            'brand'            => 'nullable|string',
            'model'            => 'nullable|string',
            'color'            => 'nullable|string',
            'size'             => 'nullable|string',
            'cost_price'       => 'required|numeric|min:0',
            'selling_price'    => 'required|numeric|min:0',
            'stock_quantity'   => 'required|integer|min:0',
            'reorder_point'    => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:1',
        ]);

        $validated['sku'] = 'SKU-' . strtoupper(Str::random(8));

        $product = Product::create($validated);

        if ($validated['stock_quantity'] > 0) {
            StockMovement::create([
                'product_id'      => $product->id,
                'user_id'         => $request->user()->id,
                'type'            => 'stock_in',
                'quantity'        => $validated['stock_quantity'],
                'quantity_before' => 0,
                'quantity_after'  => $validated['stock_quantity'],
                'notes'           => 'Initial stock',
            ]);
        }

        return response()->json($product->load(['category', 'supplier']), 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['category', 'supplier', 'stockMovements' => fn($q) => $q->latest()->limit(20)]));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'             => 'sometimes|string|max:200',
            'category_id'      => 'sometimes|exists:product_categories,id',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'description'      => 'nullable|string',
            'brand'            => 'nullable|string',
            'model'            => 'nullable|string',
            'color'            => 'nullable|string',
            'size'             => 'nullable|string',
            'cost_price'       => 'sometimes|numeric|min:0',
            'selling_price'    => 'sometimes|numeric|min:0',
            'reorder_point'    => 'sometimes|integer|min:0',
            'reorder_quantity' => 'sometimes|integer|min:1',
            'is_active'        => 'sometimes|boolean',
        ]);

        $product->update($validated);
        return response()->json($product->fresh(['category', 'supplier']));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted.']);
    }

    public function stockIn(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity'         => 'required|integer|min:1',
            'unit_cost'        => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $before = $product->stock_quantity;
        $after  = $before + $validated['quantity'];

        $product->update(['stock_quantity' => $after]);

        $movement = StockMovement::create([
            'product_id'       => $product->id,
            'user_id'          => $request->user()->id,
            'type'             => 'stock_in',
            'quantity'         => $validated['quantity'],
            'quantity_before'  => $before,
            'quantity_after'   => $after,
            'unit_cost'        => $validated['unit_cost'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes'            => $validated['notes'] ?? null,
        ]);

        return response()->json(['product' => $product->fresh(), 'movement' => $movement]);
    }
}
