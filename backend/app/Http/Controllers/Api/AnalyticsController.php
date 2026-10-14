<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\AnalyticsLog;
use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Predictive analytics: Auto-ARIMA demand forecasting (Croston's Method and SES
 * for sparse products), EOQ, ROP, FSN classification and turnover ratio.
 *
 * Every formula and value below is the one declared in Chapter III of the paper.
 */
class AnalyticsController extends Controller
{
    // ── Inventory parameters (Table 3) ────────────────────────────────────
    private float $orderCost   = 500.0;   // S: ordering cost per order (PHP)
    private float $holdingRate = 0.20;    // H = max(0.01, cost price x 0.20)
    private float $serviceZ    = 1.645;   // Z for a 95% service level
    private int   $leadTime    = 7;       // L: supplier lead time (days)
    private int   $weekWindow  = 208;     // weekly series length (4 years)
    private int   $dayWindow   = 1460;    // daily series length (4 years)

    // ── Forecast / accuracy parameters ────────────────────────────────────
    private const FORECAST_WEEKS        = 24;   // forecast horizon (weeks)
    private const PERIOD_WEEKS          = 4;    // one accuracy "month" = 4 consecutive weeks (28 days)
    private const WMAPE_HOLDOUT_PERIODS = 12;   // last 12 four-week periods are withheld
    private const WMAPE_MIN_PERIODS     = 36;   // at least 36 four-week periods of REAL sales history
    private const WMAPE_MIN_UNITS       = 30;   // more than 30 units sold in the holdout
    private const MONTHLY_MIN_TRAIN     = 24;   // portfolio card: months of history before ARIMA is used
    private const MONTHLY_SHOWN         = 18;   // portfolio card: months displayed

    // ─── Public Endpoints ─────────────────────────────────────────────────

    public function run()
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $products    = Product::select(['id', 'name', 'sku', 'cost_price', 'selling_price', 'stock_quantity', 'reorder_point'])->get();
        $results     = [];
        $fsnSettings = AppSetting::current();

        // The weekly series ends at the last COMPLETED week, so the week in
        // progress never counts as a week of low demand. Sales are read up to now.
        $weeklySince = now()->subWeeks($this->weekWindow)->startOfWeek();
        $dailySince  = now()->subDays($this->dayWindow - 1)->startOfDay();

        $weeklyAgg = DB::table('sale_items')
            ->selectRaw('product_id, DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY)) AS week_start, SUM(quantity) AS qty')
            ->where('created_at', '>=', $weeklySince)
            ->where('created_at', '<=', now())
            ->whereNull('deleted_at')
            ->groupBy('product_id', 'week_start')
            ->get()
            ->groupBy('product_id');

        $dailyAgg = DB::table('sale_items')
            ->selectRaw('product_id, DATE(created_at) AS sale_date, SUM(quantity) AS qty')
            ->where('created_at', '>=', $dailySince)
            ->where('created_at', '<=', now())
            ->whereNull('deleted_at')
            ->groupBy('product_id', 'sale_date')
            ->get()
            ->groupBy('product_id');

        $lastSaleMap = DB::table('sale_items')
            ->selectRaw('product_id, MAX(created_at) AS last_sale_at')
            ->where('created_at', '>=', $weeklySince)
            ->where('created_at', '<=', now())
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->pluck('last_sale_at', 'product_id');

        foreach ($products as $product) {
            $weeklySeries = $this->buildWeeklySeriesFromAgg($weeklyAgg->get($product->id, collect()), $this->weekWindow);
            $dailySeries  = $this->buildDailySeriesFromAgg($dailyAgg->get($product->id, collect()), $this->dayWindow);

            // Daily demand statistics, EOQ and ROP (Equations 8-11)
            $inv      = $this->inventoryMetrics($dailySeries, (float) $product->cost_price);
            $avgDaily = $inv['avg_daily'];
            $stdDaily = $inv['std_daily'];

            // ── Method selection (Table 4) and 24-week forecast ─────────────
            $method         = $this->selectMethod($weeklySeries);
            $forecastResult = $this->forecast($weeklySeries, $method, self::FORECAST_WEEKS);

            // 24 weekly values -> 6 buckets of 4 weeks
            $forecastMonthly = [];
            $lowerMonthly    = [];
            $upperMonthly    = [];
            for ($m = 0; $m < 6; $m++) {
                $forecastMonthly[] = round(array_sum(array_slice($forecastResult['forecast'], $m * 4, 4)), 2);
                $lowerMonthly[]    = round(max(0, array_sum(array_slice($forecastResult['lower'], $m * 4, 4))), 2);
                $upperMonthly[]    = round(array_sum(array_slice($forecastResult['upper'], $m * 4, 4)), 2);
            }

            // Expected Sales (30 days) = (sum of the first 4 weekly forecasts) x 30 / 28
            $scale30         = 30.0 / 28.0;
            $expectedSales30 = $this->expectedSales30($forecastResult['forecast']);

            // ── WMAPE (12 four-week periods withheld, real history only) ────
            $wm = $this->computeWMAPE($weeklySeries, $method, $weeklySince);

            $annualDemand = $inv['annual_demand'];
            $holdingCost  = $inv['holding_cost'];
            $eoq          = $inv['eoq'];
            $safetyStock  = $inv['safety_stock'];
            $rop          = $inv['rop'];

            // ── FSN over the product's REAL sales history (Equation 1) ──────
            $fsn    = $this->classifyFSN($weeklySeries, $avgDaily, $lastSaleMap->get($product->id), $fsnSettings);
            $params = $forecastResult['params'];

            // Turnover = annual demand / current stock (null when out of stock but selling)
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
                        'aic_table'       => $forecastResult['aic_table'] ?? null,
                        'ar_param'        => isset($params['phi'][0])   ? round($params['phi'][0],   4) : null,
                        'ma_param'        => isset($params['theta'][0]) ? round($params['theta'][0], 4) : null,
                        'ar_params'       => array_map(fn($v) => round($v, 4), $params['phi'] ?? []),
                        'ma_params'       => array_map(fn($v) => round($v, 4), $params['theta'] ?? []),
                        'diff_mean'       => round($params['mean'] ?? 0, 4),
                        'resid_sd'        => isset($params['sigma']) ? round($params['sigma'], 4) : null,
                        'used_fallback'   => $forecastResult['fallback'],
                        // Forecast outputs
                        'forecast_weekly'     => array_map(fn($v) => round($v, 4), array_slice($forecastResult['forecast'], 0, self::FORECAST_WEEKS)),
                        'forecast_monthly'    => $forecastMonthly,
                        'conf_lower_monthly'  => $lowerMonthly,
                        'conf_upper_monthly'  => $upperMonthly,
                        'conf_lower_30d'      => round($lowerMonthly[0] * $scale30, 2),
                        'conf_upper_30d'      => round($upperMonthly[0] * $scale30, 2),
                        'wmape_pct'           => $wm['wmape'] >= 0 ? $wm['wmape'] : null,
                        'wmape_holdout'       => $wm['holdout'],
                        'wmape_model'         => $wm['model'],
                        'weekly_series'       => $weeklySeries,
                        // Demand statistics
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
                        'holding_cost'    => round($holdingCost, 2),
                        // Restocking alert rule: current stock <= ROP
                        'restock_alert'   => $this->isRestockAlert((float) $product->stock_quantity, $rop),
                        // FSN detail
                        'active_weeks'    => $fsn['active_weeks'],
                        'total_weeks'     => $fsn['total_weeks'],
                        'activity_ratio'  => round($fsn['activity_ratio'], 4),
                        'last_sale_date'  => $fsn['last_sale_date'],
                        'months_no_sale'  => $fsn['months_no_sale'],
                        'weeks_no_sale'   => $fsn['weeks_no_sale'],
                        'fsn_fast_threshold_percent'      => $fsnSettings->fsn_fast_threshold_percent,
                        'fsn_nonmoving_threshold_percent' => $fsnSettings->fsn_nonmoving_threshold_percent,
                        'fsn_dead_stock_weeks'            => $fsnSettings->fsn_dead_stock_weeks,
                        'fsn_fast_min_daily_avg'          => $fsnSettings->fsn_fast_min_daily_avg,
                        // Turnover ratio components
                        'turnover_avg_inventory' => $product->stock_quantity,
                    ],
                    'predicted_demand'   => round($expectedSales30, 2),
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

        $lastRun = $latest->max('computed_at');
        $isStale = !$lastRun || $lastRun < now()->toDateString();

        return response()->json([
            'fsn_summary'   => compact('fast', 'slow', 'nonMoving'),
            'is_stale'      => $isStale,
            'last_run'      => $lastRun,
            'monthly_wmape' => $this->computeMonthlyAccuracy()['wmape'],
            'items'         => $latest,
        ]);
    }

    public function monthlyAccuracy()
    {
        $r = $this->computeMonthlyAccuracy();

        return response()->json([
            'labels'       => $r['labels'],
            'actual'       => $r['actual'],
            'forecast'     => array_map(fn($v) => round($v, 2), $r['forecast']),
            'wmape_series' => $r['wmape_series'],   // one error value per month
            'wmape'        => $r['wmape'],          // WMAPE of the last 6 COMPLETE months
        ]);
    }

    // ─── Portfolio-level monthly accuracy ─────────────────────────────────

    /**
     * Clinic-wide monthly sales (calendar months, the unfinished current month
     * excluded) forecast ONE month ahead with Auto-ARIMA, using only the months
     * before it. Until MONTHLY_MIN_TRAIN months exist the previous month is used.
     * Headline WMAPE = WMAPE of the last 6 complete months (Equation 15).
     */
    private function computeMonthlyAccuracy(): array
    {
        $cacheKey = 'analytics.monthly_accuracy.' . md5(
            now()->format('Y-m') . '|' . (string) DB::table('sale_items')->max('id')
        );

        return Cache::remember($cacheKey, 3600, fn() => $this->buildMonthlyAccuracy());
    }

    private function buildMonthlyAccuracy(): array
    {
        $evalMonths = 6;
        $min        = DB::table('sale_items')->whereNull('deleted_at')->min('created_at');
        $end        = now()->startOfMonth();                       // exclusive: current month is incomplete
        $start      = $min ? Carbon::parse($min)->startOfMonth() : $end->copy();

        $labels = [];
        $actual = [];

        if ($start < $end) {
            $rows = DB::table('sale_items')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-01") AS month, SUM(quantity) AS actual_units')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m-01")'))
                ->get();

            $byMonth = [];
            foreach ($rows as $row) {
                $byMonth[$row->month] = (float) $row->actual_units;
            }

            $cursor = $start->copy();
            while ($cursor < $end) {
                $key      = $cursor->format('Y-m-01');
                $labels[] = $key;
                $actual[] = $byMonth[$key] ?? 0.0;
                $cursor->addMonth();
            }
        }

        $forecast = $this->buildMonthlyArimaForecast($actual);

        // Show the most recent months only
        $shown   = min(count($actual), self::MONTHLY_SHOWN);
        $labels  = array_slice($labels,   -$shown);
        $actual  = array_slice($actual,   -$shown);
        $forecast = array_slice($forecast, -$shown);

        $evalStart     = max(0, count($actual) - $evalMonths);
        $wmapeSeries   = [];
        $totalActual   = 0.0;
        $totalAbsError = 0.0;

        foreach ($actual as $i => $a) {
            $err           = abs($a - $forecast[$i]);
            $wmapeSeries[] = $a > 0 ? round(($err / $a) * 100, 2) : null;

            if ($i >= $evalStart) {
                $totalActual   += $a;
                $totalAbsError += $err;
            }
        }

        return [
            'labels'       => $labels,
            'actual'       => $actual,
            'forecast'     => $forecast,
            'wmape_series' => $wmapeSeries,
            'wmape'        => $totalActual > 0 ? round(($totalAbsError / $totalActual) * 100, 2) : 0.0,
        ];
    }

    /** forecast[i] uses months 0..i-1 only (walk-forward). */
    private function buildMonthlyArimaForecast(array $actual): array
    {
        $n = count($actual);
        if ($n === 0) return [];

        $forecast = [$actual[0]];
        for ($i = 1; $i < $n; $i++) {
            // Only the months that are displayed need a model; older ones use the previous month.
            if ($i < self::MONTHLY_MIN_TRAIN || $i < $n - self::MONTHLY_SHOWN) {
                $forecast[] = $actual[$i - 1];
                continue;
            }
            $res        = $this->autoARIMA(array_slice($actual, 0, $i), 1);
            $forecast[] = max(0.0, $res['forecast'][0]);
        }

        return $forecast;
    }

    // ─── Method Selection (Table 4) ───────────────────────────────────────

    /**
     * Choose forecasting algorithm based on demand sparsity (activity ratio).
     *
     * Auto-ARIMA is the system's primary forecasting method, used for any
     * product with a reasonably regular sales pattern — regardless of how
     * long it's been selling. It only steps aside for data ARIMA genuinely
     * can't be fit to responsibly:
     *
     *   ≥ 20% active weeks → Auto-ARIMA (the default / primary method)
     *   10–20% active      → Croston's  (true intermittent demand — ARIMA's
     *                         differencing assumptions break down when most
     *                         periods are zero)
     *   < 10% active, or
     *   < 30 units total   → Optimised SES (too little signal for any
     *                         autoregressive model to learn from)
     */
    private function selectMethod(array $series): string
    {
        // buildWeeklySeriesFromAgg() always zero-pads every product out to the
        // full 4-year window, regardless of how long it's actually been selling.
        // Measuring activity ratio / length against that padded array dilutes
        // both: a product that sells every single week it's existed would still
        // show a low ratio if it only launched a few months ago. Restrict every
        // calculation below to the real-history portion (since its first sale)
        // instead.
        $weeksOfHistory = $this->weeksOfHistory($series);
        $relevant       = $weeksOfHistory > 0 ? array_slice($series, -$weeksOfHistory) : $series;

        $n       = count($relevant);
        $nonZero = count(array_filter($relevant, fn($v) => $v > 0));
        $ratio   = $n > 0 ? $nonZero / $n : 0.0;

        $totalDemand = array_sum($relevant);

        if ($totalDemand < 30) {
            return 'ses'; // too little signal for any model to learn from
        }

        if ($ratio < 0.10) {
            return 'ses';
        }

        if ($ratio < 0.20) {
            return 'croston'; // genuinely intermittent demand
        }

        return 'auto_arima';
    }

    /** Weeks since this product's first recorded sale — the real-history portion of a zero-padded series. */
    private function weeksOfHistory(array $series): int
    {
        foreach ($series as $i => $v) {
            if ($v > 0) {
                return count($series) - $i;
            }
        }
        return 0;
    }

    /**
     * Drops the zero-padded weeks before a product's first recorded sale, so
     * ARIMA fits against its actual history instead of years of irrelevant
     * leading padding (which would otherwise dominate the autocovariance
     * estimates and bias the fitted model).
     */
    private function trimLeadingZeros(array $series): array
    {
        foreach ($series as $i => $v) {
            if ($v > 0) {
                return array_values(array_slice($series, $i));
            }
        }
        return $series;
    }


    // ─── Declared formulas ────────────────────────────────────────────────

    /** Expected Sales (30 days) = (sum of the next 4 weekly forecasts) x 30 / 28. */
    private function expectedSales30(array $weeklyForecast): float
    {
        return array_sum(array_slice($weeklyForecast, 0, 4)) * (30.0 / 28.0);
    }

    /** Restocking alert rule: raised when current stock is at or below the Reorder Point. */
    private function isRestockAlert(float $stock, float $rop): bool
    {
        return $stock <= $rop;
    }

    /**
     * Daily demand statistics, EOQ and ROP.
     *   d̄ = mean(daily units)      σ = sample standard deviation of daily units
     *   D  = d̄ x 365              H = max(0.01, cost price x 0.20)
     *   EOQ = sqrt(2·D·S / H)      SS = Z·σ·sqrt(L)      ROP = d̄·L + SS
     */
    private function inventoryMetrics(array $dailySeries, float $costPrice): array
    {
        $n       = count($dailySeries);
        $avg     = $n > 0 ? array_sum($dailySeries) / $n : 0.0;
        $std     = $this->sampleStdDev($dailySeries);
        $annual  = $avg * 365.0;
        $holding = max(0.01, $costPrice * $this->holdingRate);
        $eoq     = $annual > 0 ? sqrt((2.0 * $annual * $this->orderCost) / $holding) : 0.0;
        $safety  = $this->serviceZ * $std * sqrt($this->leadTime);

        return [
            'avg_daily'     => $avg,
            'std_daily'     => $std,
            'annual_demand' => $annual,
            'holding_cost'  => $holding,
            'eoq'           => $eoq,
            'safety_stock'  => $safety,
            'rop'           => ($avg * $this->leadTime) + $safety,
        ];
    }

    // ─── Forecast dispatch ────────────────────────────────────────────────

    /**
     * Dispatch to the assigned method. ARIMA is fitted on the product's REAL
     * sales history only (leading zero-weeks before its first sale removed).
     */
    private function forecast(array $series, string $method, int $steps): array
    {
        return match ($method) {
            'croston'    => $this->crostonMethod($series, $steps),
            'ses'        => $this->optimizedSES($series, $steps),
            'auto_arima' => $this->autoARIMA($this->trimLeadingZeros($series), $steps),
            default      => $this->runARIMA($this->trimLeadingZeros($series), 1, 1, 1, $steps),
        };
    }

    // ─── Auto-ARIMA ───────────────────────────────────────────────────────

    /**
     * Fits the seven candidate orders (0,1,0) (1,1,0) (0,1,1) (1,1,1) (2,1,1)
     * (1,1,2) (2,1,2), keeps the one with the lowest AIC (Equation 3) and
     * forecasts with it. All AICs are computed on the same observations.
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
        $table     = [];

        foreach ($candidates as [$p, $d, $q]) {
            $minObs = $p + $d + max($q, 1) + 4;
            if (count($series) < $minObs) continue;

            $aic = $this->computeAIC($series, $p, $d, $q);
            if ($aic >= PHP_FLOAT_MAX) continue;

            $table[] = ['order' => [$p, $d, $q], 'aic' => round($aic, 3)];
            if ($aic < $bestAIC) {
                $bestAIC   = $aic;
                $bestOrder = [$p, $d, $q];
            }
        }

        [$p, $d, $q] = $bestOrder;
        $result = $this->runARIMA($series, $p, $d, $q, $steps);
        $result['arima_order_selected'] = $bestOrder;
        $result['aic_table']            = $table;
        return $result;
    }

    /**
     * AIC = n·ln(RSS/n) + 2k, k = p + q + 1 (Equation 3).
     * The first two residuals are dropped for EVERY candidate so that n (and
     * therefore the AIC) is comparable between orders.
     */
    private function computeAIC(array $series, int $p, int $d, int $q): float
    {
        if (count($series) < $p + $d + max($q, 1) + 2) return PHP_FLOAT_MAX;

        $diffed = $series;
        for ($i = 0; $i < $d; $i++) {
            $diffed = $this->difference($diffed);
        }

        $mean     = array_sum($diffed) / count($diffed);
        $centered = array_map(fn($x) => $x - $mean, $diffed);
        $phi      = $this->yuleWalker($centered, $p);
        $theta    = $this->estimateMAByCSS($centered, $phi, $p, $q);
        $eps      = $this->computeARMAResiduals($centered, $phi, $theta, $p, $q);

        $valid = array_slice($eps, 2);
        $n     = count($valid);
        if ($n < 2) return PHP_FLOAT_MAX;

        $rss = array_sum(array_map(fn($r) => $r ** 2, $valid));
        if ($rss <= 0.0) return PHP_FLOAT_MAX;

        return $n * log($rss / $n) + 2.0 * ($p + $q + 1);
    }

    // ─── WMAPE (Equation 15) ──────────────────────────────────────────────

    /**
     * Hold-out WMAPE for Auto-ARIMA products.
     *
     *   • Only the product's REAL sales history is used (weeks before its first
     *     sale are removed) and it must cover at least 36 four-week periods.
     *   • One "month" = 4 consecutive weeks (28 days). Periods are aligned to the
     *     newest completed week, so all 12 held-out periods are complete.
     *   • The last 12 periods are withheld, ARIMA is refitted on the earlier
     *     periods and forecasts the 12 withheld ones.
     *   • The holdout must contain more than 30 units, otherwise "N/A".
     *
     * @return array{wmape: float, holdout: array}  wmape = -1 means N/A
     */
    private function computeWMAPE(array $weeklySeries, string $method, Carbon $weeklySince): array
    {
        $none = ['wmape' => -1.0, 'holdout' => [], 'model' => null];
        if ($method !== 'auto_arima') return $none;

        $real    = $this->trimLeadingZeros($weeklySeries);
        $leading = count($weeklySeries) - count($real);
        $monthly = $this->toMonthlySeries($real);
        $n       = count($monthly);
        $hold    = self::WMAPE_HOLDOUT_PERIODS;

        if ($n < self::WMAPE_MIN_PERIODS) return $none;

        $train   = array_slice($monthly, 0, $n - $hold);
        $actuals = array_slice($monthly, $n - $hold);

        if (array_sum($actuals) <= self::WMAPE_MIN_UNITS) return $none;

        $fit       = $this->autoARIMA($train, $hold);       // fitted on the 4-week totals before the holdout
        $forecasts = $fit['forecast'];

        $offset        = count($real) % self::PERIOD_WEEKS;    // oldest leftover weeks dropped
        $totalAbsError = 0.0;
        $totalActual   = 0.0;
        $rows          = [];

        for ($i = 0; $i < $hold; $i++) {
            $totalAbsError += abs($actuals[$i] - $forecasts[$i]);
            $totalActual   += $actuals[$i];

            $weekIdx = $leading + $offset + ($n - $hold + $i) * self::PERIOD_WEEKS;
            $from    = $weeklySince->copy()->addWeeks($weekIdx);
            $rows[]  = [
                'period'   => $i + 1,
                'from'     => $from->toDateString(),
                'to'       => $from->copy()->addWeeks(self::PERIOD_WEEKS)->subDay()->toDateString(),
                'actual'   => round($actuals[$i], 2),
                'forecast' => round($forecasts[$i], 2),
            ];
        }

        if ($totalActual <= 0.0) return $none;

        return [
            'wmape'   => round(min(($totalAbsError / $totalActual) * 100, 999.9), 2),
            'holdout' => $rows,
            // The holdout model is fitted on 4-week totals, so its order and coefficients
            // can differ from the weekly forecasting model - they are reported separately.
            'model'   => [
                'order'         => $fit['arima_order_selected'] ?? null,
                'ar'            => array_map(fn($v) => round($v, 4), $fit['params']['phi'] ?? []),
                'ma'            => array_map(fn($v) => round($v, 4), $fit['params']['theta'] ?? []),
                'mean'          => round($fit['params']['mean'] ?? 0, 4),
                'train_periods' => count($train),
            ],
        ];
    }

    /** Collapse weekly sales into 4-week periods, aligned to the NEWEST week. */
    private function toMonthlySeries(array $weeklySeries): array
    {
        $monthly = [];
        $n       = count($weeklySeries);

        for ($i = $n % self::PERIOD_WEEKS; $i + self::PERIOD_WEEKS <= $n; $i += self::PERIOD_WEEKS) {
            $monthly[] = array_sum(array_slice($weeklySeries, $i, self::PERIOD_WEEKS));
        }

        return $monthly;
    }

    // ─── ARIMA(p, d, q) ───────────────────────────────────────────────────

    /**
     * Fits ARIMA(p,d,q), d = 1, and forecasts $steps weeks ahead.
     *
     *   w(t)  = y(t) - y(t-1)                                           (Equation 2)
     *   w(t+1) = mu + sum_i phi_i (w(t+1-i) - mu) + sum_j theta_j e(t+1-j)   (Equation 4)
     *   y(t+h) = y(t) + sum_{k=1..h} w(t+k)   (y(t) = LAST observed week)   (Equation 5)
     *   band   = y(t+h) +/- 1.96 · s_e · sqrt(h)   (h = weeks ahead, 1..24) (Equation 6)
     *
     * Future errors have expected value 0, so MA terms only act on the first q
     * forecast steps. Falls back to SES when the series is too short.
     */
    private function runARIMA(array $series, int $p, int $d, int $q, int $steps): array
    {
        if (count($series) < $p + $d + max($q, 1) + 2) {
            return $this->exponentialSmoothing($series, $steps);
        }

        $origLast = (float) end($series);          // Equation 5 starts from the last observation
        $diffed   = $series;
        for ($i = 0; $i < $d; $i++) {
            $diffed = $this->difference($diffed);
        }

        $mean     = array_sum($diffed) / count($diffed);
        $centered = array_map(fn($x) => $x - $mean, $diffed);

        $phi   = $this->yuleWalker($centered, $p);
        $theta = $this->estimateMAByCSS($centered, $phi, $p, $q);
        $eps   = $this->computeARMAResiduals($centered, $phi, $theta, $p, $q);

        $forecastDiff = $this->forecastSteps($diffed, $eps, $phi, $theta, $mean, $steps);
        $forecast     = $this->undifference($forecastDiff, $origLast, $d);
        $forecast     = array_map(fn($v) => max(0.0, $v), $forecast);

        $resSd = $this->sampleStdDev(array_slice($eps, max($p, $q)));
        $lower = [];
        $upper = [];
        foreach ($forecast as $h => $f) {
            $margin  = 1.96 * $resSd * sqrt($h + 1);     // $h is 0-based, weeks ahead = $h + 1
            $lower[] = max(0.0, $f - $margin);
            $upper[] = $f + $margin;
        }

        return [
            'forecast' => $forecast,
            'lower'    => $lower,
            'upper'    => $upper,
            'params'   => ['phi' => $phi, 'theta' => $theta, 'mean' => $mean, 'd' => $d, 'sigma' => $resSd],
            'fallback' => false,
        ];
    }

    /**
     * MA coefficients by conditional sum of squares (CSS).
     *
     *   q = 1 : grid theta1 = -0.99 ... 0.99, step 0.01 -> smallest CSS.
     *   q = 2 : the same grid is searched one coefficient at a time (theta1 with
     *           theta2 fixed, then theta2 with theta1 fixed), repeated for two
     *           sweeps; only invertible pairs are accepted.
     */
    private function estimateMAByCSS(array $centered, array $phi, int $p, int $q): array
    {
        if ($q === 0) return [];

        $theta = array_fill(0, $q, 0.0);
        $best  = $this->cssValue($centered, $phi, $theta, $p, $q);
        $sweeps = $q === 1 ? 1 : 2;

        for ($sweep = 0; $sweep < $sweeps; $sweep++) {
            for ($j = 0; $j < $q; $j++) {
                for ($ti = -99; $ti <= 99; $ti++) {
                    $cand     = $theta;
                    $cand[$j] = $ti / 100.0;
                    if (!$this->isInvertible($cand)) continue;
                    $v = $this->cssValue($centered, $phi, $cand, $p, $q);
                    if ($v < $best - 1e-12) {
                        $best  = $v;
                        $theta = $cand;
                    }
                }
            }
        }

        return $theta;
    }

    private function cssValue(array $centered, array $phi, array $theta, int $p, int $q): float
    {
        $eps = $this->computeARMAResiduals($centered, $phi, $theta, $p, $q);
        $sum = 0.0;
        for ($t = max($p, $q), $n = count($eps); $t < $n; $t++) {
            $sum += $eps[$t] ** 2;
        }
        return $sum;
    }

    /** MA invertibility: the roots of 1 + theta1·z (+ theta2·z²) lie outside the unit circle. */
    private function isInvertible(array $theta): bool
    {
        if (count($theta) === 1) return abs($theta[0]) < 1.0;
        if (count($theta) === 2) {
            return abs($theta[1]) < 1.0
                && ($theta[1] + $theta[0]) > -1.0
                && ($theta[1] - $theta[0]) > -1.0;
        }
        return true;
    }

    /**
     * Recursive forecast of the differenced series (Equation 4). Residuals of
     * future weeks are 0, so a theta term only contributes while it still
     * reaches back into the observed residuals.
     */
    private function forecastSteps(
        array $diffed,
        array $eps,
        array $phi,
        array $theta,
        float $mean,
        int   $steps
    ): array {
        $p   = count($phi);
        $q   = count($theta);
        $ext = $diffed;
        $e   = $eps;
        $out = [];

        for ($h = 0; $h < $steps; $h++) {
            $t      = count($ext);
            $arPart = 0.0;
            for ($i = 0; $i < $p; $i++) {
                $idx = $t - $i - 1;
                if ($idx >= 0) $arPart += $phi[$i] * ($ext[$idx] - $mean);
            }
            $maPart = 0.0;
            for ($j = 0; $j < $q; $j++) {
                $idx = $t - $j - 1;
                if ($idx >= 0) $maPart += $theta[$j] * ($e[$idx] ?? 0.0);
            }
            $val   = $mean + $arPart + $maPart;
            $out[] = $val;
            $ext[] = $val;
            $e[]   = 0.0;
        }

        return $out;
    }

    // ─── FSN Classification ────────────────────────────────────────────────

    /**
     * FSN over the product's REAL sales history (Equation 1):
     *
     *   activity ratio = weeks with sales / weeks since its first sale
     *
     *   Non-moving : weeks since last sale >= dead-stock weeks  OR  ratio < non-moving %
     *   Fast       : ratio >= fast %  OR  average daily demand >= fast minimum
     *   Slow       : everything else
     *
     * The four thresholds are administrator settings (Settings page).
     */
    private function classifyFSN(array $weeklySeries, float $avgDailyDemand, ?string $lastSaleAt, AppSetting $settings): array
    {
        $fastRatio       = $settings->fsn_fast_threshold_percent / 100.0;
        $nonMovingRatio  = $settings->fsn_nonmoving_threshold_percent / 100.0;
        $deadStockWeeks  = $settings->fsn_dead_stock_weeks;
        $fastMinDaily    = $settings->fsn_fast_min_daily_avg;

        $weeksOfHistory = $this->weeksOfHistory($weeklySeries);
        $relevant       = $weeksOfHistory > 0 ? array_slice($weeklySeries, -$weeksOfHistory) : [];
        $totalWeeks     = count($relevant);
        $activeWeeks    = count(array_filter($relevant, fn($v) => $v > 0));
        $activityRatio  = $totalWeeks > 0 ? $activeWeeks / $totalWeeks : 0.0;

        $lastSaleDate = $lastSaleAt ? Carbon::parse($lastSaleAt)->toDateString() : null;
        $weeksNoSale  = $lastSaleAt ? round(Carbon::parse($lastSaleAt)->diffInDays(now()) / 7, 1) : 999.0;
        $monthsNoSale = $lastSaleAt ? round(Carbon::parse($lastSaleAt)->diffInDays(now()) / 30.44, 1) : 999.0;

        $classification = match (true) {
            $weeksNoSale >= $deadStockWeeks || $activityRatio < $nonMovingRatio => 'non_moving',
            $activityRatio >= $fastRatio || $avgDailyDemand >= $fastMinDaily    => 'fast',
            default => 'slow',
        };

        return [
            'classification' => $classification,
            'active_weeks'   => $activeWeeks,
            'total_weeks'    => $totalWeeks,
            'activity_ratio' => $activityRatio,
            'last_sale_date' => $lastSaleDate,
            'weeks_no_sale'  => $weeksNoSale,
            'months_no_sale' => $monthsNoSale,
        ];
    }

    // ─── Croston and SES (sparse products) ────────────────────────────────

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

    // ─── ARIMA helpers ────────────────────────────────────────────────────

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
            // Floor at each step, not just on the final array. A single large
            // negative MA correction (one outsized recent swing combined with
            // a strong theta) can push the first forecasted step sharply
            // negative; since this is a cumulative sum, leaving it negative
            // would permanently drag every later step down too, and a single
            // end-of-array clamp would then zero out the ENTIRE forecast
            // instead of just the one bad step.
            $prev     = max(0.0, $prev + $diff);
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

    /** Full ARMA residuals (AR + MA terms) — used for AIC computation. */
    private function computeARMAResiduals(array $centered, array $phi, array $theta, int $p, int $q): array
    {
        $n   = count($centered);
        $eps = array_fill(0, $n, 0.0);

        // estimateMAByCSS() only ever fits a single theta coefficient regardless
        // of the requested $q (candidates like ARIMA(2,1,2) ask for q=2), so loop
        // over what was actually estimated — looping to the requested $q instead
        // reads past the end of $theta.
        $qActual = count($theta);

        for ($t = max($p, $q); $t < $n; $t++) {
            $arPart = 0.0;
            for ($i = 0; $i < $p; $i++) {
                if ($t - $i - 1 >= 0) {
                    $arPart += $phi[$i] * $centered[$t - $i - 1];
                }
            }
            $maPart = 0.0;
            for ($j = 0; $j < $qActual; $j++) {
                if ($t - $j - 1 >= 0) {
                    $maPart += $theta[$j] * $eps[$t - $j - 1];
                }
            }
            $eps[$t] = $centered[$t] - $arPart - $maPart;
        }

        return $eps;
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

    // ─── Time series builders and statistics ──────────────────────────────

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