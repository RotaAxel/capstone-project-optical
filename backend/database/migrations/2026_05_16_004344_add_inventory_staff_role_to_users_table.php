<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','receptionist','optometrist','inventory_staff') NOT NULL DEFAULT 'receptionist'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','receptionist','optometrist') NOT NULL DEFAULT 'receptionist'");
    }
};
