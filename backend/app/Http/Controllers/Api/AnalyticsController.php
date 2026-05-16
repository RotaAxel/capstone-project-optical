<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\AnalyticsLog;
class AnalyticsController extends Controller
{
    public function run()
    {
        $products = Product::with('saleItems')->get();
        $results  = [];

        foreach ($products as $product) {
            $salesLast90 = SaleItem::where('product_id', $product->id)
                ->where('created_at', '>=', now()->subDays(90))
                ->sum('quantity');

            $avgDailyDemand = $salesLast90 / 90;
            $leadTimeDays   = 7;

            // EOQ: sqrt((2 * annual_demand * order_cost) / holding_cost)
            $annualDemand  = $avgDailyDemand * 365;
            $orderCost     = 500;
            $holdingCost   = max(1, $product->cost_price * 0.20);
            $eoq           = $annualDemand > 0 ? sqrt((2 * $annualDemand * $orderCost) / $holdingCost) : 0;

            // ROP: avg daily demand * lead time
            $rop = $avgDailyDemand * $leadTimeDays;

            // FSN classification by avg daily demand
            $fsn = match(true) {
                $avgDailyDemand >= 1  => 'fast',
                $avgDailyDemand >= 0.1 => 'slow',
                default               => 'non_moving',
            };

            // Simple ARIMA-like prediction (linear trend on last 3 months)
            $predicted = $avgDailyDemand * 30;

            $log = AnalyticsLog::updateOrCreate(
                ['product_id' => $product->id, 'method' => 'arima', 'computed_at' => now()->toDateString()],
                [
                    'result_data'       => ['sales_90d' => $salesLast90, 'avg_daily' => round($avgDailyDemand, 4)],
                    'predicted_demand'  => round($predicted, 2),
                    'eoq_value'         => round($eoq, 2),
                    'rop_value'         => round($rop, 2),
                    'fsn_classification'=> $fsn,
                ]
            );

            $results[] = [
                'product'    => $product->only(['id', 'name', 'sku', 'stock_quantity', 'reorder_point', 'cost_price', 'selling_price']),
                'analytics'  => $log,
            ];
        }

        return response()->json(['results' => $results, 'computed_at' => now()]);
    }

    public function summary()
    {
        $latest = AnalyticsLog::with('product')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('analytics_logs')->groupBy('product_id');
            })
            ->get();

        $fast      = $latest->where('fsn_classification', 'fast')->count();
        $slow      = $latest->where('fsn_classification', 'slow')->count();
        $nonMoving = $latest->where('fsn_classification', 'non_moving')->count();

        return response()->json([
            'fsn_summary'   => compact('fast', 'slow', 'nonMoving'),
            'items'         => $latest,
        ]);
    }
}
