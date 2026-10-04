<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The optometrist sets a professional/exam fee on the prescription itself;
     * when a receptionist later processes a sale against that prescription,
     * the fee is carried over onto the sale (read from the prescription
     * server-side — never trusted from the client — see SaleController::store()).
     */
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->decimal('fee', 10, 2)->default(0)->after('valid_until');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('prescription_fee', 10, 2)->default(0)->after('prescription_id');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('fee');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('prescription_fee');
        });
    }
};
