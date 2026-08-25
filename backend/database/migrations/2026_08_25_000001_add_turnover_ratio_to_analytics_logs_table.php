<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_logs', function (Blueprint $table) {
            $table->decimal('turnover_ratio', 10, 2)->nullable()->after('fsn_classification');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_logs', function (Blueprint $table) {
            $table->dropColumn('turnover_ratio');
        });
    }
};
