<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\AnalyticsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function salesDaily(Request $request)
    {
        $request->validate(['date' => 'nullable|date']);
        $date = $request->date ?? now()->toDateString();

        $stats = Sale::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue, COALESCE(SUM(discount_amount), 0) as total_discount')
            ->first();

        $sales = Sale::with(['patient', 'cashier', 'items.product'])
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->orderBy('id')
            ->get();

        return response()->json([
            'date'               => $date,
            'total_transactions' => (int) $stats->total_transactions,
            'total_revenue'      => (float) $stats->total_revenue,
            'total_discount'     => (float) $stats->total_discount,
            'sales'              => $sales,
        ]);
    }

    public function salesMonthly(Request $request)
    {
        $request->validate([
            'month_from' => 'nullable|integer|min:1|max:12',
            'month_to'   => 'nullable|integer|min:1|max:12',
            'year'       => 'nullable|integer|min:2000|max:2100',
        ]);
        $year      = $request->year ?? now()->year;
        $monthFrom = $request->month_from ?? now()->month;
        $monthTo   = $request->month_to ?? $monthFrom;
        if ($monthTo < $monthFrom) {
            [$monthFrom, $monthTo] = [$monthTo, $monthFrom];
        }

        $start = \Illuminate\Support\Carbon::create($year, $monthFrom, 1)->startOfMonth();
        $end   = \Illuminate\Support\Carbon::create($year, $monthTo, 1)->endOfMonth();

        $daily = Sale::selectRaw('DATE(created_at) as date, COUNT(*) as transactions, SUM(total_amount) as revenue')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'year'              => $year,
            'month_from'        => $monthFrom,
            'month_to'          => $monthTo,
            'total_transactions'=> $daily->sum('transactions'),
            'total_revenue'     => $daily->sum('revenue'),
            'daily_breakdown'   => $daily,
        ]);
    }

    public function salesYearly(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $year = (int) $request->year;

        $stats = Sale::whereYear('created_at', $year)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue, COALESCE(SUM(discount_amount), 0) as total_discount')
            ->first();

        // Single summary row for the selected year.
        $yearly = Sale::selectRaw('YEAR(created_at) as year, COUNT(*) as transactions, SUM(total_amount) as revenue, SUM(discount_amount) as discount')
            ->whereYear('created_at', $year)
            ->where('status', 'completed')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        return response()->json([
            'year'               => $year,
            'total_transactions' => (int) $stats->total_transactions,
            'total_revenue'      => (float) $stats->total_revenue,
            'total_discount'     => (float) $stats->total_discount,
            'yearly_breakdown'   => $yearly,
        ]);
    }

    public function inventoryReport()
    {
        $stats = DB::table('products')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total_products, SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count, SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= reorder_point THEN 1 ELSE 0 END) as low_stock_count, COALESCE(SUM(stock_quantity * cost_price), 0) as total_stock_value')
            ->first();

        // Most recent FSN classification + turnover ratio per product, from the last analytics run.
        $latestAnalytics = AnalyticsLog::whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('analytics_logs')->groupBy('product_id');
            })
            ->get(['product_id', 'fsn_classification', 'turnover_ratio'])
            ->keyBy('product_id');

        $deadStockCount = $latestAnalytics->where('fsn_classification', 'non_moving')->count();

        $products = Product::select(['id', 'sku', 'name', 'category_id', 'supplier_id', 'stock_quantity', 'reorder_point', 'cost_price', 'selling_price'])
            ->with(['category:id,name', 'supplier:id,name'])
            ->get()
            ->map(function ($p) use ($latestAnalytics) {
                $analytics = $latestAnalytics->get($p->id);
                return [
                    'id'                 => $p->id,
                    'sku'                => $p->sku,
                    'name'               => $p->name,
                    'category'           => $p->category?->name,
                    'stock_quantity'     => $p->stock_quantity,
                    'reorder_point'      => $p->reorder_point,
                    'is_out_of_stock'    => $p->stock_quantity === 0,
                    'is_low_stock'       => $p->stock_quantity > 0 && $p->stock_quantity <= $p->reorder_point,
                    'cost_price'         => $p->cost_price,
                    'selling_price'      => $p->selling_price,
                    'stock_value'        => $p->stock_quantity * $p->cost_price,
                    'fsn_classification' => $analytics?->fsn_classification,
                    'turnover_ratio'     => $analytics?->turnover_ratio !== null ? (float) $analytics->turnover_ratio : null,
                ];
            });

        return response()->json([
            'total_products'    => (int) $stats->total_products,
            'out_of_stock_count'=> (int) $stats->out_of_stock_count,
            'low_stock_count'   => (int) $stats->low_stock_count,
            'total_stock_value' => (float) $stats->total_stock_value,
            'dead_stock_count'  => $deadStockCount,
            'products'          => $products,
        ]);
    }

    public function topProducts(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
            'limit'     => 'nullable|integer|min:1',
        ]);
        $limit    = min((int) ($request->limit ?? 10), 100);
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $top = SaleItem::with('product.category')
            ->whereHas('sale', fn($q) => $q->where('status', 'completed'))
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        return response()->json(['date_from' => $dateFrom, 'date_to' => $dateTo, 'products' => $top]);
    }
}
