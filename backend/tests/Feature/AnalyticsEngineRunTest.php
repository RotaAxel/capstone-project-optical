<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AnalyticsController;
use App\Models\AnalyticsLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Replays a synthetic, ARIMA-fit weekly demand series as real sale_items rows
 * and runs it through the actual AnalyticsController::run() pipeline — the
 * same code path the "Run Analytics" button hits — to confirm the engine
 * produces a coherent end-to-end result (method selection, EOQ, ROP, FSN,
 * the 6-month forecast and its confidence band) rather than just testing the
 * ARIMA math in isolation (see tests/Unit/ArimaForecastingTest.php).
 */
class AnalyticsEngineRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_produces_a_coherent_forecast_for_a_dense_trending_product(): void
    {
        $category = ProductCategory::create(['name' => 'Frames', 'type' => 'frame']);
        $cashier  = User::factory()->create(['role' => 'receptionist']);

        $product = Product::create([
            'sku'              => 'TST-ARIMA-001',
            'name'             => 'Test Trending Frame',
            'category_id'      => $category->id,
            'cost_price'       => 500,
            'selling_price'    => 1200,
            'stock_quantity'   => 200,
            'reorder_point'    => 10,
            'reorder_quantity' => 30,
        ]);

        // Same shape as ArimaForecastingTest's dataset, but spanning the full
        // weekWindow (208 weeks) so it lines up with how buildWeeklySeriesFromAgg()
        // actually builds a product's series in run() — every week gets a slot,
        // zero or not.
        $weeks     = 208;
        $weekStart = now()->subWeeks($weeks)->startOfWeek();

        for ($t = 0; $t < $weeks; $t++) {
            $qty = (int) round(max(0.0, 10 + 0.05 * $t + 2.0 * sin($t * 0.3)));
            if ($qty <= 0) continue;

            $date = $weekStart->copy()->addWeeks($t)->addDays(2);

            $sale = Sale::create([
                'receipt_number' => 'TST-' . $t . '-' . uniqid(),
                'cashier_id'     => $cashier->id,
                'subtotal'       => $qty * 1200,
                'total_amount'   => $qty * 1200,
                'amount_paid'    => $qty * 1200,
                'payment_method' => 'cash',
                'status'         => 'completed',
            ]);
            $sale->timestamps = false;
            $sale->created_at = $date;
            $sale->updated_at = $date;
            $sale->save();

            $item = SaleItem::create([
                'sale_id'    => $sale->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_price' => 1200,
                'discount'   => 0,
                'subtotal'   => $qty * 1200,
            ]);
            $item->timestamps = false;
            $item->created_at = $date;
            $item->updated_at = $date;
            $item->save();
        }

        $response = (new AnalyticsController())->run();
        $this->assertSame(200, $response->getStatusCode());

        $log = AnalyticsLog::where('product_id', $product->id)->first();
        $this->assertNotNull($log, 'Expected an AnalyticsLog row to be created for the seeded product.');

        // Auto-ARIMA is the system's primary forecasting method — a dense,
        // regularly-selling product like this one should get Auto-ARIMA.
        $this->assertSame('auto_arima', $log->method);

        // EOQ / ROP: should be positive, finite numbers for a steadily-selling product.
        $this->assertGreaterThan(0, (float) $log->eoq_value);
        $this->assertGreaterThan(0, (float) $log->rop_value);

        // FSN: a product selling almost every week should not be classified non-moving.
        $this->assertContains($log->fsn_classification, ['fast', 'slow', 'non_moving']);
        $this->assertNotSame('non_moving', $log->fsn_classification);

        // Forecast: 6 monthly buckets, each within its own confidence band, all non-negative.
        $rd = $log->result_data;
        $this->assertCount(6, $rd['forecast_monthly']);
        foreach ($rd['forecast_monthly'] as $i => $forecast) {
            $this->assertGreaterThanOrEqual(0, $forecast);
            $this->assertGreaterThanOrEqual($rd['conf_lower_monthly'][$i] - 0.01, $forecast);
            $this->assertLessThanOrEqual($rd['conf_upper_monthly'][$i] + 0.01, $forecast);
        }

        // Predicted 30-day demand should be a positive, finite figure derived
        // from the same forecast (not a leftover/placeholder zero).
        $this->assertGreaterThan(0, (float) $log->predicted_demand);

        // ...and it is exactly the first 4 forecast weeks (28 days) scaled to 30
        // days. forecast_monthly[0] is that same 4-week sum, rounded to 2 dp.
        $this->assertEqualsWithDelta($rd['forecast_monthly'][0] * 30 / 28, (float) $log->predicted_demand, 0.02);
    }
}
