<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\AnalyticsLog;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    private float $orderCost   = 500.0;
    private float $holdingRate = 0.20;
    private float $serviceZ    = 1.645;
    private int   $leadTime    = 7;
    private int   $weekWindow  = 208;
    private int   $dayWindow   = 1460;

    // ─── Public Endpoints ─────────────────────────────────────────────────

    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $products = Product::with('category')->get();
        $results  = [];

        $weeklySince = now()->subWeeks($this->weekWindow)->startOfWeek();
        $dailySince  = now()->subDays($this->dayWindow - 1)->startOfDay();

        $weeklyRows = SaleItem::where('created_at', '>=', $weeklySince)
            ->get(['product_id', 'created_at', 'quantity'])
            ->groupBy('product_id');

        $dailyRows = SaleItem::where('created_at', '>=', $dailySince)
            ->get(['product_id', 'created_at', 'quantity'])
            ->groupBy('product_id');

        foreach ($products as $product) {
            $weeklySeries = $this->buildWeeklyTimeSeriesFromRows(
                $weeklyRows->get($product->id, collect()), $this->weekWindow
            );
            $dailySeries = $this->buildDailyDemandSeriesFromRows(
                $dailyRows->get($product->id, collect()), $this->dayWindow
            );

            $n        = count($dailySeries);
            $avgDaily = $n > 0 ? array_sum($dailySeries) / $n : 0.0;
            $stdDaily = $this->sampleStdDev($dailySeries);

            // ── Method selection based on demand sparsity ───────────────────
            $method         = $this->selectMethod($weeklySeries);
            $forecastResult = $this->forecast($weeklySeries, $method, 4);

            // Convert 4-week forecast to 30-day equivalent
            $predictedMonthly = array_sum($forecastResult['forecast']) * (30.0 / 7.0);

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
            $fsn    = $this->classifyFSN($weeklyRows->get($product->id, collect()), $avgDaily);
            $params = $forecastResult['params'];

            $log = AnalyticsLog::updateOrCreate(
                [
                    'product_id'  => $product->id,
                    'method'      => 'arima',
                    'computed_at' => now()->toDateString(),
                ],
                [
                    'result_data' => [
                        // Method metadata
                        'method_used'     => $method,
                        'arima_order'     => $forecastResult['arima_order_selected'] ?? null,
                        'ar_param'        => isset($params['phi'][0])   ? round($params['phi'][0],   4) : null,
                        'ma_param'        => isset($params['theta'][0]) ? round($params['theta'][0], 4) : null,
                        'diff_mean'       => round($params['mean'] ?? 0, 4),
                        'used_fallback'   => $forecastResult['fallback'],
                        // Forecast outputs
                        'forecast_weekly' => array_map(fn($v) => round($v, 2), $forecastResult['forecast']),
                        'conf_lower_30d'  => round(max(0, array_sum($forecastResult['lower']) * (30.0 / 7.0)), 2),
                        'conf_upper_30d'  => round(array_sum($forecastResult['upper']) * (30.0 / 7.0), 2),
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
                    ],
                    'predicted_demand'   => round($predictedMonthly, 2),
                    'eoq_value'          => round($eoq, 2),
                    'rop_value'          => round($rop, 2),
                    'fsn_classification' => $fsn['classification'],
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

        return response()->json([
            'fsn_summary' => compact('fast', 'slow', 'nonMoving'),
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

        if ($ratio >= 0.40) return 'auto_arima';
        if ($ratio >= 0.10) return 'croston';
        return 'ses';
    }

    /** Dispatch to the right algorithm. */
    private function forecast(array $series, string $method, int $steps): array
    {
        return match ($method) {
            'croston'    => $this->crostonMethod($series, $steps),
            'ses'        => $this->optimizedSES($series, $steps),
            'auto_arima' => $this->autoARIMA($series, $steps),
            default      => $this->runARIMA($series, 1, 1, 1, $steps),
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

        $level    = max(0.0, $z / max(0.5, $p));
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

        for ($ai = 5; $ai <= 50; $ai += 5) {
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
                $forecast = $z / max(0.5, $p);
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
    private function computeWMAPE(array $series, string $method): float
    {
        $n       = count($series);
        $holdOut = 4;

        if ($n < $holdOut + 6) return -1.0;

        $train   = array_slice($series, 0, $n - $holdOut);
        $actuals = array_slice($series, $n - $holdOut);

        $result    = $this->forecast($train, $method, $holdOut);
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

    private function buildWeeklyTimeSeriesFromRows(\Illuminate\Support\Collection $rows, int $weeks): array
    {
        $since   = now()->subWeeks($weeks)->startOfWeek();
        $weekMap = [];

        foreach ($rows as $row) {
            $key = Carbon::parse($row->created_at)->startOfWeek()->toDateString();
            $weekMap[$key] = ($weekMap[$key] ?? 0) + $row->quantity;
        }

        $series  = [];
        $current = $since->copy()->startOfWeek();
        for ($i = 0; $i < $weeks; $i++) {
            $series[] = (float) ($weekMap[$current->toDateString()] ?? 0);
            $current->addWeek();
        }

        return $series;
    }

    private function buildDailyDemandSeriesFromRows(\Illuminate\Support\Collection $rows, int $days): array
    {
        $since  = now()->subDays($days - 1)->startOfDay();
        $dayMap = [];

        foreach ($rows as $row) {
            $key = Carbon::parse($row->created_at)->toDateString();
            $dayMap[$key] = ($dayMap[$key] ?? 0) + $row->quantity;
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
    private function classifyFSN(\Illuminate\Support\Collection $weeklyRows, float $avgDailyDemand): array
    {
        $weeksToCheck = $this->weekWindow;
        $weekSet      = [];
        $lastSaleTs   = null;

        foreach ($weeklyRows as $row) {
            $weekStart = Carbon::parse($row->created_at)->startOfWeek()->toDateString();
            $weekSet[$weekStart] = true;
            if ($lastSaleTs === null || $row->created_at > $lastSaleTs) {
                $lastSaleTs = $row->created_at;
            }
        }

        $activeWeeks   = count($weekSet);
        $activityRatio = $activeWeeks / $weeksToCheck;
        $lastSaleDate  = $lastSaleTs ? Carbon::parse($lastSaleTs)->toDateString() : null;
        $monthsNoSale  = $lastSaleTs
            ? round(Carbon::parse($lastSaleTs)->diffInDays(now()) / 30.44, 1)
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
