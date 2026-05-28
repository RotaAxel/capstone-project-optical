<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ── Users ──────────────────────────────────────────────────────────────
        $admin = User::create(['name' => 'Admin',        'email' => 'admin@acebedo.com',       'password' => Hash::make('password'), 'role' => 'admin',        'phone' => '09171000001']);
        $recep = User::create(['name' => 'Receptionist', 'email' => 'reception@acebedo.com',   'password' => Hash::make('password'), 'role' => 'receptionist', 'phone' => '09171000002']);
        $opto  = User::create(['name' => 'Dr. Santos',   'email' => 'optometrist@acebedo.com', 'password' => Hash::make('password'), 'role' => 'optometrist',  'phone' => '09171000003']);

        // ── Product Categories ─────────────────────────────────────────────────
        ProductCategory::insert([
            ['name' => 'Eyeglass Frames',    'type' => 'frame',     'description' => 'Prescription eyeglass frames',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Single Vision Lens', 'type' => 'lens',      'description' => 'Single vision lenses',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Progressive Lens',   'type' => 'lens',      'description' => 'Progressive / bifocal lenses',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Contact Lens',       'type' => 'lens',      'description' => 'Soft contact lenses',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sunglasses',         'type' => 'frame',     'description' => 'Polarized & fashion sunglasses','created_at' => now(), 'updated_at' => now()],
            ['name' => 'Accessories',        'type' => 'accessory', 'description' => 'Cases, cleaners, chains',       'created_at' => now(), 'updated_at' => now()],
        ]);
        [$cFrames, $cSV, $cProg, $cCL, $cSun, $cAcc] = ProductCategory::orderBy('id')->pluck('id')->toArray();

        // ── Suppliers ─────────────────────────────────────────────────────────
        Supplier::insert([
            ['name' => 'OpticalPro Supply Co.', 'contact_person' => 'Juan dela Cruz', 'phone' => '09171234567', 'email' => 'supply@opticalpro.ph', 'address' => 'Manila',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'LensWorld Philippines', 'contact_person' => 'Maria Santos',   'phone' => '09281234567', 'email' => 'info@lensworld.ph',    'address' => 'Cebu',    'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        [$sup1, $sup2] = Supplier::orderBy('id')->pluck('id')->toArray();

        // ── Core Base Products (Required by other Seeders) ────────────────────
        $products = [
            // Fast-moving frames
            ['sku'=>'FR-001','name'=>'Classic Metal Frame Silver',  'category_id'=>$cFrames,'supplier_id'=>$sup1,'brand'=>'OptiStyle','cost_price'=>450,'selling_price'=>1200,'stock_quantity'=>32,'reorder_point'=>10,'reorder_quantity'=>30],
            ['sku'=>'FR-002','name'=>'Lightweight Titanium Frame',  'category_id'=>$cFrames,'supplier_id'=>$sup1,'brand'=>'TitanVision','cost_price'=>900,'selling_price'=>2500,'stock_quantity'=>18,'reorder_point'=>8, 'reorder_quantity'=>20],
            ['sku'=>'FR-003','name'=>'Round Acetate Frame Black',   'category_id'=>$cFrames,'supplier_id'=>$sup1,'brand'=>'AceFrame','cost_price'=>380,'selling_price'=>980,'stock_quantity'=>25,'reorder_point'=>10,'reorder_quantity'=>25],
            ['sku'=>'FR-004','name'=>'Kids Flexible Frame Blue',    'category_id'=>$cFrames,'supplier_id'=>$sup1,'brand'=>'KiddoVision','cost_price'=>300,'selling_price'=>750,'stock_quantity'=>15,'reorder_point'=>8, 'reorder_quantity'=>20],
            ['sku'=>'FR-005','name'=>'Cat-eye Fashion Frame',       'category_id'=>$cFrames,'supplier_id'=>$sup1,'brand'=>'Glamour','cost_price'=>420,'selling_price'=>1100,'stock_quantity'=>12,'reorder_point'=>6, 'reorder_quantity'=>15],
            // Lenses — fast moving
            ['sku'=>'LN-001','name'=>'Single Vision 1.50 Clear',   'category_id'=>$cSV,    'supplier_id'=>$sup2,'brand'=>'LensWorld','cost_price'=>180,'selling_price'=>450,'stock_quantity'=>60,'reorder_point'=>20,'reorder_quantity'=>50],
            ['sku'=>'LN-002','name'=>'Single Vision 1.67 Thin',    'category_id'=>$cSV,    'supplier_id'=>$sup2,'brand'=>'LensWorld','cost_price'=>350,'selling_price'=>850,'stock_quantity'=>40,'reorder_point'=>15,'reorder_quantity'=>40],
            ['sku'=>'LN-003','name'=>'Progressive Essilor 1.60',   'category_id'=>$cProg,  'supplier_id'=>$sup2,'brand'=>'Essilor','cost_price'=>1200,'selling_price'=>3200,'stock_quantity'=>20,'reorder_point'=>8, 'reorder_quantity'=>20],
            ['sku'=>'LN-004','name'=>'Anti-Radiation Coating SV',  'category_id'=>$cSV,    'supplier_id'=>$sup2,'brand'=>'RadiGuard','cost_price'=>220,'selling_price'=>550,'stock_quantity'=>45,'reorder_point'=>15,'reorder_quantity'=>40],
            // Contact lenses — slow moving
            ['sku'=>'CL-001','name'=>'Daily Disposable Clear 30pk','category_id'=>$cCL,    'supplier_id'=>$sup2,'brand'=>'FreshLook','cost_price'=>320,'selling_price'=>780,'stock_quantity'=>25,'reorder_point'=>10,'reorder_quantity'=>20],
            ['sku'=>'CL-002','name'=>'Monthly Soft Lens -2.50',    'category_id'=>$cCL,    'supplier_id'=>$sup2,'brand'=>'Acuvue','cost_price'=>280,'selling_price'=>650,'stock_quantity'=>18,'reorder_point'=>8, 'reorder_quantity'=>15],
            // Sunglasses — slow moving
            ['sku'=>'SG-001','name'=>'Polarized Aviator Sunglasses','category_id'=>$cSun,  'supplier_id'=>$sup1,'brand'=>'SunShade','cost_price'=>600,'selling_price'=>1500,'stock_quantity'=>10,'reorder_point'=>5, 'reorder_quantity'=>10],
            ['sku'=>'SG-002','name'=>'Wayfarer UV400 Sunglasses',  'category_id'=>$cSun,   'supplier_id'=>$sup1,'brand'=>'SunShade','cost_price'=>500,'selling_price'=>1200,'stock_quantity'=>8, 'reorder_point'=>4, 'reorder_quantity'=>10],
            // Accessories — non-moving
            ['sku'=>'AC-001','name'=>'Hard Shell Case Black',      'category_id'=>$cAcc,   'supplier_id'=>$sup1,'brand'=>'SafeKeep','cost_price'=>80,'selling_price'=>180,'stock_quantity'=>30,'reorder_point'=>10,'reorder_quantity'=>20],
            ['sku'=>'AC-002','name'=>'Microfiber Cleaning Cloth',  'category_id'=>$cAcc,   'supplier_id'=>$sup1,'brand'=>'CleanVision','cost_price'=>20,'selling_price'=>50,'stock_quantity'=>100,'reorder_point'=>30,'reorder_quantity'=>50],
            ['sku'=>'AC-003','name'=>'Lens Cleaning Solution 50ml','category_id'=>$cAcc,   'supplier_id'=>$sup1,'brand'=>'CleanVision','cost_price'=>45,'selling_price'=>120,'stock_quantity'=>40,'reorder_point'=>15,'reorder_quantity'=>30],
        ];

        foreach ($products as &$p) {
            $p['model'] = null; $p['color'] = null; $p['size'] = null;
            $p['description'] = null; $p['is_active'] = true;
            $p['created_at'] = now(); $p['updated_at'] = now();
        }
        Product::insert($products);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->command->info('✓ Core Users, Categories, Suppliers, and Base Products seeded.');

        // ── Call External Seeders ──────────────────────────────────────────────
        // The order below is critical to maintain foreign key integrity
        $this->call([
            ProductInventorySeeder::class,      // Adds the 500+ generated products
            PatientHistorySeeder::class,        // Generates patients and initial appointments
            HistoricalTransactionSeeder::class, // Runs the 1460-day 4-year loop
        ]);
    }
}