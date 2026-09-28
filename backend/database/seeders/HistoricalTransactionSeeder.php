<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalTransactionSeeder extends Seeder
{
    // ── Name pools ────────────────────────────────────────────────────────────

    private array $femaleNames = [
        'Abigail','Agnes','Aileen','Alicia','Ana','Analiza','Angelina','Anita',
        'Annaliza','Beatriz','Caridad','Cecilia','Celia','Charisse','Cindy',
        'Concepcion','Cora','Corazon','Cristina','Daisy','Diana','Dolores',
        'Eden','Eleanor','Elena','Elisa','Elsa','Emma','Erlinda','Esmeralda',
        'Esperanza','Estrella','Eufemia','Evelyn','Faith','Faye','Felicidad',
        'Felisa','Florinda','Geraldine','Gina','Gloria','Grace','Hannah',
        'Helen','Imelda','Irene','Irma','Jade','Jasmine','Jennifer','Jessica',
        'Jennalyn','Joan','Josephine','Juanita','Karen','Katrina','Kristine',
        'Lea','Leila','Leonor','Leonora','Liezl','Lorraine','Lorna','Lourdes',
        'Luisa','Luz','Mae','Maribel','Maricel','Marilou','Marinela','Marian',
        'Marissa','Marites','Marlene','Mary','Melanie','Mercedes','Michelle',
        'Milagros','Monica','Nancy','Natividad','Nenita','Nena','Nina','Norma',
        'Olive','Pamela','Patricia','Paz','Pearl','Perla','Pilar','Racquel',
        'Rebecca','Reina','Remedios','Rica','Rhodora','Riza','Rosa','Rosario',
        'Rowena','Ruby','Sandra','Sarah','Sharon','Sheila','Shirley','Sonia',
        'Stella','Susan','Teresa','Teresita','Tina','Vanessa','Venus','Victoria',
        'Violeta','Vivian','Vivien','Wendy','Wilma','Yvonne','Zenaida',
    ];

    private array $maleNames = [
        'Aaron','Adrian','Alan','Albert','Alejandro','Alex','Alfredo','Allan',
        'Allen','Amado','Andres','Angelo','Anthony','Anton','Ariel','Aris',
        'Arnaldo','Arnold','Arturo','Bernard','Bonifacio','Bryan','Carl','Carlo',
        'Carlos','Cesar','Chester','Christian','Christopher','Clifford',
        'Danilo','Dante','Dario','David','Dennis','Dino','Don','Donnie',
        'Edgar','Edgardo','Edmundo','Eduardo','Efren','Elbert','Elmer',
        'Emilio','Emmanuel','Enrique','Eric','Ernesto','Erwin','Eugene','Evan',
        'Federico','Felix','Fernando','Florencio','Francis','Francisco','Frank',
        'Frederick','Gabriel','Gerardo','Gideon','Gilbert','Glen','Gonzalo',
        'Gregorio','Harry','Hector','Hermogenes','Hilario','Hugo','Ian',
        'Ignacio','Isidro','Ivan','James','Jason','Jay','Jeffrey','Jerome',
        'Jerry','Jimmy','Joel','John','Johnny','Jonathan','Jorge','Jose',
        'Joseph','Josh','Juan','Julian','Justin','Ken','Kenneth','Kevin',
        'Leandro','Leo','Leonardo','Lester','Lloyd','Lorenzo','Luisito',
        'Manuel','Marcelo','Marco','Mariano','Mark','Martin','Marvin',
        'Matthew','Maximo','Max','Michael','Miguel','Narciso','Neil','Nelson',
        'Nicanor','Nicolas','Nilo','Norman','Octavio','Orlando','Oswaldo',
        'Pablo','Patrick','Paul','Pedro','Peter','Philip','Ramon','Randy',
        'Raul','Ray','Renato','Richard','Ricardo','Rob','Roberto','Roderick',
        'Rodolfo','Roel','Roger','Rolando','Romualdo','Ronaldo','Ron','Roy',
        'Rustico','Ryan','Rafael','Salvador','Samuel','Santiago','Sebastian',
        'Sergio','Silvio','Stephen','Steve','Teodoro','Thomas','Tito','Tomas',
        'Tony','Troy','Urbano','Vicente','Victor','Virgilio','Vincent',
        'Warren','Wayne','Wilson',
    ];

    private array $lastNames = [
        'Abella','Aguilar','Alcantara','Alvarez','Aquino','Araneta','Ariola',
        'Bacalso','Baluyot','Bautista','Bello','Buenaventura','Cabrera','Cadiz',
        'Canlas','Capuno','Castillo','Catacutan','Centeno','Corpuz','Cruz',
        'Cuizon','Custodio','Dacalos','Dalisay','David','De Guzman','De Jesus',
        'De Leon','De los Santos','Del Rosario','Dela Cruz','Diaz','Dimayuga',
        'Dizon','Domingo','Dungca','Escano','Espino','Evangelista','Fabian',
        'Fajardo','Fernandez','Flores','Galang','Garcia','Gaviola','Genuino',
        'Gonzales','Gorospe','Guevarra','Guinto','Hernandez','Herrera',
        'Ilagan','Jacinto','Jimenez','Joson','Lacson','Llamas','Lopez',
        'Lorenzo','Lozada','Macapagal','Maceda','Magno','Mangahas','Marcelino',
        'Marasigan','Medina','Mendoza','Mercado','Molina','Montalvo',
        'Montenegro','Morales','Munoz','Navarro','Nieva','Noriega','Ocampo',
        'Olan','Paras','Pascual','Paterno','Perez','Peralta','Pineda','Pingol',
        'Planas','Poblete','Quizon','Ramirez','Ramos','Real','Recio','Reyes',
        'Rivera','Robles','Rodriguez','Roman','Sagun','Salazar','Salcedo',
        'Sanchez','Santos','Soriano','Solis','Sotto','Suarez','Tan','Tiongson',
        'Tolentino','Torres','Trinidad','Uy','Valencia','Valdez','Vargas',
        'Vasquez','Velarde','Vergara','Viray','Villanueva','Villalba',
        'Villafuerte','Villareal','Vinuya','Yap','Zamora',
    ];

    private array $addresses = [
        'Lahug, Cebu City','Mabolo, Cebu City','Banilad, Cebu City',
        'Talamban, Cebu City','IT Park, Cebu City','Apas, Cebu City',
        'Guadalupe, Cebu City','Sambag, Cebu City','Pardo, Cebu City',
        'Labangon, Cebu City','Carbon, Cebu City','Punta Princesa, Cebu City',
        'Basak, Cebu City','Bulacao, Cebu City','Inayawan, Cebu City',
        'Mandaue City','Mactan, Lapu-Lapu City','Talisay City',
        'Consolacion, Cebu','Liloan, Cebu','Minglanilla, Cebu',
        'Naga, Cebu','San Fernando, Cebu','Cordova, Cebu',
        'Danao City','Toledo City','Carcar City','Argao, Cebu',
        'North Reclamation, Cebu City','Kinasang-an, Cebu City',
    ];

    private array $medHistories = [
        'Nearsighted since childhood, mild astigmatism.',
        'Presbyopia. Uses reading glasses.',
        'History of dry eyes. Prescribed lubricating drops.',
        'No significant ocular history.',
        'Diabetes Type 2. Regular eye monitoring advised.',
        'Family history of glaucoma. Annual pressure check required.',
        'High myopia. Progressive lens recommended.',
        'Post-cataract surgery (left eye). Follow-up required.',
        'Allergic conjunctivitis. Seasonal flare-ups.',
        'Mild hyperopia. First prescription.',
        'Referred by family doctor for routine eye check.',
        'Reports occasional blurred vision when reading.',
        'Contact lens wearer. Switching to glasses.',
        'Mild floaters noted. No retinal detachment.',
        'Headaches due to uncorrected vision. New patient.',
        'Previous RLE surgery. Annual monitoring needed.',
        'Convergence insufficiency. Vision therapy recommended.',
        'Color vision deficiency (mild). Advised accordingly.',
        'Ptosis right eye. Referred to ophthalmologist.',
        'Routine annual check-up. No complaints.',
    ];

    private array $rxNotes = [
        'Initial examination. No significant changes.',
        'Follow-up: slight change in right eye.',
        'Annual check-up. Prescription updated.',
        'Patient reports improved vision with new lenses.',
        'Progressive lens prescribed for near work.',
        'Blue-light coating recommended for screen use.',
        'Astigmatism correction adjusted.',
        'Reading prescription only. No distance correction.',
        'First prescription for myopia.',
        'Prescription unchanged. Patient counselled on eye care.',
    ];

    private array $apptReasons = [
        'Annual eye examination',
        'Blurred vision complaint',
        'Headaches and eye strain',
        'Lens fitting appointment',
        'Follow-up after prescription change',
        'Routine check-up',
        'Contact lens evaluation',
        'Post-purchase frame adjustment',
        'Child eye screening',
        'Referred by physician for vision assessment',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('sale_items')->truncate();
        DB::table('sales')->truncate();
        // Re-runnable: drop any 'sale' movements from a previous run of this
        // seeder before regenerating them below, so running it twice never
        // duplicates the ledger.
        DB::table('stock_movements')->where('type', 'sale')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $recep = User::where('role', 'receptionist')->first()
               ?? User::where('role', 'admin')->first();
        $opto  = User::where('role', 'optometrist')->first()
               ?? User::where('role', 'admin')->first();

        // ── Product pools (Pareto-weighted) ───────────────────────────────────
        $corefast = Product::whereIn('sku', [
            'FR-001','FR-002','FR-003','FR-004','FR-005',
            'LN-001','LN-002','LN-003','LN-004',
        ])->get();

        $extFrames = Product::where('sku', 'like', 'EF-%')->inRandomOrder()->limit(30)->get();
        $extSV     = Product::where('sku', 'like', 'SV-%')->inRandomOrder()->limit(20)->get();
        $extProg   = Product::where('sku', 'like', 'PG-%')->inRandomOrder()->limit(15)->get();
        $fastPool  = $corefast->merge($extFrames)->merge($extSV)->merge($extProg)->values();

        $coreSlow = Product::whereIn('sku', ['CL-001','CL-002','SG-001','SG-002'])->get();
        $extCL    = Product::where('sku', 'like', 'CL-%')
                        ->whereNotIn('sku', ['CL-001','CL-002'])
                        ->inRandomOrder()->limit(15)->get();
        $extSG    = Product::where('sku', 'like', 'SG-%')
                        ->whereNotIn('sku', ['SG-001','SG-002'])
                        ->inRandomOrder()->limit(15)->get();
        $slowPool = $coreSlow->merge($extCL)->merge($extSG)->values();
        $accPool  = Product::whereIn('sku', ['AC-001','AC-002','AC-003'])->get()->values();

        if ($fastPool->isEmpty()) {
            $this->command->error('No products found. Run DatabaseSeeder first.');
            return;
        }

        [$fastCum, $totalFast] = $this->buildWeights($fastPool, 0.88);
        [$slowCum, $totalSlow] = $this->buildWeights($slowPool->isEmpty() ? $fastPool : $slowPool, 0.92);

        // ── Add bulk patients to reach 10,000 total ───────────────────────────
        $existingCount = Patient::count();
        $needed        = max(0, 7234 - $existingCount);
        $prevMaxId     = (int)(DB::table('patients')->max('id') ?? 0);

        if ($needed > 0) {
            $this->command->info("Creating {$needed} patients…");

            $lastCode = Patient::orderByDesc('id')->value('patient_code') ?? 'PT-0000';
            preg_match('/(\d+)$/', $lastCode, $m);
            $codeOffset = (int)($m[1] ?? 0);

            $start    = Carbon::create(2021, 1, 1);
            $end      = Carbon::now()->subDays(1);
            $spanDays = (int) $start->diffInDays($end);

            $combinations = $this->nameCombinations($needed);
            $batch        = [];

            foreach ($combinations as $idx => [$firstName, $lastName, $gender]) {
                $regDays      = (int) round($spanDays * ($idx / $needed));
                $registeredAt = $start->copy()->addDays($regDays)->addHours(rand(8, 17));
                $emailSlug    = strtolower($firstName) . '.' . strtolower(preg_replace('/[^a-z]/i', '', $lastName));

                $batch[] = [
                    'patient_code'            => 'PT-' . str_pad($codeOffset + $idx + 1, 4, '0', STR_PAD_LEFT),
                    'first_name'              => $firstName,
                    'last_name'               => $lastName,
                    'date_of_birth'           => Carbon::create(rand(1955, 2005), rand(1, 12), rand(1, 28))->toDateString(),
                    'gender'                  => $gender,
                    'phone'                   => '09' . rand(100000000, 999999999),
                    'email'                   => $emailSlug . ($codeOffset + $idx + 1) . '@gmail.com',
                    'address'                 => $this->addresses[$idx % count($this->addresses)],
                    'medical_history'         => $this->medHistories[$idx % count($this->medHistories)],
                    'emergency_contact_name'  => $this->emergencyName($gender),
                    'emergency_contact_phone' => '09' . rand(100000000, 999999999),
                    'created_by'              => $recep->id,
                    'created_at'              => $registeredAt,
                    'updated_at'              => $registeredAt,
                ];

                if (count($batch) >= 500) {
                    DB::table('patients')->insert($batch);
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                DB::table('patients')->insert($batch);
            }
        }

        // ── Fetch newly inserted patients for prescriptions + appointments ─────
        if ($prevMaxId < DB::table('patients')->max('id')) {
            $this->command->info('Inserting prescriptions and appointments…');

            $newPatients = DB::table('patients')
                ->where('id', '>', $prevMaxId)
                ->select('id', 'created_at')
                ->get();

            $spherePool   = [-0.25,-0.50,-0.75,-1.00,-1.25,-1.50,-1.75,-2.00,-2.25,-2.50,-2.75,-3.00,-3.50,-4.00,+0.25,+0.50,+0.75,+1.00];
            $cylinderPool = [0.00,-0.25,-0.50,-0.75,-1.00,-1.25,-1.50,-1.75,-2.00];
            $apptTypes    = ['eye_exam','eye_exam','eye_exam','follow_up','fitting','other'];
            $apptStatuses = ['completed','completed','completed','completed','completed','cancelled','no_show'];
            $now          = Carbon::now();

            $rxBatch   = [];
            $apptBatch = [];
            $rxCount   = 0;
            $apptCount = 0;

            foreach ($newPatients as $np) {
                $regDate  = Carbon::parse($np->created_at);
                $daysOpen = max(1, (int) $regDate->diffInDays($now));

                // ── 1-2 prescriptions ─────────────────────────────────────────
                $numRx = rand(1, 2);
                for ($rx = 0; $rx < $numRx; $rx++) {
                    $examOffset = rand(1, min($daysOpen, 365 * 4));
                    $examDate   = $regDate->copy()->addDays($examOffset);
                    if ($examDate->isAfter($now)) $examDate = $now->copy()->subDays(rand(1, 30));

                    $odSph = $spherePool[($np->id + $rx * 3) % count($spherePool)];
                    $odCyl = $cylinderPool[($np->id + $rx)   % count($cylinderPool)];
                    $osSph = $spherePool[($np->id * 2 + $rx + 5) % count($spherePool)];
                    $osCyl = $cylinderPool[($np->id * 2 + $rx)   % count($cylinderPool)];

                    $rxBatch[] = [
                        'patient_id'        => $np->id,
                        'optometrist_id'    => $opto->id,
                        'od_sphere'         => $odSph,
                        'od_cylinder'       => $odCyl,
                        'od_axis'           => $odCyl != 0 ? rand(5, 175) : 0,
                        'od_add'            => null,
                        'od_pd'             => round(rand(290, 345) / 10, 1),
                        'os_sphere'         => $osSph,
                        'os_cylinder'       => $osCyl,
                        'os_axis'           => $osCyl != 0 ? rand(5, 175) : 0,
                        'os_add'            => null,
                        'os_pd'             => round(rand(290, 345) / 10, 1),
                        'visual_acuity_od'  => null,
                        'visual_acuity_os'  => null,
                        'notes'             => $this->rxNotes[($np->id + $rx) % count($this->rxNotes)],
                        'exam_date'         => $examDate->toDateString(),
                        'valid_until'       => $examDate->copy()->addYear()->toDateString(),
                        'created_at'        => $examDate,
                        'updated_at'        => $examDate,
                    ];
                    $rxCount++;

                    if (count($rxBatch) >= 500) {
                        DB::table('prescriptions')->insert($rxBatch);
                        $rxBatch = [];
                    }
                }

                // ── 1-2 appointments ──────────────────────────────────────────
                $numAppts = rand(1, 2);
                for ($a = 0; $a < $numAppts; $a++) {
                    $apptOffset  = rand(1, min($daysOpen, 365 * 4));
                    $apptDate    = $regDate->copy()->addDays($apptOffset)->setHour(rand(8, 17))->setMinute(0);
                    $isFuture    = $daysOpen <= 30 && $a === $numAppts - 1;

                    if ($isFuture) {
                        $apptDate = $now->copy()->addDays(rand(3, 30))->setHour(rand(8, 17))->setMinute(0);
                        $status   = 'scheduled';
                    } else {
                        if ($apptDate->isAfter($now)) $apptDate = $now->copy()->subDays(rand(1, 14))->setHour(rand(8, 17))->setMinute(0);
                        $status = $apptStatuses[($np->id + $a) % count($apptStatuses)];
                    }

                    $apptBatch[] = [
                        'patient_id'         => $np->id,
                        'optometrist_id'     => $opto->id,
                        'created_by'         => $recep->id,
                        'appointment_month'  => $apptDate->month,
                        'appointment_year'   => $apptDate->year,
                        'type'               => $apptTypes[($np->id + $a) % count($apptTypes)],
                        'status'             => $status,
                        'reason'             => $this->apptReasons[($np->id + $a) % count($this->apptReasons)],
                        'notes'              => null,
                        'created_at'         => $apptDate->copy()->subHours(rand(1, 48)),
                        'updated_at'         => $apptDate,
                    ];
                    $apptCount++;

                    if (count($apptBatch) >= 500) {
                        DB::table('appointments')->insert($apptBatch);
                        $apptBatch = [];
                    }
                }
            }

            // Flush remaining batches
            if (!empty($rxBatch))   DB::table('prescriptions')->insert($rxBatch);
            if (!empty($apptBatch)) DB::table('appointments')->insert($apptBatch);

            $this->command->info("✓ {$rxCount} prescriptions | {$apptCount} appointments inserted.");
        }

        // ── Fetch ALL patients for sales ──────────────────────────────────────
        $allPatients = DB::table('patients')
            ->whereNull('deleted_at')
            ->select('id', 'created_at')
            ->get()
            ->map(fn($p) => [
                'id'  => $p->id,
                'reg' => Carbon::parse($p->created_at),
            ])
            ->toArray();

        $total = count($allPatients);
        $this->command->info("Assigning 1–2 sales to each of {$total} patients…");

        // ── Generate sales using realistic yearly quotas rather than 1–2 sales per patient ──
        $patientIds = DB::table('patients')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        $yearlyTargets = [
            2021 => 1100,
            2022 => 1300,
            2023 => 1450,
            2024 => 1600,
            2025 => 1700,
            $currentYear => $this->generateOpenYearTarget($currentMonth),
        ];

        $this->command->info('Generating sales using realistic quotas: ' . json_encode($yearlyTargets));

        $payMethods = ['cash','cash','cash','cash','gcash','gcash','card','maya'];
        $receiptNo  = 0;
        $saleCount  = 0;
        $itemCount  = 0;

        // Every sale_item generated below is recorded here so a matching
        // 'sale' StockMovement can be built afterward — the ledger needs to
        // reflect the same transaction volume as sale_items, not just the
        // handful of manual stock-ins from StockMovementHistorySeeder.
        $movementSeeds = [];

        DB::beginTransaction();
        try {
            foreach ($yearlyTargets as $year => $target) {
                $isOpenYear = ($year === $currentYear);

                $generatedThisYear = 0;
                while ($generatedThisYear < $target) {
                    $saleDate = $this->pickSaleDateForYear($year, $isOpenYear);

                    // no future dates allowed
                    if ($saleDate->isFuture()) {
                        continue;
                    }

                    $patientId = $patientIds[array_rand($patientIds)];
                    $roll = random_int(1, 100);

                    $product = match (true) {
                        $roll <= 68 => $this->weightedPick($fastPool, $fastCum, $totalFast),
                        $roll <= 92 => $this->weightedPick($slowPool->isEmpty() ? $fastPool : $slowPool, $slowCum, $totalSlow),
                        default     => $accPool->isEmpty()
                            ? $this->weightedPick($fastPool, $fastCum, $totalFast)
                            : $accPool->values()[random_int(0, $accPool->count() - 1)],
                    };

                    $qty = random_int(1, 2);
                    $subtotal = $product->selling_price * $qty;
                    $receiptNo++;
                    $receiptNumber = 'RCP-' . str_pad($receiptNo, 6, '0', STR_PAD_LEFT);

                    $saleId = DB::table('sales')->insertGetId([
                        'receipt_number'  => $receiptNumber,
                        'patient_id'      => $patientId,
                        'cashier_id'      => $recep->id,
                        'prescription_id' => null,
                        'subtotal'        => $subtotal,
                        'discount_amount' => 0,
                        'tax_amount'      => 0,
                        'total_amount'    => $subtotal,
                        'amount_paid'     => $subtotal,
                        'change_amount'   => 0,
                        'payment_method'  => $payMethods[$receiptNo % count($payMethods)],
                        'status'          => 'completed',
                        'notes'           => null,
                        'created_at'      => $saleDate,
                        'updated_at'      => $saleDate,
                    ]);

                    DB::table('sale_items')->insert([
                        'sale_id'    => $saleId,
                        'product_id' => $product->id,
                        'quantity'   => $qty,
                        'unit_price' => $product->selling_price,
                        'discount'   => 0,
                        'subtotal'   => $subtotal,
                        'created_at' => $saleDate,
                        'updated_at' => $saleDate,
                    ]);

                    $movementSeeds[] = [
                        'product_id'      => $product->id,
                        'current_stock'   => $product->stock_quantity,
                        'quantity'        => $qty,
                        'reference_number'=> $receiptNumber,
                        'created_at'      => $saleDate,
                    ];

                    $saleCount++;
                    $itemCount++;

                    if ($roll <= 68 && random_int(1, 100) <= 23 && $fastPool->count() > 1) {
                        $product2 = $this->weightedPick($fastPool, $fastCum, $totalFast);

                        if ($product2->id !== $product->id) {
                            DB::table('sale_items')->insert([
                                'sale_id'    => $saleId,
                                'product_id' => $product2->id,
                                'quantity'   => 1,
                                'unit_price' => $product2->selling_price,
                                'discount'   => 0,
                                'subtotal'   => $product2->selling_price,
                                'created_at' => $saleDate,
                                'updated_at' => $saleDate,
                            ]);

                            $movementSeeds[] = [
                                'product_id'      => $product2->id,
                                'current_stock'   => $product2->stock_quantity,
                                'quantity'        => 1,
                                'reference_number'=> $receiptNumber,
                                'created_at'      => $saleDate,
                            ];

                            $itemCount++;
                        }
                    }

                    $generatedThisYear++;
                }
            }

            // ── Build the 'sale' stock_movements ledger to match sale_items ──
            // Each product's CURRENT stock_quantity is treated as the true
            // ending point (nothing else touches it), so the ledger is built
            // backward from it: beginning stock = current + everything sold,
            // then walked forward chronologically so quantity_before/after
            // land on real, non-negative numbers and the last entry's
            // quantity_after always equals the product's actual stock today.
            $byProduct = [];
            foreach ($movementSeeds as $seed) {
                $byProduct[$seed['product_id']][] = $seed;
            }

            $movementRows  = [];
            $movementCount = 0;

            foreach ($byProduct as $productId => $seeds) {
                usort($seeds, fn($a, $b) => $a['created_at'] <=> $b['created_at']);

                $totalSold      = array_sum(array_column($seeds, 'quantity'));
                $currentStock   = $seeds[0]['current_stock'];
                $running        = $currentStock + $totalSold;

                foreach ($seeds as $seed) {
                    $before = $running;
                    $after  = $running - $seed['quantity'];

                    $movementRows[] = [
                        'product_id'       => $productId,
                        'user_id'          => $recep->id,
                        'type'             => 'sale',
                        'quantity'         => $seed['quantity'],
                        'quantity_before'  => $before,
                        'quantity_after'   => $after,
                        'unit_cost'        => null,
                        'reference_number' => $seed['reference_number'],
                        'notes'            => null,
                        'created_at'       => $seed['created_at'],
                        'updated_at'       => $seed['created_at'],
                    ];

                    $running = $after;
                    $movementCount++;

                    if (count($movementRows) >= 500) {
                        DB::table('stock_movements')->insert($movementRows);
                        $movementRows = [];
                    }
                }
            }
            if (!empty($movementRows)) {
                DB::table('stock_movements')->insert($movementRows);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->command->info("✓ Sales generated: {$saleCount} sales | {$itemCount} line items.");
        $this->command->info("✓ Stock movements generated: {$movementCount} 'sale' entries matching those line items.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function emergencyName(string $gender): string
    {
        $opposite = $gender === 'female' ? $this->maleNames : $this->femaleNames;
        $contact  = $opposite[array_rand($opposite)];
        $last     = $this->lastNames[array_rand($this->lastNames)];
        return "{$contact} {$last}";
    }

    private function nameCombinations(int $limit): array
    {
        $out = [];
        foreach ($this->femaleNames as $fn) {
            foreach ($this->lastNames as $ln) {
                $out[] = [$fn, $ln, 'female'];
            }
        }
        foreach ($this->maleNames as $fn) {
            foreach ($this->lastNames as $ln) {
                $out[] = [$fn, $ln, 'male'];
            }
        }
        shuffle($out);
        return array_slice($out, 0, $limit);
    }

    private function generateOpenYearTarget(int $currentMonth): int
    {
        $base = random_int(250, 700);

        if ($currentMonth >= 9) {
            return random_int(350, 900);
        }

        if ($currentMonth >= 6) {
            return random_int(250, 700);
        }

        return $base;
    }

    private function pickSaleDateForYear(int $year, bool $isOpenYear = false): Carbon
    {
        $maxMonth = $isOpenYear ? min(Carbon::now()->month, 12) : 12;
        $month = random_int(1, $maxMonth);

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $maxDay = $daysInMonth;
        if ($isOpenYear && $month === Carbon::now()->month) {
            $maxDay = min($daysInMonth, Carbon::now()->day);
        }

        $day = random_int(1, $maxDay);

        return Carbon::create(
            $year,
            $month,
            $day,
            random_int(9, 18),
            random_int(0, 59),
            0
        );
    }

    private function buildWeights(\Illuminate\Support\Collection $pool, float $decay): array
    {
        $cum = [];
        $total = 0;

        foreach ($pool as $i => $_) {
            $total += max(1, (int) round(100 * pow($decay, $i)));
            $cum[] = $total;
        }

        return [$cum, $total];
    }

    private function weightedPick(\Illuminate\Support\Collection $pool, array $cum, int $total): object
    {
        $rand = mt_rand(1, $total);
        $lo = 0;
        $hi = count($cum) - 1;

        while ($lo < $hi) {
            $mid = (int) floor(($lo + $hi) / 2);

            if ($cum[$mid] < $rand) {
                $lo = $mid + 1;
            } else {
                $hi = $mid;
            }
        }

        return $pool[$lo];
    }
}
