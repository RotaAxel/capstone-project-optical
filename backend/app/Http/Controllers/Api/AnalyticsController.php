<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\AnalyticsLog;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    private float $orderCost  = 500.0;   // ₱500 fixed ordering cost per order
    private float $holdingRate = 0.20;   // 20% of unit cost per year
    private float $serviceZ   = 1.645;  // 95% service level Z-score
    private int   $leadTime   = 7;       // supplier lead time in days

    // ─── Public Endpoints ─────────────────────────────────────────────────

    public function run()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $products = Product::with('category')->get();
        $results  = [];

        // ── Bulk-load all sale items in 2 queries (avoids N+1) ────────────
        $weeklySince = now()->subWeeks(24)->startOfWeek();
        $dailySince  = now()->subDays(89)->startOfDay();

        $weeklyRows = SaleItem::where('created_at', '>=', $weeklySince)
            ->get(['product_id', 'created_at', 'quantity'])
            ->groupBy('product_id');

        $dailyRows = SaleItem::where('created_at', '>=', $dailySince)
            ->get(['product_id', 'created_at', 'quantity'])
            ->groupBy('product_id');

        foreach ($products as $product) {
            /** @var \App\Models\Product $product */
            $weeklySeries = $this->buildWeeklyTimeSeriesFromRows($weeklyRows->get($product->id, collect()), 24);
            $dailySeries  = $this->buildDailyDemandSeriesFromRows($dailyRows->get($product->id, collect()), 90);

            $n            = count($dailySeries);
            $avgDaily     = $n > 0 ? array_sum($dailySeries) / $n : 0.0;
            $stdDaily     = $this->sampleStdDev($dailySeries);

            // ── ARIMA(1,1,1) ──────────────────────────────────────────────
            $arima            = $this->runARIMA($weeklySeries, 1, 1, 1, 4);
            // Convert 4-week forecast to 30-day equivalent
            $predictedMonthly = array_sum($arima['forecast']) * (30.0 / 7.0);

            // ── MAPE walk-forward validation ──────────────────────────────
            $mape = $this->computeMAPE($weeklySeries, 1, 1, 1);

            // ── EOQ: √(2·D·S / H) ────────────────────────────────────────
            $annualDemand = $avgDaily * 365.0;
            $holdingCost  = max(0.01, $product->cost_price * $this->holdingRate);
            $eoq = $annualDemand > 0
                ? sqrt((2.0 * $annualDemand * $this->orderCost) / $holdingCost)
                : 0.0;

            // ── ROP: d̄·L + Z·σ·√L (with safety stock) ──────────────────
            $safetyStock = $this->serviceZ * $stdDaily * sqrt($this->leadTime);
            $rop         = ($avgDaily * $this->leadTime) + $safetyStock;

            // ── FSN: activity-based classification ───────────────────────
            $fsn = $this->classifyFSN($weeklyRows->get($product->id, collect()), $avgDaily);

            $log = AnalyticsLog::updateOrCreate(
                [
                    'product_id'  => $product->id,
                    'method'      => 'arima',
                    'computed_at' => now()->toDateString(),
                ],
                [
                    'result_data' => [
                        // ARIMA
                        'arima_order'     => [1, 1, 1],
                        'ar_param'        => round($arima['params']['phi'][0]   ?? 0, 4),
                        'ma_param'        => round($arima['params']['theta'][0] ?? 0, 4),
                        'diff_mean'       => round($arima['params']['mean'],       4),
                        'forecast_weekly' => array_map(fn($v) => round($v, 2), $arima['forecast']),
                        'conf_lower_30d'  => round(max(0, array_sum($arima['lower']) * (30.0 / 7.0)), 2),
                        'conf_upper_30d'  => round(array_sum($arima['upper'])        * (30.0 / 7.0),  2),
                        'used_fallback'   => $arima['fallback'],
                        'mape_pct'        => $mape >= 0 ? $mape : null,
                        'weekly_series'   => $weeklySeries,
                        // Demand stats
                        'sales_90d'       => array_sum($dailySeries),
                        'avg_daily'       => round($avgDaily,  4),
                        'std_daily'       => round($stdDaily,  4),
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
                    'predicted_demand'    => round($predictedMonthly, 2),
                    'eoq_value'           => round($eoq, 2),
                    'rop_value'           => round($rop, 2),
                    'fsn_classification'  => $fsn['classification'],
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

    // ─── Time Series Builders ──────────────────────────────────────────────

    /** Builds weekly series from a pre-loaded Collection of SaleItem rows. */
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

    /** Builds daily series from a pre-loaded Collection of SaleItem rows. */
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

    // ─── ARIMA(p, d, q) ───────────────────────────────────────────────────

    /**
     * Fits ARIMA(p,d,q) to $series and returns $steps-step ahead forecasts.
     *
     * Algorithm:
     *   1. Difference the series d times to achieve stationarity.
     *   2. Mean-center the differenced series.
     *   3. Estimate AR(p) coefficients via Yule-Walker (Durbin-Levinson).
     *   4. Compute AR residuals; estimate MA(q) via Conditional Sum of Squares.
     *   5. Forecast $steps steps on the differenced, mean-centered series.
     *   6. Invert differencing to recover forecasts on the original scale.
     *   7. Build 95% CI from residual standard deviation.
     *
     * Falls back to simple exponential smoothing when insufficient data.
     */
    private function runARIMA(array $series, int $p, int $d, int $q, int $steps): array
    {
        $minRequired = $p + $d + max($q, 1) + 2;
        if (count($series) < $minRequired) {
            return $this->exponentialSmoothing($series, $steps);
        }

        // Step 1: Differencing
        $origLast = (float) end($series);
        $diffed   = $series;
        for ($i = 0; $i < $d; $i++) {
            $diffed = $this->difference($diffed);
        }

        // Step 2: Mean-center
        $mean     = array_sum($diffed) / count($diffed);
        $centered = array_map(fn($x) => $x - $mean, $diffed);

        // Step 3: AR parameters via Yule-Walker / Durbin-Levinson
        $phi = $this->yuleWalker($centered, $p);

        // Step 4: AR residuals, then MA parameters via CSS grid search
        $residuals = $this->computeARResiduals($centered, $phi, $p);
        $theta     = $this->estimateMAByCSS($centered, $phi, $p, $q);

        // Step 5: Forecast on differenced scale
        $forecastDiff = $this->forecastSteps($diffed, $residuals, $phi, $theta, $mean, $steps);

        // Step 6: Invert differencing (cumulative sum from last original value)
        $forecast = $this->undifference($forecastDiff, $origLast, $d);
        $forecast = array_map(fn($v) => max(0.0, $v), $forecast);

        // Step 7: 95% CI using residual std dev (grows with forecast horizon)
        $resSd  = $this->sampleStdDev($residuals);
        $lower  = [];
        $upper  = [];
        foreach ($forecast as $h => $f) {
            $margin = 1.96 * $resSd * sqrt($h + 1);
            $lower[] = max(0.0, $f - $margin);
            $upper[] = $f + $margin;
        }

        return [
            'forecast' => $forecast,
            'lower'    => $lower,
            'upper'    => $upper,
            'params'   => [
                'phi'   => $phi,
                'theta' => $theta,
                'mean'  => $mean,
                'd'     => $d,
            ],
            'fallback' => false,
        ];
    }

    /** First difference: ∇y_t = y_t − y_{t−1} */
    private function difference(array $series): array
    {
        $out = [];
        for ($i = 1, $n = count($series); $i < $n; $i++) {
            $out[] = $series[$i] - $series[$i - 1];
        }
        return $out;
    }

    /** Invert d-fold differencing given the last original value. */
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

    /**
     * Yule-Walker equations: estimates AR(p) coefficients from the
     * autocovariance structure of $series using Durbin-Levinson recursion.
     */
    private function yuleWalker(array $series, int $p): array
    {
        if ($p === 0) return [];

        $gamma = [];
        for ($k = 0; $k <= $p; $k++) {
            $gamma[$k] = $this->autocovariance($series, $k);
        }

        if (abs($gamma[0]) < 1e-10) {
            return array_fill(0, $p, 0.0);
        }

        if ($p === 1) {
            return [$gamma[1] / $gamma[0]];
        }

        return $this->durbinLevinson($gamma, $p);
    }

    /**
     * Durbin-Levinson algorithm: solves the Yule-Walker Toeplitz system
     * incrementally from order 1 up to p.
     *
     * Returns the p AR coefficients [φ_1, φ_2, …, φ_p].
     */
    private function durbinLevinson(array $gamma, int $p): array
    {
        $phi = [];
        $phi[1][1] = $gamma[1] / $gamma[0];
        $v         = [$gamma[0], $gamma[0] * (1.0 - $phi[1][1] ** 2)];

        for ($m = 2; $m <= $p; $m++) {
            $sum = 0.0;
            for ($k = 1; $k < $m; $k++) {
                $sum += $phi[$m - 1][$k] * $gamma[$m - $k];
            }
            $denom        = $v[$m - 1];
            $phi[$m][$m]  = abs($denom) < 1e-10 ? 0.0 : ($gamma[$m] - $sum) / $denom;

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

    /** Sample autocovariance at lag k: γ̂(k) = (1/n) Σ (y_t − ȳ)(y_{t−k} − ȳ) */
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

    /** AR residuals: ε_t = y_t − Σ φ_i · y_{t−i}  (on mean-centered series) */
    private function computeARResiduals(array $centered, array $phi, int $p): array
    {
        $n      = count($centered);
        $resid  = array_fill(0, $p, 0.0);

        for ($t = $p; $t < $n; $t++) {
            $pred = 0.0;
            for ($i = 0; $i < $p; $i++) {
                $pred += $phi[$i] * $centered[$t - $i - 1];
            }
            $resid[] = $centered[$t] - $pred;
        }
        return $resid;
    }

    /**
     * Estimates MA(q) parameters by minimising the Conditional Sum of Squares
     * (CSS) over a grid of θ values in (−0.99, 0.99) with step 0.01.
     *
     * For q = 1: one-dimensional grid search over θ₁.
     * Residual recursion: ε_t = y_t − Σ φ_i·y_{t−i} − Σ θ_j·ε_{t−j}
     */
    private function estimateMAByCSS(array $centered, array $phi, int $p, int $q): array
    {
        if ($q === 0) return [];

        $n         = count($centered);
        $bestTheta = array_fill(0, $q, 0.0);
        $bestCSS   = PHP_FLOAT_MAX;

        for ($ti = -99; $ti <= 99; $ti++) {
            $t1  = $ti / 100.0;
            $css = 0.0;
            $eps = array_fill(0, $n, 0.0);
            $start = max($p, $q);

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

    /**
     * Generates $steps forecasts on the differenced, mean-centered scale.
     *
     * For h = 1: uses the last known residual in the MA term.
     * For h > 1: future innovations are zero (point forecast = conditional mean).
     */
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

            // MA contribution: only h=0 uses the last known residual
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
     * Simple exponential smoothing fallback used when the series is too short
     * for ARIMA fitting. α = 0.3 (standard choice for inventory smoothing).
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
            'params'   => ['phi' => [], 'theta' => [], 'mean' => $level, 'd' => 0],
            'fallback' => true,
        ];
    }

    // ─── MAPE Walk-forward Validation ─────────────────────────────────────

    /**
     * Computes MAPE via hold-out validation: trains on the first (n−4) weeks,
     * forecasts the last 4 weeks, then measures mean absolute percentage error.
     *
     * Returns −1.0 when there is insufficient data or all holdout actuals are zero.
     */
    private function computeMAPE(array $weeklySeries, int $p, int $d, int $q): float
    {
        $n       = count($weeklySeries);
        $holdOut = 4;
        $minTrain = $p + $d + max($q, 1) + 2;

        if ($n < $holdOut + $minTrain) {
            return -1.0;
        }

        $trainSeries = array_slice($weeklySeries, 0, $n - $holdOut);
        $actuals     = array_slice($weeklySeries, $n - $holdOut);

        $arima     = $this->runARIMA($trainSeries, $p, $d, $q, $holdOut);
        $forecasts = $arima['forecast'];

        $errors = [];
        for ($i = 0; $i < $holdOut; $i++) {
            if ($actuals[$i] > 0) {
                $errors[] = abs($actuals[$i] - $forecasts[$i]) / $actuals[$i];
            }
        }

        if (empty($errors)) {
            return -1.0;
        }

        return round(min((array_sum($errors) / count($errors)) * 100.0, 999.9), 2);
    }

    // ─── FSN Classification ────────────────────────────────────────────────

    /**
     * Activity-based FSN classification over a 24-week review window.
     *
     * Rules (applied in order):
     *   Non-moving : no sale in ≥ 6 months  OR  active in < 10% of weeks
     *   Fast       : active in ≥ 50% of weeks  OR  avg demand ≥ 1 unit/day
     *   Slow       : everything else (10–50% activity, 0.1–1 unit/day)
     */
    private function classifyFSN(\Illuminate\Support\Collection $weeklyRows, float $avgDailyDemand): array
    {
        $weeksToCheck = 24;

        $weekSet = [];
        $lastSaleTs = null;
        foreach ($weeklyRows as $row) {
            $weekStart = Carbon::parse($row->created_at)->startOfWeek()->toDateString();
            $weekSet[$weekStart] = true;
            if ($lastSaleTs === null || $row->created_at > $lastSaleTs) {
                $lastSaleTs = $row->created_at;
            }
        }

        $activeWeeks   = count($weekSet);
        $activityRatio = $activeWeeks / $weeksToCheck;

        $lastSaleDate = $lastSaleTs ? Carbon::parse($lastSaleTs)->toDateString() : null;
        $monthsNoSale = $lastSaleTs
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

    // ─── Statistics Helpers ────────────────────────────────────────────────

    /** Sample standard deviation: s = √[ Σ(x − x̄)² / (n − 1) ] */
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
