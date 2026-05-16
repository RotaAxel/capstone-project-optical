<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Patient;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Prescription;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $cats = ProductCategory::insert([
            ['name' => 'Eyeglass Frames',    'type' => 'frame',     'description' => 'Prescription eyeglass frames',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Single Vision Lens', 'type' => 'lens',      'description' => 'Single vision lenses',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Progressive Lens',   'type' => 'lens',      'description' => 'Progressive / bifocal lenses',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Contact Lens',       'type' => 'lens',      'description' => 'Soft contact lenses',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sunglasses',         'type' => 'frame',     'description' => 'Polarized & fashion sunglasses','created_at' => now(), 'updated_at' => now()],
            ['name' => 'Accessories',        'type' => 'accessory', 'description' => 'Cases, cleaners, chains',       'created_at' => now(), 'updated_at' => now()],
        ]);
        [$cFrames, $cSV, $cProg, $cCL, $cSun, $cAcc] = ProductCategory::orderBy('id')->pluck('id')->toArray();

        // ── Suppliers ─────────────────────────────────────────────────────────
        [$sup1, $sup2] = tap(Supplier::insert([
            ['name' => 'OpticalPro Supply Co.', 'contact_person' => 'Juan dela Cruz', 'phone' => '09171234567', 'email' => 'supply@opticalpro.ph', 'address' => 'Manila',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'LensWorld Philippines', 'contact_person' => 'Maria Santos',   'phone' => '09281234567', 'email' => 'info@lensworld.ph',    'address' => 'Cebu',    'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]), fn() => null) ? Supplier::orderBy('id')->pluck('id')->toArray() : [];
        [$sup1, $sup2] = Supplier::orderBy('id')->pluck('id')->toArray();

        // ── Products ──────────────────────────────────────────────────────────
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
        $productList = Product::orderBy('id')->get();

        // ── Patients ──────────────────────────────────────────────────────────
        $patientData = [
            ['first_name'=>'Juan',    'last_name'=>'Dela Cruz',   'date_of_birth'=>'1985-03-15','gender'=>'male',  'phone'=>'09171111001','email'=>'juan@email.com',   'address'=>'123 Rizal St, Cebu City'],
            ['first_name'=>'Maria',   'last_name'=>'Santos',      'date_of_birth'=>'1990-07-22','gender'=>'female','phone'=>'09181111002','email'=>'maria@email.com',  'address'=>'456 Mabini Ave, Cebu City'],
            ['first_name'=>'Pedro',   'last_name'=>'Reyes',       'date_of_birth'=>'1978-11-08','gender'=>'male',  'phone'=>'09191111003','email'=>'pedro@email.com',  'address'=>'789 Osmena Blvd, Cebu City'],
            ['first_name'=>'Ana',     'last_name'=>'Garcia',      'date_of_birth'=>'1995-05-30','gender'=>'female','phone'=>'09201111004','email'=>'ana@email.com',    'address'=>'321 Colon St, Cebu City'],
            ['first_name'=>'Carlos',  'last_name'=>'Lopez',       'date_of_birth'=>'1982-09-14','gender'=>'male',  'phone'=>'09211111005','email'=>'carlos@email.com', 'address'=>'654 General Maxilom, Cebu City'],
            ['first_name'=>'Rosa',    'last_name'=>'Mendoza',     'date_of_birth'=>'1970-02-28','gender'=>'female','phone'=>'09221111006','email'=>'rosa@email.com',   'address'=>'987 Jones Ave, Cebu City'],
            ['first_name'=>'Miguel',  'last_name'=>'Fernandez',   'date_of_birth'=>'1988-12-03','gender'=>'male',  'phone'=>'09231111007','email'=>'miguel@email.com', 'address'=>'147 Sanciangko St, Cebu City'],
            ['first_name'=>'Luz',     'last_name'=>'Villanueva',  'date_of_birth'=>'1993-06-19','gender'=>'female','phone'=>'09241111008','email'=>'luz@email.com',    'address'=>'258 P. Del Rosario, Cebu City'],
            ['first_name'=>'Roberto', 'last_name'=>'Aquino',      'date_of_birth'=>'1975-04-11','gender'=>'male',  'phone'=>'09251111009','email'=>'roberto@email.com','address'=>'369 Kamagayan, Cebu City'],
            ['first_name'=>'Elena',   'last_name'=>'Torres',      'date_of_birth'=>'2001-08-25','gender'=>'female','phone'=>'09261111010','email'=>'elena@email.com',  'address'=>'741 Urgello St, Cebu City'],
        ];
        foreach ($patientData as $i => &$p) {
            $p['patient_code'] = 'PT-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $p['created_by']   = $recep->id;
            $p['created_at']   = now()->subDays(rand(30, 180));
            $p['updated_at']   = now();
        }
        Patient::insert($patientData);
        $patients = Patient::orderBy('id')->get();

        // ── Prescriptions ─────────────────────────────────────────────────────
        foreach ($patients->take(8) as $patient) {
            Prescription::create([
                'patient_id'    => $patient->id,
                'optometrist_id'=> $opto->id,
                'exam_date'     => now()->subDays(rand(10, 90))->toDateString(),
                'valid_until'   => now()->addYear()->toDateString(),
                'od_sphere'     => -round(rand(25, 300) / 100, 2),
                'od_cylinder'   => -round(rand(0, 150) / 100, 2),
                'od_axis'       => rand(0, 180),
                'od_pd'         => round(rand(30, 34) / 1, 1),
                'os_sphere'     => -round(rand(25, 300) / 100, 2),
                'os_cylinder'   => -round(rand(0, 150) / 100, 2),
                'os_axis'       => rand(0, 180),
                'os_pd'         => round(rand(30, 34) / 1, 1),
                'notes'         => 'Regular prescription check.',
            ]);
        }

        // ── Appointments ──────────────────────────────────────────────────────
        $apptTypes    = ['eye_exam','follow_up','fitting','other'];
        $apptStatuses = ['scheduled','completed','completed','completed','cancelled'];
        foreach ($patients as $i => $patient) {
            Appointment::create([
                'patient_id'       => $patient->id,
                'optometrist_id'   => $opto->id,
                'created_by'       => $recep->id,
                'appointment_date' => now()->subDays(rand(0, 60))->setHour(rand(8, 16))->setMinute(0),
                'type'             => $apptTypes[$i % count($apptTypes)],
                'status'           => $apptStatuses[$i % count($apptStatuses)],
                'notes'            => 'Routine visit.',
                'created_at'       => now()->subDays(rand(1, 65)),
                'updated_at'       => now(),
            ]);
        }

        // ── Sales History (90 days) for Analytics ─────────────────────────────
        // Fast-moving: FR-001, FR-003, LN-001, LN-004 → daily sales
        // Slow-moving: CL-001, SG-001 → weekly
        // Non-moving: AC-003 → no recent sales
        $fastProducts = $productList->whereIn('sku', ['FR-001','FR-003','LN-001','LN-004'])->values();
        $slowProducts = $productList->whereIn('sku', ['CL-001','SG-001','FR-002'])->values();
        $saleCount    = 0;

        for ($day = 90; $day >= 1; $day--) {
            $date = now()->subDays($day);

            // 2-4 sales per day on fast products
            $dailySales = rand(2, 4);
            for ($s = 0; $s < $dailySales; $s++) {
                $patient  = $patients->random();
                $product  = $fastProducts->random();
                $qty      = rand(1, 3);
                $subtotal = $product->selling_price * $qty;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad(++$saleCount, 5, '0', STR_PAD_LEFT),
                    'patient_id'      => $patient->id,
                    'cashier_id'      => $recep->id,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount'      => 0,
                    'total_amount'    => $subtotal,
                    'amount_paid'     => $subtotal,
                    'change_amount'   => 0,
                    'payment_method'  => collect(['cash','gcash','card'])->random(),
                    'status'          => 'completed',
                    'created_at'      => $date,
                    'updated_at'      => $date,
                ]);
                SaleItem::create([
                    'sale_id'    => $sale->id, 'product_id' => $product->id,
                    'quantity'   => $qty,      'unit_price'  => $product->selling_price,
                    'discount'   => 0,         'subtotal'    => $subtotal,
                    'created_at' => $date,     'updated_at'  => $date,
                ]);
            }

            // Slow products: every 7 days
            if ($day % 7 === 0) {
                $product  = $slowProducts->random();
                $qty      = rand(1, 2);
                $subtotal = $product->selling_price * $qty;
                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad(++$saleCount, 5, '0', STR_PAD_LEFT),
                    'patient_id'      => $patients->random()->id,
                    'cashier_id'      => $recep->id,
                    'subtotal'        => $subtotal, 'discount_amount'=>0, 'tax_amount'=>0,
                    'total_amount'    => $subtotal, 'amount_paid'=>$subtotal, 'change_amount'=>0,
                    'payment_method'  => 'cash', 'status'=>'completed',
                    'created_at'      => $date, 'updated_at'=>$date,
                ]);
                SaleItem::create([
                    'sale_id'=>$sale->id,'product_id'=>$product->id,'quantity'=>$qty,
                    'unit_price'=>$product->selling_price,'discount'=>0,'subtotal'=>$subtotal,
                    'created_at'=>$date,'updated_at'=>$date,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('✓ Seeded: 3 users, ' . count($patientData) . ' patients, ' . count($products) . ' products, ' . $saleCount . ' sales over 90 days.');
    }
}
