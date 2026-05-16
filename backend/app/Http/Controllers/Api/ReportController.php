<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function salesDaily(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $sales = Sale::with(['patient', 'cashier', 'items.product'])
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();

        return response()->json([
            'date'              => $date,
            'total_transactions'=> $sales->count(),
            'total_revenue'     => $sales->sum('total_amount'),
            'total_discount'    => $sales->sum('discount_amount'),
            'sales'             => $sales,
        ]);
    }

    public function salesMonthly(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $daily = Sale::selectRaw('DATE(created_at) as date, COUNT(*) as transactions, SUM(total_amount) as revenue')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'month'             => $month,
            'year'              => $year,
            'total_transactions'=> $daily->sum('transactions'),
            'total_revenue'     => $daily->sum('revenue'),
            'daily_breakdown'   => $daily,
        ]);
    }

    public function inventoryReport()
    {
        $products = Product::with(['category', 'supplier'])
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'sku'            => $p->sku,
                'name'           => $p->name,
                'category'       => $p->category?->name,
                'stock_quantity' => $p->stock_quantity,
                'reorder_point'  => $p->reorder_point,
                'is_low_stock'   => $p->is_low_stock,
                'cost_price'     => $p->cost_price,
                'selling_price'  => $p->selling_price,
                'stock_value'    => $p->stock_quantity * $p->cost_price,
            ]);

        return response()->json([
            'total_products'   => $products->count(),
            'low_stock_count'  => $products->where('is_low_stock', true)->count(),
            'total_stock_value'=> $products->sum('stock_value'),
            'products'         => $products,
        ]);
    }

    public function topProducts(Request $request)
    {
        $limit    = $request->limit ?? 10;
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $top = SaleItem::with('product.category')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        return response()->json(['date_from' => $dateFrom, 'date_to' => $dateTo, 'products' => $top]);
    }
}
