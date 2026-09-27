<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Singleton table — always exactly one row (see AppSetting::current()).
     * Holds the admin-configurable thresholds that used to be hardcoded in
     * AlertController (low-stock sensitivity) and AnalyticsController (FSN
     * classification cutoffs).
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();

            // Alerts: how early the "Low Stock" alert fires, as a percentage
            // of each product's own reorder_point. 100 = fires exactly at the
            // reorder point (today's original behavior); 150 fires while
            // stock is still 50% above it, for more lead time. Out-of-stock
            // alerts (stock_quantity = 0) are never affected by this.
            $table->unsignedInteger('low_stock_sensitivity_percent')->default(100);

            // FSN classification (AnalyticsController::classifyFSN).
            // A product is "fast" once its activity ratio (share of weeks
            // with a sale, over the analysis window) reaches this percent —
            // or its average daily sales reach fsn_fast_min_daily_avg,
            // whichever comes first.
            $table->unsignedTinyInteger('fsn_fast_threshold_percent')->default(50);

            // Below this activity-ratio percent, a product is "non_moving"
            // (dead stock) regardless of the weeks-unsold rule below.
            $table->unsignedTinyInteger('fsn_nonmoving_threshold_percent')->default(10);

            // A product with no sale in this many consecutive weeks is
            // classified "non_moving" regardless of its activity ratio.
            // Default ~26 weeks mirrors the original hardcoded "6 months".
            $table->unsignedInteger('fsn_dead_stock_weeks')->default(26);

            // Average daily units sold at/above which a product is always
            // classified "fast", regardless of the activity-ratio threshold.
            $table->decimal('fsn_fast_min_daily_avg', 8, 2)->default(1.00);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
