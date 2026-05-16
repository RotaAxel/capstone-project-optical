<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:200',
            'contact_person' => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email',
            'address'        => 'nullable|string',
            'tin'            => 'nullable|string',
        ]);

        return response()->json(Supplier::create($validated), 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier->load('products'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate([
            'name'           => 'sometimes|string|max:200',
            'contact_person' => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email',
            'address'        => 'nullable|string',
            'tin'            => 'nullable|string',
            'is_active'      => 'sometimes|boolean',
        ]));

        return response()->json($supplier->fresh());
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted.']);
    }
}
