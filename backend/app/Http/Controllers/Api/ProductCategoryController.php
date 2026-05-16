<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        return response()->json(ProductCategory::withCount('products')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:frame,lens,accessory,other',
            'description' => 'nullable|string',
        ]);

        return response()->json(ProductCategory::create($validated), 201);
    }

    public function show(ProductCategory $productCategory)
    {
        return response()->json($productCategory->load('products'));
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $productCategory->update($request->validate([
            'name'        => 'sometimes|string|max:100',
            'type'        => 'sometimes|in:frame,lens,accessory,other',
            'description' => 'nullable|string',
        ]));

        return response()->json($productCategory->fresh());
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
