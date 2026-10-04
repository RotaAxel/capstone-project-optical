<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientHistorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $recep = User::where('role', 'receptionist')->first() ?? User::where('role', 'admin')->first();
        $opto  = User::where('role', 'optometrist')->first()  ?? User::where('role', 'admin')->first();

        // Determine next patient code offset
        $lastCode = Patient::orderByDesc('id')->value('patient_code') ?? 'PT-0000';
        preg_match('/(\d+)$/', $lastCode, $m);
        $codeOffset = (int)($m[1] ?? 0);

        // ── 30 patients ────────────────────────────────────────────────────────
        $rows = [
            // name, dob, gender, phone, email, address, months_ago_registered
            ['Andres',    'Bautista',    '1980-04-12','male',  '09171230011','andres.bautista@gmail.com',   'Lahug, Cebu City',                  14],
            ['Corazon',   'Navarro',     '1974-09-27','female','09181230012','cora.navarro@yahoo.com',      'Mabolo, Cebu City',                 13],
            ['Ferdinand', 'Cruz',        '1992-01-05','male',  '09191230013','ferd.cruz@gmail.com',         'Mandaue City',                      13],
            ['Sheila',    'Ramos',       '1998-06-18','female','09201230014','sheila.ramos@gmail.com',      'Talamban, Cebu City',               12],
            ['Reynaldo',  'Uy',          '1967-11-30','male',  '09211230015','rey.uy@hotmail.com',          'Carbon, Cebu City',                 12],
            ['Maricel',   'Tan',         '1989-03-08','female','09221230016','maricel.tan@gmail.com',       'Banilad, Cebu City',                11],
            ['Eduardo',   'Lim',         '1976-07-21','male',  '09231230017','eduardo.lim@yahoo.com',       'IT Park, Cebu City',                11],
            ['Cristina',  'Ong',         '2000-12-14','female','09241230018','cristina.ong@gmail.com',      'Mactan, Lapu-Lapu City',            10],
            ['Dante',     'Flores',      '1963-05-03','male',  '09251230019','dante.flores@gmail.com',      'Talisay City',                      10],
            ['Lorraine',  'Herrera',     '1995-08-26','female','09261230020','lorraine.herrera@gmail.com',  'North Reclamation, Cebu City',       9],
            ['Rodolfo',   'Castillo',    '1971-02-17','male',  '09271230021','rodo.castillo@yahoo.com',     'Punta Princesa, Cebu City',          9],
            ['Marites',   'Soriano',     '1987-10-09','female','09281230022','marites.soriano@gmail.com',   'Guadalupe, Cebu City',               8],
            ['Ernesto',   'Villalba',    '1958-06-25','male',  '09291230023','ernesto.villalba@gmail.com',  'Sambag, Cebu City',                  8],
            ['Jennalyn',  'Bacalso',     '2002-04-01','female','09301230024','jennalyn.bacalso@gmail.com',  'Talamban, Cebu City',                7],
            ['Nestor',    'Abella',      '1969-09-15','male',  '09311230025','nestor.abella@yahoo.com',     'Basak, Mandaue City',                7],
            ['Patricia',  'Cabrera',     '1991-12-28','female','09321230026','patricia.cabrera@gmail.com',  'Lahug, Cebu City',                   6],
            ['Salvador',  'Magno',       '1983-07-07','male',  '09331230027','salvador.magno@gmail.com',    'Consolacion, Cebu',                  6],
            ['Rowena',    'Peñaflorida', '1977-03-20','female','09341230028','rowena.pena@hotmail.com',     'Mabolo, Cebu City',                  5],
            ['Arnel',     'Cuizon',      '1994-11-11','male',  '09351230029','arnel.cuizon@gmail.com',      'Labangon, Cebu City',                5],
            ['Gloria',    'Montejo',     '1960-08-04','female','09361230030','gloria.montejo@gmail.com',    'Pardo, Cebu City',                   4],
            ['Dennis',    'Escaño',      '1986-01-23','male',  '09371230031','dennis.escano@gmail.com',     'Apas, Cebu City',                    4],
            ['Leonor',    'Pingol',      '1972-05-16','female','09381230032','leonor.pingol@yahoo.com',     'Mandaue City',                       3],
            ['Renato',    'Delos Santos','1979-10-30','male',  '09391230033','renato.delos@gmail.com',      'Talisay City',                       3],
            ['Geraldine', 'Solis',       '1997-02-08','female','09401230034','geraldine.solis@gmail.com',   'IT Park, Cebu City',                 2],
            ['Bonifacio', 'Gaviola',     '1955-07-19','male',  '09411230035','boni.gaviola@gmail.com',      'Carbon, Cebu City',                  2],
            ['Jasmine',   'Alcantara',   '2003-04-05','female','09421230036','jasmine.alcantara@gmail.com', 'Banilad, Cebu City',                 2],
            ['Cesar',     'Dacalos',     '1968-12-22','male',  '09431230037','cesar.dacalos@yahoo.com',     'Lapu-Lapu City',                     1],
            ['Irene',     'Maceda',      '1990-06-14','female','09441230038','irene.maceda@gmail.com',      'Guadalupe, Cebu City',               1],
            ['Raul',      'Tiongson',    '1982-09-01','male',  '09451230039','raul.tiongson@gmail.com',     'Sambag, Cebu City',                  1],
            ['Felicidad', 'Ybañez',      '1965-03-10','female','09461230040','feli.ybanez@gmail.com',       'Punta Princesa, Cebu City',          0],
        ];

        $medHistories = [
            'Nearsighted since childhood, mild astigmatism.',
            'Presbyopia. Uses reading glasses.',
            'History of dry eyes. Prescribed lubricating drops.',
            'No significant ocular history.',
            'Diabetes Type 2. Regular eye monitoring advised.',
            'Family history of glaucoma. Annual pressure check required.',
            'High myopia. Progressive lens recommended.',
            'Post-cataract surgery (left eye). Follow-up required.',
            'Allergic conjunctivitis. Seasonal.',
            'Mild hyperopia. First prescription.',
        ];

        $patientIds = [];
        foreach ($rows as $i => $r) {
            [$first, $last, $dob, $gender, $phone, $email, $address, $monthsAgo] = $r;
            $registeredAt = Carbon::now()->subMonths($monthsAgo)->subDays(rand(0, 25));

            $patient = Patient::create([
                'patient_code'            => 'PT-' . str_pad($codeOffset + $i + 1, 4, '0', STR_PAD_LEFT),
                'first_name'              => $first,
                'last_name'               => $last,
                'date_of_birth'           => $dob,
                'gender'                  => $gender,
                'phone'                   => $phone,
                'email'                   => $email,
                'address'                 => $address,
                'medical_history'         => $medHistories[$i % count($medHistories)],
                'emergency_contact_name'  => 'Contact of ' . $first,
                'emergency_contact_phone' => '094' . rand(10000000, 99999999),
                'created_by'              => $recep->id,
                'created_at'              => $registeredAt,
                'updated_at'              => $registeredAt,
            ]);

            $patientIds[] = ['id' => $patient->id, 'registered' => $registeredAt, 'months_ago' => $monthsAgo];
        }

        // ── Prescriptions ──────────────────────────────────────────────────────
        $spherePool    = [-0.25,-0.50,-0.75,-1.00,-1.25,-1.50,-1.75,-2.00,-2.25,-2.50,-2.75,-3.00,-3.50,-4.00,-4.50,-5.00,+0.25,+0.50,+0.75,+1.00];
        $cylinderPool  = [0,-0.25,-0.50,-0.75,-1.00,-1.25,-1.50,-1.75,-2.00];
        $prescCount    = 0;

        foreach ($patientIds as $idx => $p) {
            $numRx = ($p['months_ago'] >= 8) ? rand(1, 2) : 1;

            for ($rx = 0; $rx < $numRx; $rx++) {
                $daysBack = ($rx === 0 && $p['months_ago'] >= 8)
                    ? rand(180, $p['months_ago'] * 30)
                    : rand(5, min(60, $p['months_ago'] * 30 + 5));

                $examDate = Carbon::now()->subDays($daysBack);

                Prescription::create([
                    'patient_id'     => $p['id'],
                    'optometrist_id' => $opto->id,
                    'exam_date'      => $examDate->toDateString(),
                    'valid_until'    => $examDate->copy()->addYear()->toDateString(),
                    'od_sphere'      => $spherePool[($idx * 3 + $rx) % count($spherePool)],
                    'od_cylinder'    => $cylinderPool[($idx + $rx)   % count($cylinderPool)],
                    'od_axis'        => ($cylinderPool[($idx + $rx) % count($cylinderPool)] != 0) ? rand(5, 175) : 0,
                    'od_pd'          => round(rand(290, 345) / 10, 1),
                    'os_sphere'      => $spherePool[($idx * 2 + $rx + 5) % count($spherePool)],
                    'os_cylinder'    => $cylinderPool[($idx * 2 + $rx)   % count($cylinderPool)],
                    'os_axis'        => ($cylinderPool[($idx * 2 + $rx) % count($cylinderPool)] != 0) ? rand(5, 175) : 0,
                    'os_pd'          => round(rand(290, 345) / 10, 1),
                    'notes'          => $rx === 0 ? 'Initial examination.' : 'Follow-up: slight change in right eye.',
                    'created_at'     => $examDate,
                    'updated_at'     => $examDate,
                ]);
                $prescCount++;
            }
        }

        // ── Appointments ──────────────────────────────────────────────────────
        $apptTypes    = ['eye_exam', 'follow_up', 'fitting', 'other'];
        $apptStatuses = ['completed', 'completed', 'completed', 'cancelled', 'scheduled'];
        $apptCount    = 0;

        foreach ($patientIds as $idx => $p) {
            $numAppts = ($p['months_ago'] >= 6) ? rand(2, 3) : rand(1, 2);

            for ($a = 0; $a < $numAppts; $a++) {
                $isFuture = ($a === $numAppts - 1 && $p['months_ago'] <= 2);
                if ($isFuture) {
                    $apptDate  = Carbon::now()->addDays(rand(3, 21))->setHour(rand(8, 16))->setMinute(0);
                    $status    = 'scheduled';
                } else {
                    $daysBack  = rand(5, min(120, $p['months_ago'] * 30 + 5));
                    $apptDate  = Carbon::now()->subDays($daysBack)->setHour(rand(8, 16))->setMinute(0);
                    $status    = $apptStatuses[($idx + $a) % count($apptStatuses)];
                    if ($status === 'scheduled') $status = 'completed';
                }

                Appointment::create([
                    'patient_id'       => $p['id'],
                    'optometrist_id'   => $opto->id,
                    'created_by'       => $recep->id,
                    'appointment_date' => $apptDate,
                    'type'             => $apptTypes[($idx + $a) % count($apptTypes)],
                    'status'           => $status,
                    'notes'            => 'Routine appointment.',
                    'created_at'       => $apptDate->copy()->subHours(2),
                    'updated_at'       => $apptDate,
                ]);
                $apptCount++;
            }
        }

        // ── Sales linked to patients ───────────────────────────────────────────
        $products     = Product::whereIn('sku', ['FR-001','FR-002','FR-003','LN-001','LN-002','LN-003','LN-004','CL-001','SG-001'])->get();
        $payMethods   = ['cash','cash','cash','gcash','card'];
        $receiptNo    = (int) DB::table('sales')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED)) as max_num")
            ->value('max_num');
        $saleCount    = 0;

        foreach ($patientIds as $idx => $p) {
            // Patients registered longer ago get more sales
            $numSales = ($p['months_ago'] >= 10) ? rand(3, 5)
                      : (($p['months_ago'] >= 5)  ? rand(2, 3)
                      :                             rand(0, 2));

            for ($s = 0; $s < $numSales; $s++) {
                if ($products->isEmpty()) continue;

                $maxDays = max(1, $p['months_ago'] * 30);
                $saleDate = Carbon::now()->subDays(rand(1, $maxDays));
                $product  = $products->values()[($idx + $s) % $products->count()];
                $qty      = rand(1, 2);
                $subtotal = $product->selling_price * $qty;

                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad(++$receiptNo, 5, '0', STR_PAD_LEFT),
                    'patient_id'      => $p['id'],
                    'cashier_id'      => $recep->id,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount'      => 0,
                    'total_amount'    => $subtotal,
                    'amount_paid'     => $subtotal,
                    'change_amount'   => 0,
                    'payment_method'  => $payMethods[($idx + $s) % count($payMethods)],
                    'status'          => 'completed',
                    'created_at'      => $saleDate,
                    'updated_at'      => $saleDate,
                ]);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $product->selling_price,
                    'discount'   => 0,
                    'subtotal'   => $subtotal,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
                $saleCount++;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("✓ Inserted 30 patients, {$prescCount} prescriptions, {$apptCount} appointments, {$saleCount} linked sales.");
    }
}
