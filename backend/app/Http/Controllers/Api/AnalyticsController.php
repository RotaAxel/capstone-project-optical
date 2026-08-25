<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\AnalyticsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private float $orderCost   = 500.0;
    private float $holdingRate = 0.20;
    private float $serviceZ    = 1.645;
    private int   $leadTime    = 7;
    private int   $weekWindow  = 208; // 4 years — gives HW 4 full seasonal cycles to learn from
    private int   $dayWindow   = 1460;

    // ─── Public Endpoints ─────────────────────────────────────────────────

    public function run()
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $products = Product::select(['id', 'name', 'sku', 'cost_price', 'selling_price', 'stock_quantity', 'reorder_point'])->get();
        $results  = [];

        $weeklySince = now()->subWeeks($this->weekWindow)->startOfWeek();
        $dailySince  = now()->subDays($this->dayWindow - 1)->startOfDay();

        // Pre-aggregate in SQL — avoids loading raw sale item rows into PHP memory
        $weeklyAgg = DB::table('sale_items')
            ->selectRaw('product_id, DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY)) AS week_start, SUM(quantity) AS qty')
            ->where('created_at', '>=', $weeklySince)
            ->whereNull('deleted_at')
            ->groupBy('product_id', 'week_start')
            ->get()
            ->groupBy('product_id');

        $dailyAgg = DB::table('sale_items')
            ->selectRaw('product_id, DATE(created_at) AS sale_date, SUM(quantity) AS qty')
            ->where('created_at', '>=', $dailySince)
            ->whereNull('deleted_at')
            ->groupBy('product_id', 'sale_date')
            ->get()
            ->groupBy('product_id');

        $lastSaleMap = DB::table('sale_items')
            ->selectRaw('product_id, MAX(created_at) AS last_sale_at')
            ->where('created_at', '>=', $weeklySince)
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->pluck('last_sale_at', 'product_id');

        foreach ($products as $product) {
            $weeklySeries = $this->buildWeeklySeriesFromAgg(
                $weeklyAgg->get($product->id, collect()), $this->weekWindow
            );
            $dailySeries = $this->buildDailySeriesFromAgg(
                $dailyAgg->get($product->id, collect()), $this->dayWindow
            );

            $n        = count($dailySeries);
            $avgDaily = $n > 0 ? array_sum($dailySeries) / $n : 0.0;
            $stdDaily = $this->sampleStdDev($dailySeries);

            // ── Method selection based on demand sparsity ───────────────────
            $method         = $this->selectMethod($weeklySeries);
            $forecastResult = $this->forecast($weeklySeries, $method, 24); // 24 weeks = 6 months

            // Aggregate 24 weekly values into 6 monthly buckets (4 weeks each)
            $forecastMonthly  = [];
            $lowerMonthly     = [];
            $upperMonthly     = [];
            for ($m = 0; $m < 6; $m++) {
                $forecastMonthly[] = round(array_sum(array_slice($forecastResult['forecast'], $m * 4, 4)), 2);
                $lowerMonthly[]    = round(max(0, array_sum(array_slice($forecastResult['lower'],   $m * 4, 4))), 2);
                $upperMonthly[]    = round(array_sum(array_slice($forecastResult['upper'],   $m * 4, 4)), 2);
            }

            // 30-day predicted demand = first month (4 weeks) scaled to 30 days
            $predictedMonthly = array_sum(array_slice($forecastResult['forecast'], 0, 4)) * (30.0 / 7.0);

            // ── WMAPE via hold-out with the same method ─────────────────────
            $mape = $this->computeWMAPE($weeklySeries, $method);

            // ── EOQ: √(2·D·S / H) ────────────────────────────────────────
            $annualDemand = $avgDaily * 365.0;
            $holdingCost  = max(0.01, $product->cost_price * $this->holdingRate);
            $eoq = $annualDemand > 0
                ? sqrt((2.0 * $annualDemand * $this->orderCost) / $holdingCost)
                : 0.0;

            // ── ROP: d̄·L + Z·σ·√L ───────────────────────────────────────
            $safetyStock = $this->serviceZ * $stdDaily * sqrt($this->leadTime);
            $rop         = ($avgDaily * $this->leadTime) + $safetyStock;

            // ── FSN classification ────────────────────────────────────────
            $fsn    = $this->classifyFSN($weeklyAgg->get($product->id, collect()), $avgDaily, $lastSaleMap->get($product->id));
            $params = $forecastResult['params'];

            // ── Turnover Ratio: Annual Demand / Average Inventory ─────────
            // How many times this product's stock fully sells through and gets
            // replaced per year. No return/refund data is involved — it is purely
            // units sold vs. units held, so the client's no-return policy has no
            // bearing on this metric. Current stock_quantity is used as the proxy
            // for average inventory (no historical stock-level snapshots exist).
            // Null when there is no stock to divide by (can't compute a ratio for
            // an out-of-stock item); 0 when neither sales nor stock exist.
            $turnoverRatio = $product->stock_quantity > 0
                ? round($annualDemand / $product->stock_quantity, 2)
                : ($annualDemand > 0 ? null : 0.0);

            $log = AnalyticsLog::updateOrCreate(
                [
                    'product_id'  => $product->id,
                    'computed_at' => now()->toDateString(),
                ],
                [
                    'method' => $method,
                    'result_data' => [
                        // Method metadata
                        'method_used'     => $method,
                        'arima_order'     => $forecastResult['arima_order_selected'] ?? null,
                        'ar_param'        => isset($params['phi'][0])   ? round($params['phi'][0],   4) : null,
                        'ma_param'        => isset($params['theta'][0]) ? round($params['theta'][0], 4) : null,
                        'diff_mean'       => round($params['mean'] ?? 0, 4),
                        'used_fallback'   => $forecastResult['fallback'],
                        'hw_gamma'        => isset($params['gamma'])   ? round($params['gamma'], 4) : null,
                        'hw_variant'      => $params['variant'] ?? 'additive',
                        // Forecast outputs (6-month monthly aggregation)
                        'forecast_monthly'    => $forecastMonthly,
                        'conf_lower_monthly'  => $lowerMonthly,
                        'conf_upper_monthly'  => $upperMonthly,
                        'conf_lower_30d'      => round($lowerMonthly[0] * (30.0 / 7.0), 2),
                        'conf_upper_30d'      => round($upperMonthly[0] * (30.0 / 7.0), 2),
                        'wmape_pct'       => $mape >= 0 ? $mape : null,
                        'weekly_series'   => $weeklySeries,
                        // Demand statistics (last 90 days for display, full window for computation)
                        'sales_90d'       => (int) array_sum(array_slice($dailySeries, -90)),
                        'avg_daily'       => round($avgDaily, 4),
                        'std_daily'       => round($stdDaily, 4),
                        // ROP components
                        'safety_stock'    => round($safetyStock, 2),
                        'lead_time'       => $this->leadTime,
                        'service_level'   => '95%',
                        // EOQ components
                        'annual_demand'   => round($annualDemand, 2),
                        'order_cost'      => $this->orderCost,
                        'holding_cost'    => round($holdingCost,  2),
                        // FSN detail
                        'active_weeks'    => $fsn['active_weeks'],
                        'total_weeks'     => $fsn['total_weeks'],
                        'activity_ratio'  => round($fsn['activity_ratio'], 4),
                        'last_sale_date'  => $fsn['last_sale_date'],
                        'months_no_sale'  => $fsn['months_no_sale'],
                        // Turnover ratio components
                        'turnover_avg_inventory' => $product->stock_quantity,
                    ],
                    'predicted_demand'   => round($predictedMonthly, 2),
                    'eoq_value'          => round($eoq, 2),
                    'rop_value'          => round($rop, 2),
                    'fsn_classification' => $fsn['classification'],
                    'turnover_ratio'     => $turnoverRatio,
                ]
            );

            $results[] = [
                'product'   => $product->only(['id', 'name', 'sku', 'stock_quantity', 'reorder_point', 'cost_price', 'selling_price']),
                'analytics' => $log,
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

        $lastRun = $latest->max('computed_at');            // date string e.g. "2026-05-29"
        $isStale = !$lastRun || $lastRun < now()->toDateString();

        return response()->json([
            'fsn_summary' => compact('fast', 'slow', 'nonMoving'),
            'is_stale'    => $isStale,
            'last_run'    => $lastRun,
            'items'       => $latest,
        ]);
    }

    // ─── Method Selection ─────────────────────────────────────────────────

    /**
     * Choose forecasting algorithm based on demand sparsity (activity ratio).
     *
     *   ≥ 40% active weeks → Auto-ARIMA  (regular / fast-moving)
     *   10–40% active      → Croston's   (intermittent demand)
     *   < 10% active       → Optimised SES (rare / non-moving)
     */
    private function selectMethod(array $series): string
    {
        $n       = count($series);
        $nonZero = count(array_filter($series, fn($v) => $v > 0));
        $ratio   = $n > 0 ? $nonZero / $n : 0.0;

        if ($ratio >= 0.40) {
            // Always prefer HW for regular items with enough data.
            // HW with full (α,β,γ) optimisation degrades gracefully to Holt's linear
            // trend (γ→0) when there is no seasonal signal, so it is never worse than
            // ARIMA and avoids the false-negative risk of ACF-based seasonality tests.
            if ($n >= 104) return 'holt_winters';
            return 'auto_arima';
        }
        if ($ratio >= 0.10) return 'croston';
        return 'ses';
    }

    /** Dispatch to the right algorithm. */
    private function forecast(array $series, string $method, int $steps): array
    {
        return match ($method) {
            'holt_winters' => $this->holtWinters($series, $steps),
            'croston'      => $this->crostonMethod($series, $steps),
            'ses'          => $this->optimizedSES($series, $steps),
            'auto_arima'   => $this->autoARIMA($series, $steps),
            default        => $this->runARIMA($series, 1, 1, 1, $steps),
        };
    }

    // ─── Auto-ARIMA ───────────────────────────────────────────────────────

    /**
     * Tests 5 candidate ARIMA orders, selects the one with the lowest AIC,
     * and returns that model's forecast.
     *
     * Candidates: (0,1,0), (1,1,0), (0,1,1), (1,1,1), (2,1,1)
     */
    private function autoARIMA(array $series, int $steps): array
    {
        $candidates = [
            [0, 1, 0],
            [1, 1, 0],
            [0, 1, 1],
            [1, 1, 1],
            [2, 1, 1],
            [1, 1, 2],
            [2, 1, 2],
        ];

        $bestOrder = [1, 1, 1];
        $bestAIC   = PHP_FLOAT_MAX;

        foreach ($candidates as [$p, $d, $q]) {
            $minObs = $p + $d + max($q, 1) + 4;
            if (count($series) < $minObs) continue;

            $aic = $this->computeAIC($series, $p, $d, $q);
            if ($aic < $bestAIC) {
                $bestAIC   = $aic;
                $bestOrder = [$p, $d, $q];
            }
        }

        [$p, $d, $q] = $bestOrder;
        $result = $this->runARIMA($series, $p, $d, $q, $steps);
        $result['arima_order_selected'] = $bestOrder;
        return $result;
    }

    /**
     * Akaike Information Criterion for ARIMA(p,d,q).
     * AIC ≈ n · ln(RSS/n) + 2·k   where k = p + q + 1.
     */
    private function computeAIC(array $series, int $p, int $d, int $q): float
    {
        $minReq = $p + $d + max($q, 1) + 2;
        if (count($series) < $minReq) return PHP_FLOAT_MAX;

        $diffed = $series;
        for ($i = 0; $i < $d; $i++) {
            $diffed = $this->difference($diffed);
        }

        $mean     = array_sum($diffed) / count($diffed);
        $centered = array_map(fn($x) => $x - $mean, $diffed);
        $phi      = $this->yuleWalker($centered, $p);
        $theta    = $this->estimateMAByCSS($centered, $phi, $p, $q);
        $eps      = $this->computeARMAResiduals($centered, $phi, $theta, $p, $q);

        $start    = max($p, $q);
        $validEps = array_slice($eps, $start);
        $n        = count($validEps);

        if ($n < 2) return PHP_FLOAT_MAX;

        $rss = array_sum(array_map(fn($r) => $r ** 2, $validEps));
        if ($rss <= 0.0) return PHP_FLOAT_MAX;

        $k = max(1, $p + $q + 1);
        return $n * log($rss / $n) + 2.0 * $k;
    }

    // ─── Croston's Method ─────────────────────────────────────────────────

    /**
     * Croston's method for intermittent demand.
     * Smooths demand size (z) and inter-demand interval (p) separately,
     * yielding a per-period forecast of z / p.
     * Alpha is optimised per-product via grid search.
     */
    private function crostonMethod(array $series, int $steps): array
    {
        $nonZeroVals = array_values(array_filter($series, fn($v) => $v > 0));

        if (count($nonZeroVals) < 2) {
            return $this->exponentialSmoothing($series, $steps, 0.1);
        }

        $alpha = $this->optimizeCrostonAlpha($series);

        $n    = count($series);
        $z    = $nonZeroVals[0];
        $p    = 1.0;
        $last = -1;

        for ($t = 0; $t < $n; $t++) {
            if ($series[$t] > 0) {
                $interval = $last < 0 ? 1 : $t - $last;
                $z        = $alpha * $series[$t] + (1.0 - $alpha) * $z;
                $p        = $alpha * $interval   + (1.0 - $alpha) * $p;
                $last     = $t;
            }
        }

        $level    = max(0.0, $z / max(1.0, $p));
        $se       = $this->sampleStdDev($nonZeroVals);
        $forecast = array_fill(0, $steps, $level);
        $lower    = array_map(fn($f) => max(0.0, $f - 1.96 * $se), $forecast);
        $upper    = array_map(fn($f) => $f + 1.96 * $se, $forecast);

        return [
            'forecast' => $forecast,
            'lower'    => $lower,
            'upper'    => $upper,
            'params'   => ['phi' => [], 'theta' => [], 'mean' => $level, 'd' => 0, 'alpha' => $alpha],
            'fallback' => false,
        ];
    }

    private function optimizeCrostonAlpha(array $series): float
    {
        $best    = 0.15;
        $bestMSE = PHP_FLOAT_MAX;

        for ($ai = 5; $ai <= 50; $ai += 2) {
            $mse = $this->crostonInSampleMSE($series, $ai / 100.0);
            if ($mse < $bestMSE) {
                $bestMSE = $mse;
                $best    = $ai / 100.0;
            }
        }

        return $best;
    }

    private function crostonInSampleMSE(array $series, float $alpha): float
    {
        $nonZero = array_filter($series, fn($v) => $v > 0);
        if (count($nonZero) < 2) return PHP_FLOAT_MAX;

        $z      = array_values($nonZero)[0];
        $p      = 1.0;
        $last   = -1;
        $errors = [];

        foreach ($series as $t => $y) {
            if ($y > 0) {
                $forecast = $z / max(1.0, $p);
                $errors[] = ($y - $forecast) ** 2;
                $interval = $last < 0 ? 1 : $t - $last;
                $z        = $alpha * $y        + (1.0 - $alpha) * $z;
                $p        = $alpha * $interval + (1.0 - $alpha) * $p;
                $last     = $t;
            }
        }

        return empty($errors) ? PHP_FLOAT_MAX : array_sum($errors) / count($errors);
    }

    // ─── Optimised SES ────────────────────────────────────────────────────

    /**
     * Simple Exponential Smoothing with alpha found by minimising
     * one-step-ahead in-sample MSE (grid search 0.05 – 0.95).
     */
    private function optimizedSES(array $series, int $steps): array
    {
        $best    = 0.3;
        $bestMSE = PHP_FLOAT_MAX;

        for ($ai = 5; $ai <= 95; $ai += 5) {
            $mse = $this->sesInSampleMSE($series, $ai / 100.0);
            if ($mse < $bestMSE) {
                $bestMSE = $mse;
                $best    = $ai / 100.0;
            }
        }

        return $this->exponentialSmoothing($series, $steps, $best);
    }

    private function sesInSampleMSE(array $series, float $alpha): float
    {
        $n = count($series);
        if ($n < 2) return PHP_FLOAT_MAX;

        $level  = $series[0];
        $errors = [];

        for ($t = 1; $t < $n; $t++) {
            $errors[] = ($series[$t] - $level) ** 2;
            $level    = $alpha * $series[$t] + (1.0 - $alpha) * $level;
        }

        return array_sum($errors) / count($errors);
    }

    // ─── Holt-Winters Triple Exponential Smoothing ───────────────────────

    /**
     * Detects annual seasonality using the normalised ACF at lag 52
     * computed on the **first-differenced** series so that a plain
     * upward trend cannot be mistaken for seasonality.
     * Threshold: normalised ACF > 0.12.
     */
    private function hasSeasonal(array $series, int $period = 52): bool
    {
        if (count($series) < 2 * $period + 1) return false;
        $diffed = $this->difference($series);   // removes linear trend
        $var    = $this->autocovariance($diffed, 0);
        if ($var < 1e-10) return false;
        return abs($this->autocovariance($diffed, $period)) / $var > 0.12;
    }

    /**
     * Holt-Winters Additive (level + trend + seasonal).
     * Seasonal period m = 52 weeks (annual cycle).
     * Alpha and beta are fixed at literature defaults;
     * gamma (seasonal smoothing) is tuned via grid search.
     */
    /**
     * Holt-Winters with full (α, β, γ) grid search and automatic selection
     * between the additive and multiplicative seasonal variants.
     *
     * Additive:        F = (L + b·h) + S_h          — absolute seasonal swings
     * Multiplicative:  F = (L + b·h) × S_h          — proportional seasonal swings
     *
     * Multiplicative is tried when ≥ 80 % of the series is non-zero (avoids
     * division-by-zero); zeros are floored to 0.01 inside the mult. methods.
     * The variant with the lower in-sample MSE wins.
     */
    private function holtWinters(array $series, int $steps, int $m = 52): array
    {
        $n = count($series);
        if ($n < 2 * $m) return $this->optimizedSES($series, $steps);

        $alphas = [0.05, 0.10, 0.15, 0.25];
        $betas  = [0.02, 0.05, 0.10];
        $gammas = [0.05, 0.10, 0.15, 0.20, 0.30, 0.40, 0.50];

        $nonZeroRatio = count(array_filter($series, fn($v) => $v > 0)) / $n;
        $variants     = $nonZeroRatio >= 0.80 ? ['additive', 'multiplicative'] : ['additive'];

        $best = ['alpha' => 0.15, 'beta' => 0.05, 'gamma' => 0.10,
                 'variant' => 'additive', 'mse' => PHP_FLOAT_MAX];

        foreach ($variants as $variant) {
            foreach ($alphas as $alpha) {
                foreach ($betas as $beta) {
                    foreach ($gammas as $gamma) {
                        $mse = $variant === 'additive'
                            ? $this->hwInSampleMSE($series, $alpha, $beta, $gamma, $m)
                            : $this->hwMultInSampleMSE($series, $alpha, $beta, $gamma, $m);
                        if ($mse < $best['mse']) {
                            $best = ['alpha' => $alpha, 'beta' => $beta,
                                     'gamma' => $gamma, 'variant' => $variant, 'mse' => $mse];
                        }
                    }
                }
            }
        }

        return $best['variant'] === 'additive'
            ? $this->runHoltWinters($series, $steps, $best['alpha'], $best['beta'], $best['gamma'], $m)
            : $this->runHoltWintersMultiplicative($series, $steps, $best['alpha'], $best['beta'], $best['gamma'], $m);
    }

    private function hwInSampleMSE(array $series, float $alpha, float $beta, float $gamma, int $m): float
    {
        $n = count($series);
        if ($n < $m + 1) return PHP_FLOAT_MAX;

        $initL = array_sum(array_slice($series, 0, $m)) / $m;
        $initB = 0.0;
        if ($n >= 2 * $m) {
            $l2    = array_sum(array_slice($series, $m, $m)) / $m;
            $initB = ($l2 - $initL) / $m;
        }

        $s = [];
        for ($i = 0; $i < $m; $i++) $s[$i] = $series[$i] - $initL;
        $sAvg = array_sum($s) / $m;
        for ($i = 0; $i < $m; $i++) $s[$i] -= $sAvg;

        $l = $initL; $b = $initB; $sse = 0.0;

        for ($t = 0; $t < $n; $t++) {
            $si    = $t % $m;
            $yhat  = $l + $b + $s[$si];
            $sse  += ($series[$t] - $yhat) ** 2;
            $prevL = $l;
            $l     = $alpha * ($series[$t] - $s[$si]) + (1.0 - $alpha) * ($l + $b);
            $b     = $beta  * ($l - $prevL)            + (1.0 - $beta)  * $b;
            $s[$si] = $gamma * ($series[$t] - $l)      + (1.0 - $gamma) * $s[$si];
        }

        return $sse / $n;
    }

    private function runHoltWinters(array $series, int $steps, float $alpha, float $beta, float $gamma, int $m): array
    {
        $n = count($series);

        $initL = array_sum(array_slice($series, 0, $m)) / $m;
        $initB = 0.0;
        if ($n >= 2 * $m) {
            $l2    = array_sum(array_slice($series, $m, $m)) / $m;
            $initB = ($l2 - $initL) / $m;
        }

        $s = [];
        for ($i = 0; $i < $m; $i++) $s[$i] = $series[$i] - $initL;
        $sAvg = array_sum($s) / $m;
        for ($i = 0; $i < $m; $i++) $s[$i] -= $sAvg;

        $l = $initL; $b = $initB; $residuals = [];

        for ($t = 0; $t < $n; $t++) {
            $si          = $t % $m;
            $yhat        = $l + $b + $s[$si];
            $residuals[] = $series[$t] - $yhat;
            $prevL       = $l;
            $l           = $alpha * ($series[$t] - $s[$si]) + (1.0 - $alpha) * ($l + $b);
            $b           = $beta  * ($l - $prevL)            + (1.0 - $beta)  * $b;
            $s[$si]      = $gamma * ($series[$t] - $l)       + (1.0 - $gamma) * $s[$si];
        }

        $forecast = [];
        for ($h = 1; $h <= $steps; $h++) {
            $si         = ($n + $h - 1) % $m;
            $forecast[] = max(0.0, $l + $b * $h + $s[$si]);
        }

        $resSd = $this->sampleStdDev($residuals);
        $lower = $upper = [];
        foreach ($forecast as $h => $f) {
            $margin  = 1.96 * $resSd * sqrt($h + 1);
            $lower[] = max(0.0, $f - $margin);
            $upper[] = $f + $margin;
        }

        return [
            'forecast' => $forecast,
            'lower'    => $lower,
            'upper'    => $upper,
            'params'   => ['phi' => [], 'theta' => [], 'mean' => round($l, 4), 'd' => 0,
                           'alpha' => $alpha, 'beta' => $beta, 'gamma' => $gamma],
            'fallback' => false,
        ];
    }

    private function hwMultInSampleMSE(array $series, float $alpha, float $beta, float $gamma, int $m): float
    {
        $n = count($series);
        if ($n < $m + 1) return PHP_FLOAT_MAX;

        $series = array_map(fn($v) => max(0.01, $v), $series);

        $initL = array_sum(array_slice($series, 0, $m)) / $m;
        if ($initL <= 0) return PHP_FLOAT_MAX;

        $initB = 0.0;
        if ($n >= 2 * $m) {
            $l2    = array_sum(array_slice($series, $m, $m)) / $m;
            $initB = ($l2 - $initL) / $m;
        }

        $s = [];
        for ($i = 0; $i < $m; $i++) $s[$i] = $series[$i] / $initL;
        $sAvg = array_sum($s) / $m;
        if ($sAvg > 0) for ($i = 0; $i < $m; $i++) $s[$i] /= $sAvg;

        $l = $initL; $b = $initB; $sse = 0.0;

        for ($t = 0; $t < $n; $t++) {
            $si    = $t % $m;
            $sVal  = max(0.01, $s[$si]);
            $yhat  = ($l + $b) * $sVal;
            $sse  += ($series[$t] - $yhat) ** 2;
            $prevL = $l;
            $l     = $alpha * ($series[$t] / $sVal)          + (1.0 - $alpha) * ($l + $b);
            $b     = $beta  * ($l - $prevL)                  + (1.0 - $beta)  * $b;
            $s[$si] = $gamma * ($series[$t] / max($l, 0.01)) + (1.0 - $gamma) * $sVal;
        }

        return $sse / $n;
    }

    private function runHoltWintersMultiplicative(array $series, int $steps, float $alpha, float $beta, float $gamma, int $m): array
    {
        $n      = count($series);
        $series = array_map(fn($v) => max(0.01, $v), $series);

        $initL = array_sum(array_slice($series, 0, $m)) / $m;
        $initB = 0.0;
        if ($n >= 2 * $m) {
            $l2    = array_sum(array_slice($series, $m, $m)) / $m;
            $initB = ($l2 - $initL) / $m;
        }

        $s = [];
        for ($i = 0; $i < $m; $i++) $s[$i] = $series[$i] / max($initL, 0.01);
        $sAvg = array_sum($s) / $m;
        if ($sAvg > 0) for ($i = 0; $i < $m; $i++) $s[$i] /= $sAvg;

        $l = $initL; $b = $initB; $residuals = [];

        for ($t = 0; $t < $n; $t++) {
            $si          = $t % $m;
            $sVal        = max(0.01, $s[$si]);
            $yhat        = ($l + $b) * $sVal;
            $residuals[] = $series[$t] - $yhat;
            $prevL       = $l;
            $l           = $alpha * ($series[$t] / $sVal)          + (1.0 - $alpha) * ($l + $b);
            $b           = $beta  * ($l - $prevL)                  + (1.0 - $beta)  * $b;
            $s[$si]      = $gamma * ($series[$t] / max($l, 0.01))  + (1.0 - $gamma) * $sVal;
        }

        $forecast = [];
        for ($h = 1; $h <= $steps; $h++) {
            $si         = ($n + $h - 1) % $m;
            $forecast[] = max(0.0, ($l + $b * $h) * max(0.01, $s[$si]));
        }

        $resSd = $this->sampleStdDev($residuals);
        $lower = $upper = [];
        foreach ($forecast as $h => $f) {
            $margin  = 1.96 * $resSd * sqrt($h + 1);
            $lower[] = max(0.0, $f - $margin);
            $upper[] = $f + $margin;
        }

        return [
            'forecast' => $forecast,
            'lower'    => $lower,
            'upper'    => $upper,
            'params'   => ['phi' => [], 'theta' => [], 'mean' => round($l, 4), 'd' => 0,
                           'alpha' => $alpha, 'beta' => $beta, 'gamma' => $gamma,
                           'variant' => 'multiplicative'],
            'fallback' => false,
        ];
    }

    // ─── WMAPE ────────────────────────────────────────────────────────────

    /**
     * Weighted Mean Absolute Percentage Error via hold-out validation.
     * Trains on first (n − 4) weeks, forecasts last 4.
     *
     * WMAPE = Σ|actual − forecast| / Σactual × 100
     *
     * Unlike MAPE, the denominator is the total actual demand over the
     * holdout window, so zero-demand weeks are handled naturally and
     * high-volume periods are weighted more — a better fit for
     * slow/intermittent optical inventory.
     */
    private function computeWMAPE(array $weeklySeries, string $method): float
    {
        // WMAPE only applies to fast-moving items (HW / ARIMA).
        if (!in_array($method, ['holt_winters', 'auto_arima'])) return -1.0;

        // Aggregate weekly → monthly (4 weeks = 1 month).
        // Monthly totals smooth out individual zero-weeks, reducing noise in the error metric.
        $monthly = $this->toMonthlySeries($weeklySeries);
        $n       = count($monthly);
        $holdOut = 12; // 1-year hold-out in months

        // Need ≥ 2 full annual cycles for training (24 months) plus the 12-month hold-out
        if ($n < $holdOut + 24) return -1.0;

        $train     = array_slice($monthly, 0, $n - $holdOut);
        $actuals   = array_slice($monthly, $n - $holdOut);

        // For HW use monthly seasonal period (12); ARIMA is non-seasonal so no change needed
        $result    = $method === 'holt_winters'
            ? $this->holtWinters($train, $holdOut, 12)
            : $this->autoARIMA($train, $holdOut);
        $forecasts = $result['forecast'];

        $totalAbsError = 0.0;
        $totalActual   = 0.0;
        for ($i = 0; $i < $holdOut; $i++) {
            $totalAbsError += abs($actuals[$i] - $forecasts[$i]);
            $totalActual   += $actuals[$i];
        }

        if ($totalActual <= 0.0) return -1.0;

        return round(min(($totalAbsError / $totalActual) * 100.0, 999.9), 2);
    }

    /**
     * Collapse a weekly series into monthly totals (4 weeks per month).
     * Trailing weeks that don't fill a full month are discarded.
     */
    private function toMonthlySeries(array $weeklySeries): array
    {
        $monthly = [];
        $n       = count($weeklySeries);
        for ($i = 0; $i + 4 <= $n; $i += 4) {
            $monthly[] = array_sum(array_slice($weeklySeries, $i, 4));
        }
        return $monthly;
    }

    // ─── ARIMA(p, d, q) ───────────────────────────────────────────────────

    /**
     * Fits ARIMA(p,d,q) and returns $steps-step-ahead forecasts.
     * Falls back to SES when the series is too short.
     */
    private function runARIMA(array $series, int $p, int $d, int $q, int $steps): array
    {
        $minRequired = $p + $d + max($q, 1) + 2;
        if (count($series) < $minRequired) {
            return $this->exponentialSmoothing($series, $steps);
        }

        $origLast = (float) end($series);
        $diffed   = $series;
        for ($i = 0; $i < $d; $i++) {
            $diffed = $this->difference($diffed);
        }

        $mean     = array_sum($diffed) / count($diffed);
        $centered = array_map(fn($x) => $x - $mean, $diffed);

        $phi       = $this->yuleWalker($centered, $p);
        $residuals = $this->computeARResiduals($centered, $phi, $p);
        $theta     = $this->estimateMAByCSS($centered, $phi, $p, $q);

        $forecastDiff = $this->forecastSteps($diffed, $residuals, $phi, $theta, $mean, $steps);
        $forecast     = $this->undifference($forecastDiff, $origLast, $d);
        $forecast     = array_map(fn($v) => max(0.0, $v), $forecast);

        $resSd = $this->sampleStdDev($residuals);
        $lower = [];
        $upper = [];
        foreach ($forecast as $h => $f) {
            $margin  = 1.96 * $resSd * sqrt($h + 1);
            $lower[] = max(0.0, $f - $margin);
            $upper[] = $f + $margin;
        }

        return [
            'forecast' => $forecast,
            'lower'    => $lower,
            'upper'    => $upper,
            'params'   => ['phi' => $phi, 'theta' => $theta, 'mean' => $mean, 'd' => $d],
            'fallback' => false,
        ];
    }

    private function difference(array $series): array
    {
        $out = [];
        for ($i = 1, $n = count($series); $i < $n; $i++) {
            $out[] = $series[$i] - $series[$i - 1];
        }
        return $out;
    }

    private function undifference(array $diffForecast, float $lastOriginal, int $d): array
    {
        if ($d === 0) return $diffForecast;
        $result = [];
        $prev   = $lastOriginal;
        foreach ($diffForecast as $diff) {
            $prev     += $diff;
            $result[]  = $prev;
        }
        return $result;
    }

    private function yuleWalker(array $series, int $p): array
    {
        if ($p === 0) return [];

        $gamma = [];
        for ($k = 0; $k <= $p; $k++) {
            $gamma[$k] = $this->autocovariance($series, $k);
        }

        if (abs($gamma[0]) < 1e-10) return array_fill(0, $p, 0.0);
        if ($p === 1)                return [$gamma[1] / $gamma[0]];

        return $this->durbinLevinson($gamma, $p);
    }

    private function durbinLevinson(array $gamma, int $p): array
    {
        $phi       = [];
        $phi[1][1] = $gamma[1] / $gamma[0];
        $v         = [$gamma[0], $gamma[0] * (1.0 - $phi[1][1] ** 2)];

        for ($m = 2; $m <= $p; $m++) {
            $sum = 0.0;
            for ($k = 1; $k < $m; $k++) {
                $sum += $phi[$m - 1][$k] * $gamma[$m - $k];
            }
            $denom       = $v[$m - 1];
            $phi[$m][$m] = abs($denom) < 1e-10 ? 0.0 : ($gamma[$m] - $sum) / $denom;
            for ($k = 1; $k < $m; $k++) {
                $phi[$m][$k] = $phi[$m - 1][$k] - $phi[$m][$m] * $phi[$m - 1][$m - $k];
            }
            $v[$m] = $v[$m - 1] * (1.0 - $phi[$m][$m] ** 2);
        }

        $result = [];
        for ($k = 1; $k <= $p; $k++) {
            $result[] = $phi[$p][$k] ?? 0.0;
        }
        return $result;
    }

    private function autocovariance(array $series, int $lag): float
    {
        $n = count($series);
        if ($n <= $lag) return 0.0;
        $mean = array_sum($series) / $n;
        $sum  = 0.0;
        for ($t = $lag; $t < $n; $t++) {
            $sum += ($series[$t] - $mean) * ($series[$t - $lag] - $mean);
        }
        return $sum / $n;
    }

    private function computeARResiduals(array $centered, array $phi, int $p): array
    {
        $n     = count($centered);
        $resid = array_fill(0, $p, 0.0);
        for ($t = $p; $t < $n; $t++) {
            $pred = 0.0;
            for ($i = 0; $i < $p; $i++) {
                $pred += $phi[$i] * $centered[$t - $i - 1];
            }
            $resid[] = $centered[$t] - $pred;
        }
        return $resid;
    }

    /** Full ARMA residuals (AR + MA terms) — used for AIC computation. */
    private function computeARMAResiduals(array $centered, array $phi, array $theta, int $p, int $q): array
    {
        $n   = count($centered);
        $eps = array_fill(0, $n, 0.0);

        for ($t = max($p, $q); $t < $n; $t++) {
            $arPart = 0.0;
            for ($i = 0; $i < $p; $i++) {
                if ($t - $i - 1 >= 0) {
                    $arPart += $phi[$i] * $centered[$t - $i - 1];
                }
            }
            $maPart = 0.0;
            for ($j = 0; $j < $q; $j++) {
                if ($t - $j - 1 >= 0) {
                    $maPart += $theta[$j] * $eps[$t - $j - 1];
                }
            }
            $eps[$t] = $centered[$t] - $arPart - $maPart;
        }

        return $eps;
    }

    private function estimateMAByCSS(array $centered, array $phi, int $p, int $q): array
    {
        if ($q === 0) return [];

        $n         = count($centered);
        $bestTheta = array_fill(0, $q, 0.0);
        $bestCSS   = PHP_FLOAT_MAX;
        $start     = max($p, $q);

        for ($ti = -99; $ti <= 99; $ti++) {
            $t1  = $ti / 100.0;
            $css = 0.0;
            $eps = array_fill(0, $n, 0.0);

            for ($t = $start; $t < $n; $t++) {
                $arPart = 0.0;
                for ($i = 0; $i < $p; $i++) {
                    $arPart += $phi[$i] * $centered[$t - $i - 1];
                }
                $maPart  = $t1 * $eps[$t - 1];
                $eps[$t] = $centered[$t] - $arPart - $maPart;
                $css    += $eps[$t] ** 2;
            }

            if ($css < $bestCSS) {
                $bestCSS   = $css;
                $bestTheta = [$t1];
            }
        }

        return $bestTheta;
    }

    private function forecastSteps(
        array $diffed,
        array $residuals,
        array $phi,
        array $theta,
        float $mean,
        int   $steps
    ): array {
        $p   = count($phi);
        $q   = count($theta);
        $ext = $diffed;
        $eps = $residuals;
        $out = [];

        for ($h = 0; $h < $steps; $h++) {
            $arPart = 0.0;
            for ($i = 0; $i < $p; $i++) {
                $idx     = count($ext) - $i - 1;
                $arPart += $phi[$i] * ($ext[$idx] - $mean);
            }
            $maPart = 0.0;
            if ($h === 0 && $q > 0) {
                $idx    = count($eps) - 1;
                $maPart = $theta[0] * ($idx >= 0 ? $eps[$idx] : 0.0);
            }
            $val   = $mean + $arPart + $maPart;
            $out[] = $val;
            $ext[] = $val;
            $eps[] = 0.0;
        }

        return $out;
    }

    /**
     * Simple Exponential Smoothing fallback used when a series is too
     * short for ARIMA. Alpha defaults to 0.3 unless overridden.
     */
    private function exponentialSmoothing(array $series, int $steps, float $alpha = 0.3): array
    {
        $level = count($series) > 0 ? (float) $series[0] : 0.0;
        foreach ($series as $y) {
            $level = $alpha * $y + (1.0 - $alpha) * $level;
        }
        $level = max(0.0, $level);

        $se       = $this->sampleStdDev($series);
        $forecast = array_fill(0, $steps, $level);
        $lower    = array_map(fn($f) => max(0.0, $f - 1.96 * $se), $forecast);
        $upper    = array_map(fn($f) => $f + 1.96 * $se, $forecast);

        return [
            'forecast' => $forecast,
            'lower'    => $lower,
            'upper'    => $upper,
            'params'   => ['phi' => [], 'theta' => [], 'mean' => $level, 'd' => 0, 'alpha' => $alpha],
            'fallback' => true,
        ];
    }

    // ─── Time Series Builders ──────────────────────────────────────────────

    private function buildWeeklySeriesFromAgg(\Illuminate\Support\Collection $aggRows, int $weeks): array
    {
        $since   = now()->subWeeks($weeks)->startOfWeek();
        $weekMap = [];

        foreach ($aggRows as $row) {
            $weekMap[$row->week_start] = ($weekMap[$row->week_start] ?? 0) + $row->qty;
        }

        $series  = [];
        $current = $since->copy()->startOfWeek();
        for ($i = 0; $i < $weeks; $i++) {
            $series[] = (float) ($weekMap[$current->toDateString()] ?? 0);
            $current->addWeek();
        }

        return $series;
    }

    private function buildDailySeriesFromAgg(\Illuminate\Support\Collection $aggRows, int $days): array
    {
        $since  = now()->subDays($days - 1)->startOfDay();
        $dayMap = [];

        foreach ($aggRows as $row) {
            $dayMap[$row->sale_date] = ($dayMap[$row->sale_date] ?? 0) + $row->qty;
        }

        $series  = [];
        $current = $since->copy();
        for ($i = 0; $i < $days; $i++) {
            $series[] = (float) ($dayMap[$current->toDateString()] ?? 0);
            $current->addDay();
        }

        return $series;
    }

    // ─── FSN Classification ────────────────────────────────────────────────

    /**
     * Activity-based FSN classification over the full 52-week window.
     *
     * Rules (applied in order):
     *   Non-moving : no sale in ≥ 6 months  OR  active in < 10% of weeks
     *   Fast       : active in ≥ 50% of weeks  OR  avg demand ≥ 1 unit/day
     *   Slow       : everything else (10–50% activity)
     */
    private function classifyFSN(\Illuminate\Support\Collection $weeklyAggRows, float $avgDailyDemand, ?string $lastSaleAt): array
    {
        $weeksToCheck = $this->weekWindow;
        $weekSet      = [];

        foreach ($weeklyAggRows as $row) {
            $weekSet[$row->week_start] = true;
        }

        $activeWeeks   = count($weekSet);
        $activityRatio = $activeWeeks / $weeksToCheck;
        $lastSaleDate  = $lastSaleAt ? Carbon::parse($lastSaleAt)->toDateString() : null;
        $monthsNoSale  = $lastSaleAt
            ? round(Carbon::parse($lastSaleAt)->diffInDays(now()) / 30.44, 1)
            : 999.0;

        $classification = match (true) {
            $monthsNoSale >= 6.0 || $activityRatio < 0.10 => 'non_moving',
            $activityRatio >= 0.50 || $avgDailyDemand >= 1.0 => 'fast',
            default => 'slow',
        };

        return [
            'classification' => $classification,
            'active_weeks'   => $activeWeeks,
            'total_weeks'    => $weeksToCheck,
            'activity_ratio' => $activityRatio,
            'last_sale_date' => $lastSaleDate,
            'months_no_sale' => $monthsNoSale,
        ];
    }

    // ─── Statistics ───────────────────────────────────────────────────────

    private function sampleStdDev(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0.0;
        $mean     = array_sum($data) / $n;
        $variance = 0.0;
        foreach ($data as $v) {
            $variance += ($v - $mean) ** 2;
        }
        return sqrt($variance / ($n - 1));
    }
}
