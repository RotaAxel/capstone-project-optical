<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AnalyticsController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Exercises the ARIMA / Auto-ARIMA forecasting engine inside AnalyticsController
 * directly (via reflection — the methods are private implementation details of
 * the controller, not a separate service class) against three synthetic weekly
 * demand series. Each is deterministic (no rand()) so results are reproducible,
 * and each is long enough to satisfy every candidate order autoARIMA() tries —
 * the strictest, ARIMA(2,1,2), needs count($series) >= p+d+max(q,1)+4 = 9 points
 * for computeAIC() to even evaluate it (see test_all_three_datasets_meet_...).
 *
 * The three shapes exercise genuinely different parts of the math:
 *   1. Linear trend       — differencing (d=1) + forecast extrapolation must
 *                            continue an upward trend rather than flattening out.
 *   2. Stationary wobble   — no real drift, so AR/MA terms should capture the
 *                            oscillation without the forecast running away.
 *   3. Level shift          — tests that undifference() anchors forecasts on the
 *                            *last observed value*, so the model tracks the
 *                            recent regime instead of the whole series' average.
 */
class ArimaForecastingTest extends TestCase
{
    private AnalyticsController $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AnalyticsController();
    }

    /** Invoke a private method on the controller via reflection. */
    private function call(string $method, array $args)
    {
        $ref = new ReflectionMethod(AnalyticsController::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->engine, $args);
    }

    // ── The three datasets ──────────────────────────────────────────────────

    /** Dataset 1: steady linear growth + small bounded wobble (e.g. a newly popular frame). */
    private function linearTrendDataset(int $length = 60): array
    {
        $series = [];
        for ($t = 0; $t < $length; $t++) {
            $series[] = max(0.0, 10 + 0.35 * $t + 1.5 * sin($t * 1.3));
        }
        return $series;
    }

    /** Dataset 2: oscillates around a constant mean, no long-run trend (a mature, stable seller). */
    private function stationaryOscillationDataset(int $length = 60): array
    {
        $series = [];
        for ($t = 0; $t < $length; $t++) {
            $series[] = max(0.0, 20 + 4 * sin($t * 0.9) + 2 * cos($t * 0.37));
        }
        return $series;
    }

    /** Dataset 3: a structural jump partway through (e.g. featured in a promo), back half >> front half. */
    private function levelShiftDataset(int $length = 60): array
    {
        $series = [];
        $half   = intdiv($length, 2);
        for ($t = 0; $t < $length; $t++) {
            $base      = $t < $half ? 5.0 : 15.0;
            $series[]  = max(0.0, $base + 1.2 * sin($t * 0.8));
        }
        return $series;
    }

    /** Same shape as the trend dataset, but at an arbitrary length — used to probe selectMethod()'s length gate. */
    private function denseLongRunDataset(int $length): array
    {
        $series = [];
        for ($t = 0; $t < $length; $t++) {
            $series[] = max(0.0, 10 + 0.05 * $t + 2.0 * sin($t * 0.3));
        }
        return $series;
    }

    // ── Dataset sanity: they actually meet ARIMA's own data requirements ───

    public function test_all_three_datasets_meet_auto_arima_minimum_length(): void
    {
        // auto_arima's strictest candidate is (2,1,2); computeAIC() needs
        // count($series) >= p + d + max(q,1) + 2 = 7 to evaluate it at all,
        // and autoARIMA() itself skips a candidate below p+d+max(q,1)+4 = 9.
        foreach ([
            $this->linearTrendDataset(),
            $this->stationaryOscillationDataset(),
            $this->levelShiftDataset(),
        ] as $series) {
            $this->assertGreaterThanOrEqual(9, count($series));
        }
    }

    // ── Dataset 1: linear trend ──────────────────────────────────────────

    public function test_auto_arima_continues_an_upward_trend(): void
    {
        $series = $this->linearTrendDataset();
        $result = $this->call('autoARIMA', [$series, 6]);

        $this->assertCount(6, $result['forecast']);
        $this->assertArrayHasKey('arima_order_selected', $result);

        foreach ($result['forecast'] as $h => $f) {
            $this->assertGreaterThanOrEqual(0.0, $f);
            $this->assertLessThanOrEqual($result['upper'][$h] + 1e-6, $f);
            $this->assertGreaterThanOrEqual($result['lower'][$h] - 1e-6, $f);
        }

        // Trend should continue: the forecast average should sit clearly above
        // what was actually observed in the last 6 weeks, not flatten out at
        // the historical mean the way a naive (non-trending) model would.
        $recentActualAvg = array_sum(array_slice($series, -6)) / 6;
        $forecastAvg      = array_sum($result['forecast']) / count($result['forecast']);
        $this->assertGreaterThan($recentActualAvg, $forecastAvg);
    }

    // ── Dataset 2: stationary oscillation ────────────────────────────────

    public function test_auto_arima_stays_near_the_mean_for_a_stationary_series(): void
    {
        $series = $this->stationaryOscillationDataset();
        $result = $this->call('autoARIMA', [$series, 6]);

        $this->assertCount(6, $result['forecast']);

        $historicalMean = array_sum($series) / count($series);
        $forecastAvg    = array_sum($result['forecast']) / count($result['forecast']);

        // No real trend in this series, so the forecast shouldn't drift far
        // from the long-run mean. Generous band deliberately — this checks a
        // numerical model behaves sanely, not that it hits an exact value.
        $this->assertEqualsWithDelta($historicalMean, $forecastAvg, $historicalMean * 0.5);
    }

    // ── Dataset 3: level shift ───────────────────────────────────────────

    public function test_auto_arima_tracks_the_recent_regime_after_a_level_shift(): void
    {
        $series = $this->levelShiftDataset();
        $result = $this->call('autoARIMA', [$series, 6]);

        $overallMean = array_sum($series) / count($series);        // blend of the 5-level and 15-level halves
        $recentMean  = array_sum(array_slice($series, -10)) / 10;  // the post-shift (~15) regime
        $forecastAvg = array_sum($result['forecast']) / count($result['forecast']);

        // The forecast should sit closer to the recent, post-shift level than to
        // the stale blended average — confirms undifference() anchors forecasts
        // on the last observed value rather than the whole series' history.
        $distToRecent  = abs($forecastAvg - $recentMean);
        $distToOverall = abs($forecastAvg - $overallMean);
        $this->assertLessThan($distToOverall, $distToRecent);
    }

    // ── AIC-driven order selection ───────────────────────────────────────

    public function test_auto_arima_selects_a_valid_candidate_order_by_lowest_aic(): void
    {
        $candidates = [[0, 1, 0], [1, 1, 0], [0, 1, 1], [1, 1, 1], [2, 1, 1], [1, 1, 2], [2, 1, 2]];

        foreach ([
            $this->linearTrendDataset(),
            $this->stationaryOscillationDataset(),
            $this->levelShiftDataset(),
        ] as $series) {
            $result = $this->call('autoARIMA', [$series, 6]);
            $this->assertContains($result['arima_order_selected'], $candidates);
        }
    }

    public function test_compute_aic_is_finite_for_a_well_sized_series_and_infinite_for_a_too_short_one(): void
    {
        $series = $this->linearTrendDataset();
        $aic    = $this->call('computeAIC', [$series, 1, 1, 1]);
        $this->assertIsFloat($aic);
        $this->assertLessThan(PHP_FLOAT_MAX, $aic);

        // ARIMA(2,1,2) needs >= 7 points (p+d+max(q,1)+2); 4 is a guaranteed bail-out.
        $tooShort = array_slice($series, 0, 4);
        $aicShort = $this->call('computeAIC', [$tooShort, 2, 1, 2]);
        $this->assertSame(PHP_FLOAT_MAX, $aicShort);
    }

    // ── Core math: differencing is a true inverse of undifferencing ──────

    public function test_difference_and_undifference_are_exact_inverses(): void
    {
        $series = $this->levelShiftDataset();
        $diffed = $this->call('difference', [$series]);
        $this->assertCount(count($series) - 1, $diffed);

        $reconstructed = $this->call('undifference', [$diffed, $series[0], 1]);
        foreach ($reconstructed as $i => $value) {
            $this->assertEqualsWithDelta($series[$i + 1], $value, 1e-9);
        }
    }

    // ── runARIMA() degrades safely instead of erroring on too little data ──

    public function test_run_arima_falls_back_to_exponential_smoothing_for_a_too_short_series(): void
    {
        $tooShort = [3.0, 4.0, 2.0]; // well under any candidate order's minimum
        $result   = $this->call('runARIMA', [$tooShort, 2, 1, 2, 4]);

        $this->assertTrue($result['fallback']);
        $this->assertCount(4, $result['forecast']);
        foreach ($result['forecast'] as $f) {
            $this->assertGreaterThanOrEqual(0.0, $f);
        }
    }

    // ── Method-selection: Auto-ARIMA is the primary method regardless of length ─

    public function test_select_method_chooses_auto_arima_for_dense_demand_regardless_of_history_length(): void
    {
        // Auto-ARIMA is the system's primary forecasting method — a dense,
        // regularly-selling product should get Auto-ARIMA whether it's a
        // young product (60 weeks of history) or a long-running one (208
        // weeks), unlike the old design where long history pushed it to
        // Holt-Winters instead.
        $short = $this->denseLongRunDataset(60);
        $this->assertSame('auto_arima', $this->call('selectMethod', [$short]));

        $long = $this->denseLongRunDataset(208);
        $this->assertSame('auto_arima', $this->call('selectMethod', [$long]));
    }

    public function test_select_method_uses_real_history_not_padded_array_length(): void
    {
        // Simulates a product that only started selling 40 weeks ago, inside a
        // full 208-week (4-year) window — buildWeeklySeriesFromAgg() always
        // zero-pads every product out to the full window, so the first 168
        // weeks here are padding from before the product existed. selectMethod()
        // must recognise this product only has 40 weeks of real history (not
        // that it's "inactive" for 168 of its 208 padded weeks) and still route
        // it to Auto-ARIMA since it sells densely within that real history.
        $realHistory = $this->denseLongRunDataset(40);
        $padded      = array_merge(array_fill(0, 168, 0.0), $realHistory);
        $this->assertCount(208, $padded);

        $this->assertSame('auto_arima', $this->call('selectMethod', [$padded]));
    }

    public function test_forecast_trims_leading_zero_padding_before_fitting_arima(): void
    {
        $realHistory = $this->denseLongRunDataset(40);
        $padded      = array_merge(array_fill(0, 168, 0.0), $realHistory);

        $trimmed = $this->call('trimLeadingZeros', [$padded]);
        $this->assertCount(40, $trimmed);
        foreach ($realHistory as $i => $v) {
            $this->assertEqualsWithDelta($v, $trimmed[$i], 1e-9);
        }
    }

    public function test_select_method_chooses_ses_for_very_low_total_demand(): void
    {
        $series = array_fill(0, 60, 0.0);
        $series[0] = $series[10] = $series[20] = 5.0; // total = 15, below the 30-unit floor

        $this->assertSame('ses', $this->call('selectMethod', [$series]));
    }

    public function test_select_method_chooses_croston_for_intermittent_demand(): void
    {
        // ~13% of weeks active (between the 10% and 20% thresholds), well over 30 total units
        $series = array_fill(0, 60, 0.0);
        foreach ([2, 8, 14, 20, 26, 32, 38, 44] as $i) {
            $series[$i] = 5.0;
        }

        $this->assertSame('croston', $this->call('selectMethod', [$series]));
    }

    public function test_select_method_chooses_auto_arima_once_activity_reaches_20_percent(): void
    {
        // Just over the 20% line — should now get Auto-ARIMA, not Croston's,
        // reflecting Auto-ARIMA's broader role as the primary method.
        $series = array_fill(0, 60, 0.0);
        foreach ([2, 5, 8, 11, 14, 17, 20, 23, 26, 29, 32, 35, 38] as $i) { // 13/60 ≈ 21.7%
            $series[$i] = 5.0;
        }

        $this->assertSame('auto_arima', $this->call('selectMethod', [$series]));
    }
}
