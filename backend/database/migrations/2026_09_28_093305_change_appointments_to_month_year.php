<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Appointments no longer carry a specific day/time — the clinic only
     * ever needs "come back in [month] [year]" (an exam booking or a
     * multi-month recall). Replaces the single appointment_date datetime
     * with two plain integers.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedTinyInteger('appointment_month')->nullable()->after('created_by');
            $table->unsignedSmallInteger('appointment_year')->nullable()->after('appointment_month');
        });

        // Backfill from the existing datetime before dropping it.
        DB::statement('UPDATE appointments SET appointment_month = MONTH(appointment_date), appointment_year = YEAR(appointment_date)');

        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedTinyInteger('appointment_month')->nullable(false)->change();
            $table->unsignedSmallInteger('appointment_year')->nullable(false)->change();
            $table->dropColumn('appointment_date');
            $table->index(['appointment_year', 'appointment_month']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dateTime('appointment_date')->nullable()->after('created_by');
        });

        // Best-effort reconstruction: the 1st of the stored month at 9 AM —
        // the original day/time is gone for good, this is only so `down()`
        // leaves a usable column rather than nulls.
        DB::statement("UPDATE appointments SET appointment_date = STR_TO_DATE(CONCAT(appointment_year, '-', appointment_month, '-01 09:00:00'), '%Y-%m-%d %H:%i:%s')");

        Schema::table('appointments', function (Blueprint $table) {
            $table->dateTime('appointment_date')->nullable(false)->change();
            $table->dropIndex(['appointment_year', 'appointment_month']);
            $table->dropColumn(['appointment_month', 'appointment_year']);
        });
    }
};
