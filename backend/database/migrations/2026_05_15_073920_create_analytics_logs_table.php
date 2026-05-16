<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('method', ['arima', 'eoq', 'rop', 'fsn']);
            $table->date('computed_at');
            $table->json('result_data');
            $table->decimal('predicted_demand', 10, 2)->nullable();
            $table->decimal('eoq_value', 10, 2)->nullable();
            $table->decimal('rop_value', 10, 2)->nullable();
            $table->string('fsn_classification')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_logs');
    }
};
