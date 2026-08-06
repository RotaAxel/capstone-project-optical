<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['patient', 'cashier', 'items.product'])
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('receipt_number', 'like', "%{$request->search}%")
                  ->orWhereHas('patient', fn($pq) =>
                      $pq->where('first_name', 'like', "%{$request->search}%")
                         ->orWhere('last_name', 'like', "%{$request->search}%")
                  );
            }))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        $perPage = min((int) ($request->per_page ?? 15), 100);
        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'      => 'nullable|exists:patients,id',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'payment_method'  => 'required|in:cash,card,gcash,maya,other',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount_paid'     => 'required|numeric|min:0',
            'notes'           => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // Lock all products first and validate stock before any writes
            $productMap = [];
            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                if ($product->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for \"{$product->name}\". Available: {$product->stock_quantity}, requested: {$item['quantity']}."],
                    ]);
                }
                $productMap[$item['product_id']] = $product;
            }

            // Validate per-item discount does not exceed line price
            foreach ($validated['items'] as $item) {
                $lineMax = $item['unit_price'] * $item['quantity'];
                if (($item['discount'] ?? 0) > $lineMax) {
                    throw ValidationException::withMessages([
                        'items' => ["Item discount cannot exceed its line total (max ₱{$lineMax})."],
                    ]);
                }
            }

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0);
            }

            $discount = $validated['discount_amount'] ?? 0;
            $total    = max(0, $subtotal - $discount);
            $change   = $validated['amount_paid'] - $total;

            if ($validated['amount_paid'] < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => ["Amount paid (₱{$validated['amount_paid']}) is less than the total (₱{$total})."],
                ]);
            }

            // Create with a guaranteed-unique temp receipt; overwrite with ID-based number below
            $sale = Sale::create([
                'receipt_number'  => 'TEMP-' . strtoupper(Str::uuid()),
                'patient_id'      => $validated['patient_id'] ?? null,
                'cashier_id'      => $request->user()->id,
                'prescription_id' => $validated['prescription_id'] ?? null,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => 0,
                'total_amount'    => $total,
                'amount_paid'     => $validated['amount_paid'],
                'change_amount'   => max(0, $change),
                'payment_method'  => $validated['payment_method'],
                'status'          => 'completed',
                'notes'           => $validated['notes'] ?? null,
            ]);

            // ID is now known — use it for a collision-free, human-readable receipt number
            $sale->receipt_number = 'REC-' . str_pad($sale->id, 8, '0', STR_PAD_LEFT);
            $sale->save();

            foreach ($validated['items'] as $item) {
                $product      = $productMap[$item['product_id']];
                $itemSubtotal = ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount'   => $item['discount'] ?? 0,
                    'subtotal'   => $itemSubtotal,
                ]);

                $before = $product->stock_quantity;
                $after  = $before - $item['quantity'];
                $product->update(['stock_quantity' => $after]);

                StockMovement::create([
                    'product_id'       => $product->id,
                    'user_id'          => $request->user()->id,
                    'type'             => 'sale',
                    'quantity'         => $item['quantity'],
                    'quantity_before'  => $before,
                    'quantity_after'   => $after,
                    'reference_number' => $sale->receipt_number,
                ]);
            }

            return response()->json($sale->load(['patient', 'cashier', 'items.product']), 201);
        });
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load(['patient', 'cashier', 'items.product', 'prescription']));
    }

    public function update(Request $request, Sale $sale) { /* reserved */ }

    public function destroy(Sale $sale)
    {
        $sale->delete(); // soft delete — record retained for audit trail
        return response()->json(null, 204);
    }

    public function stats(Request $request)
    {
        $stats = DB::table('sales')
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                SUM(CASE WHEN payment_method = 'cash' THEN 1 ELSE 0 END) as cash_count,
                SUM(CASE WHEN payment_method IN ('card', 'gcash', 'maya') THEN 1 ELSE 0 END) as digital_count
            ")
            ->first();
        return response()->json($stats);
    }
}
