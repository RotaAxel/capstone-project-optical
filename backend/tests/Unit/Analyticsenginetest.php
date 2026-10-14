<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AnalyticsController;
use App\Models\AppSetting;
use Carbon\Carbon;
use Database\Seeders\ArimaDemoSeeder;
use ReflectionClass;
use Tests\TestCase;

/**
 * Test cases for the predictive analytics engine, using the four ARIMA demo
 * datasets (ArimaDemoSeeder) as the known inputs. No database is needed: every
 * test feeds the weekly series straight into the engine, exactly as
 * AnalyticsController::run() does, and checks the output against a value that
 * can be recomputed by hand from the raw data.
 *
 *   php artisan test tests/Unit/AnalyticsEngineTest.php
 */
class AnalyticsEngineTest extends TestCase
{
    private AnalyticsController $controller;
    private ReflectionClass $ref;
    private array $data;     // sku => weekly unit sales (oldest -> newest)

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 10:00:00');          // a Wednesday

        $this->controller = new AnalyticsController();
        $this->ref        = new ReflectionClass($this->controller);

        $seeder = new ArimaDemoSeeder();
        $m      = (new ReflectionClass($seeder))->getMethod('datasets');
        $m->setAccessible(true);
        $this->data = [];
        foreach ($m->invoke($seeder) as $d) {
            $this->data[$d['sku']] = $d['weekly'];
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function call(string $method, mixed ...$args): mixed
    {
        $m = $this->ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke($this->controller, ...$args);
    }

    /** The engine's 208-week series: shorter datasets are right-aligned, older weeks are zero. */
    private function padded(string $sku): array
    {
        $w = array_map('floatval', $this->data[$sku]);
        return array_merge(array_fill(0, 208 - count($w), 0.0), $w);
    }

    private function monday(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('2026-10-12'))->modify('-208 weeks');   // start of the 208-week window
    }

    private function weeklySince(): Carbon
    {
        return Carbon::parse($this->monday()->format('Y-m-d'));
    }

    /** Daily series the way ArimaDemoSeeder records it: ONE sale on the Wednesday of each week. */
    private function daily(string $sku): array
    {
        $w        = $this->data[$sku];
        $offset   = 208 - count($w);
        $dayStart = (new \DateTimeImmutable('2026-10-14'))->modify('-1459 days');
        $daily    = array_fill(0, 1460, 0.0);
        foreach ($w as $i => $q) {
            $wed = $this->monday()->modify('+' . ($offset + $i) . ' weeks +2 days');
            $idx = (int) round(($wed->getTimestamp() - $dayStart->getTimestamp()) / 86400);
            if ($idx >= 0 && $idx < 1460) $daily[$idx] += $q;
        }
        return $daily;
    }

    private function fit(string $sku): array
    {
        $s = $this->padded($sku);
        return $this->call('forecast', $s, $this->call('selectMethod', $s), 24);
    }

    // ═══ Panel: "ARIMA or Holt-Winters - which is true?" ══════════════════

    public function test_holt_winters_has_been_removed(): void
    {
        foreach (['holtWinters', 'runHoltWinters', 'runHoltWintersMultiplicative', 'hwInSampleMSE', 'hwMultInSampleMSE', 'hasSeasonal'] as $gone) {
            $this->assertFalse($this->ref->hasMethod($gone), "$gone() must not exist");
        }
        $this->assertStringNotContainsStringIgnoringCase('holt', file_get_contents($this->ref->getFileName()));
    }

    public function test_every_demo_dataset_is_assigned_auto_arima(): void
    {
        foreach (array_keys($this->data) as $sku) {
            $this->assertSame('auto_arima', $this->call('selectMethod', $this->padded($sku)), $sku);
        }
    }

    // ═══ Panel: order selection and every coefficient ═════════════════════

    public function test_dataset_2_selects_the_order_with_the_lowest_aic(): void
    {
        $r     = $this->fit('ARIMA-DEMO-STABLE');
        $table = [];
        foreach ($r['aic_table'] as $row) $table[implode(',', $row['order'])] = $row['aic'];

        $this->assertCount(7, $table, 'all seven candidate orders are scored');
        $this->assertSame('2,1,2', implode(',', $r['arima_order_selected']));
        $this->assertLessThan($table['2,1,1'], $table['2,1,2'], '(2,1,2) beats the previously reported (2,1,1)');
        $this->assertEqualsWithDelta(min($table), $table[implode(',', $r['arima_order_selected'])], 1e-9, 'selected = minimum AIC');
    }

    public function test_every_ar_and_ma_coefficient_is_reported(): void
    {
        foreach (array_keys($this->data) as $sku) {
            $r = $this->fit($sku);
            [$p, , $q] = $r['arima_order_selected'];
            $this->assertCount($p, $r['params']['phi'],   "$sku AR coefficients");
            $this->assertCount($q, $r['params']['theta'], "$sku MA coefficients");
        }
    }

    public function test_models_with_two_ma_terms_are_estimated(): void
    {
        // MA(2) with theta = (0.5, 0.3), deterministic noise
        $s = 12345; $e = [];
        for ($i = 0; $i < 600; $i++) {
            $s = ($s * 48271) % 2147483647;  $u1 = $s / 2147483647;
            $s = ($s * 48271) % 2147483647;  $u2 = $s / 2147483647;
            $e[] = sqrt(-2 * log(max($u1, 1e-12))) * cos(2 * M_PI * $u2);
        }
        $x = [];
        for ($t = 2; $t < 600; $t++) $x[] = $e[$t] + 0.5 * $e[$t - 1] + 0.3 * $e[$t - 2];

        $theta = $this->call('estimateMAByCSS', $x, [], 0, 2);
        $this->assertCount(2, $theta);
        $this->assertTrue($this->call('isInvertible', $theta));
        $this->assertEqualsWithDelta(0.5, $theta[0], 0.15);
        $this->assertEqualsWithDelta(0.3, $theta[1], 0.15);
    }

    // ═══ Panel: "forecasts cannot be produced from the model" ═════════════

    public function test_dataset_3_forecast_can_be_recomputed_by_hand(): void
    {
        $series = $this->padded('ARIMA-DEMO-SHIFT');
        $r      = $this->call('autoARIMA', $series, 24);

        $this->assertSame([0, 1, 1], $r['arima_order_selected']);
        $theta = $r['params']['theta'][0];
        $mu    = $r['params']['mean'];
        $this->assertEqualsWithDelta(0.26, $theta, 0.01);

        // Hand recomputation: w = first difference, residual e(t) = (w(t) - mu) - theta * e(t-1)
        $w = [];
        for ($i = 1; $i < count($series); $i++) $w[] = $series[$i] - $series[$i - 1];
        $this->assertEqualsWithDelta(array_sum($w) / count($w), $mu, 1e-9, 'mu = mean of the weekly changes');

        $e = [0.0];
        for ($t = 1; $t < count($w); $t++) $e[$t] = ($w[$t] - $mu) - $theta * $e[$t - 1];

        $last   = end($series);                                  // Equation 5 starts from the last observation
        $step1  = $mu + $theta * end($e);                        // future errors are 0
        $step2  = $mu;
        $this->assertEqualsWithDelta($last + $step1,          $r['forecast'][0], 1e-6, 'week 1');
        $this->assertEqualsWithDelta($last + $step1 + $step2, $r['forecast'][1], 1e-6, 'week 2');
    }

    public function test_forecast_is_anchored_on_the_last_observation(): void
    {
        $line = range(10.0, 59.0);                              // +1 per week, last observation 59
        $r    = $this->call('runARIMA', $line, 0, 1, 0, 3);
        foreach ([60.0, 61.0, 62.0] as $i => $expected) {
            $this->assertEqualsWithDelta($expected, $r['forecast'][$i], 1e-9, 'week ' . ($i + 1));
        }
    }

    // ═══ Panel: formulas for Expected Sales (30 days) and the alert ═══════

    public function test_expected_sales_30_days_formula(): void
    {
        $this->assertEqualsWithDelta(40 * 30 / 28, $this->call('expectedSales30', [10.0, 10.0, 10.0, 10.0, 99.0]), 1e-9);

        $r = $this->fit('ARIMA-DEMO-TREND');
        $this->assertEqualsWithDelta(85.22, $this->call('expectedSales30', $r['forecast']), 0.01);
    }

    public function test_restocking_alert_rule_is_stock_at_or_below_rop(): void
    {
        $this->assertFalse($this->call('isRestockAlert', 150.0, 38.68), 'Dataset 1: stock above ROP, no alert');
        $this->assertTrue($this->call('isRestockAlert', 38.68, 38.68));
        $this->assertTrue($this->call('isRestockAlert', 10.0, 38.68));
    }

    // ═══ Panel: EOQ/ROP inputs and weekly-to-daily recording ══════════════

    public function test_eoq_and_rop_from_the_daily_series(): void
    {
        $daily = $this->daily('ARIMA-DEMO-TREND');
        $this->assertEqualsWithDelta(3144, array_sum($daily), 1e-9, 'weekly totals land on one Wednesday each');

        $m = $this->call('inventoryMetrics', $daily, 600.0);           // unit cost PHP 600
        $this->assertEqualsWithDelta(3144 / 1460, $m['avg_daily'], 1e-9);
        $this->assertEqualsWithDelta(80.93, $m['eoq'], 0.01);
        $this->assertEqualsWithDelta(38.68, $m['rop'], 0.01);
    }

    // ═══ Panel: FSN over real history (Equation 1) ════════════════════════

    public function test_fsn_uses_the_products_real_sales_history(): void
    {
        $settings = new AppSetting();
        $settings->fsn_fast_threshold_percent      = 50;
        $settings->fsn_nonmoving_threshold_percent = 10;
        $settings->fsn_dead_stock_weeks            = 26;
        $settings->fsn_fast_min_daily_avg          = 1.0;

        $fsn = $this->call('classifyFSN', $this->padded('ARIMA-DEMO-RECENT'), 0.0, '2026-10-09', $settings);

        $this->assertSame(50, $fsn['total_weeks'], '50 real weeks, not the 208-week window');
        $this->assertSame(50, $fsn['active_weeks']);
        $this->assertEqualsWithDelta(1.0, $fsn['activity_ratio'], 1e-9);
        $this->assertSame('fast', $fsn['classification']);
    }

    // ═══ Panel: Objective 3 - accuracy (WMAPE) ═══════════════════════════

    public function test_wmape_is_not_reported_below_36_periods_of_real_history(): void
    {
        $wm = $this->call('computeWMAPE', $this->padded('ARIMA-DEMO-RECENT'), 'auto_arima', $this->weeklySince());
        $this->assertSame(-1.0, $wm['wmape'], 'Dataset 4 has only 50 weeks (12 periods) -> N/A');
        $this->assertSame([], $wm['holdout']);
    }

    public function test_wmape_holdout_is_traceable_to_the_raw_data(): void
    {
        $expected = ['ARIMA-DEMO-TREND' => 2.59, 'ARIMA-DEMO-STABLE' => 9.46, 'ARIMA-DEMO-SHIFT' => 25.32];

        foreach ($expected as $sku => $wmape) {
            $series = $this->padded($sku);
            $wm     = $this->call('computeWMAPE', $series, 'auto_arima', $this->weeklySince());

            $this->assertEqualsWithDelta($wmape, $wm['wmape'], 0.05, $sku);
            $this->assertCount(12, $wm['holdout']);

            $abs = $act = 0.0;
            foreach ($wm['holdout'] as $i => $row) {
                $days = (new \DateTimeImmutable($row['from']))->diff(new \DateTimeImmutable($row['to']))->days + 1;
                $this->assertSame(28, $days, "$sku period " . ($i + 1) . ' is 4 full weeks');
                $this->assertSame('Monday', (new \DateTimeImmutable($row['from']))->format('l'));

                // actual = the raw weekly sales of exactly those four weeks
                $from = (int) round(((new \DateTimeImmutable($row['from']))->getTimestamp() - $this->monday()->getTimestamp()) / 604800);
                $this->assertEqualsWithDelta(array_sum(array_slice($series, $from, 4)), $row['actual'], 1e-9, "$sku period " . ($i + 1));

                $abs += abs($row['actual'] - $row['forecast']);
                $act += $row['actual'];
            }
            $this->assertEqualsWithDelta($wmape, $abs / $act * 100, 0.05, 'WMAPE = sum|A-F| / sum A');
            $this->assertSame('2026-10-11', end($wm['holdout'])['to'], 'last period ends on the last completed Sunday');
        }
    }

    public function test_accuracy_drops_after_a_sudden_change_in_demand(): void
    {
        $w = fn($sku) => $this->call('computeWMAPE', $this->padded($sku), 'auto_arima', $this->weeklySince())['wmape'];
        $this->assertGreaterThan($w('ARIMA-DEMO-TREND'),  $w('ARIMA-DEMO-SHIFT'));
        $this->assertGreaterThan($w('ARIMA-DEMO-STABLE'), $w('ARIMA-DEMO-SHIFT'));
    }

    // ═══ Robustness ═══════════════════════════════════════════════════════

    public function test_odd_series_never_raise_errors(): void
    {
        mt_srand(11);
        $cases = [array_fill(0, 208, 0.0), array_fill(0, 208, 5.0), array_pad([3.0], -208, 0.0)];
        for ($k = 0; $k < 25; $k++) {
            $p = mt_rand(5, 100) / 100;
            $s = [];
            for ($i = 0; $i < 208; $i++) $s[] = (float) (mt_rand(0, 100) / 100 < $p ? mt_rand(1, 12) : 0);
            $cases[] = $s;
        }
        foreach ($cases as $s) {
            $r = $this->call('forecast', $s, $this->call('selectMethod', $s), 24);
            $this->assertCount(24, $r['forecast']);
            foreach ($r['forecast'] as $f) {
                $this->assertFalse(is_nan($f) || is_infinite($f));
                $this->assertGreaterThanOrEqual(0.0, $f);
            }
        }
    }
}