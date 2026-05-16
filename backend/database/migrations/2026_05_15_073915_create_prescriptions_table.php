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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('optometrist_id')->constrained('users');
            $table->decimal('od_sphere', 5, 2)->nullable();
            $table->decimal('od_cylinder', 5, 2)->nullable();
            $table->decimal('od_axis', 5, 2)->nullable();
            $table->decimal('od_add', 5, 2)->nullable();
            $table->decimal('od_pd', 5, 2)->nullable();
            $table->decimal('os_sphere', 5, 2)->nullable();
            $table->decimal('os_cylinder', 5, 2)->nullable();
            $table->decimal('os_axis', 5, 2)->nullable();
            $table->decimal('os_add', 5, 2)->nullable();
            $table->decimal('os_pd', 5, 2)->nullable();
            $table->decimal('visual_acuity_od', 5, 2)->nullable();
            $table->decimal('visual_acuity_os', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('exam_date');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
