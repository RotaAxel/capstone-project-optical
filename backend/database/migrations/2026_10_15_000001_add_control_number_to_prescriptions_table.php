<?php

use App\Models\Prescription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('control_number', 30)->nullable()->unique()->after('id');
        });

        // Give every existing prescription its number (by exam date, then entry order).
        Prescription::assignMissingControlNumbers();
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropUnique(['control_number']);
            $table->dropColumn('control_number');
        });
    }
};
