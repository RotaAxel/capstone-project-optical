<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int)($request->per_page ?? 16), 100);

        $query = Product::with(['category', 'supplier'])
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('brand', 'like', "%{$request->search}%");
            }))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->low_stock, fn($q) => $q->whereColumn('stock_quantity', '<=', 'reorder_point'));

        if ($request->highlight_id) {
            $target = Product::find($request->highlight_id);
            if ($target) {
                // Stable sort: created_at DESC, id DESC — count rows that come before target
                $before = (clone $query)->where(function ($q) use ($target) {
                    $q->where('created_at', '>', $target->created_at)
                      ->orWhere(fn($q2) => $q2->where('created_at', $target->created_at)->where('id', '>', $target->id));
                })->count();
                $page = (int) floor($before / $perPage) + 1;
                return response()->json($query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page));
            }
        }

        return response()->json($query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage));
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
            'image'            => 'nullable|image|max:2048',
        ]);

        // Temp UUID SKU satisfies NOT NULL + unique; overwritten with ID-based SKU below
        $validated['sku'] = 'TEMP-' . strtoupper(Str::uuid());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $product = Product::create($validated);
        $product->sku = 'SKU-' . str_pad($product->id, 8, '0', STR_PAD_LEFT);
        $product->save();

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
            'image'            => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_url) {
                $old = ltrim(str_replace('/storage', '', $product->image_url), '/');
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }

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
            'quantity'         => 'required|integer|min:1|max:10000',
            'unit_cost'        => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
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

    public function stats()
    {
        $stats = DB::table('products')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= reorder_point THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                COALESCE(SUM(stock_quantity * cost_price), 0) as stock_value
            ")
            ->first();
        return response()->json($stats);
    }

    public function adjust(Request $request, Product $product)
    {
        $validated = $request->validate([
            'type'             => 'required|in:adjustment,damage,loss',
            'quantity'         => 'required|integer|min:1',
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $before = $product->stock_quantity;
        $actual = min($validated['quantity'], $before);
        $after  = $before - $actual;

        $product->update(['stock_quantity' => $after]);

        $movement = StockMovement::create([
            'product_id'       => $product->id,
            'user_id'          => $request->user()->id,
            'type'             => $validated['type'],
            'quantity'         => $actual,
            'quantity_before'  => $before,
            'quantity_after'   => $after,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes'            => $validated['notes'] ?? null,
        ]);

        return response()->json(['product' => $product->fresh(), 'movement' => $movement]);
    }
}
