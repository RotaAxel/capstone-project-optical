<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE analytics_logs
            MODIFY COLUMN method
            ENUM('arima','holt_winters','auto_arima','croston','ses','eoq','rop','fsn')
            NOT NULL
        ");
    }

    public function down(): void
    {
        // Rows with new method values must be removed before reverting
        DB::statement("DELETE FROM analytics_logs WHERE method NOT IN ('arima','eoq','rop','fsn')");
        DB::statement("
            ALTER TABLE analytics_logs
            MODIFY COLUMN method
            ENUM('arima','eoq','rop','fsn')
            NOT NULL
        ");
    }
};
