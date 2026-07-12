<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BulkPatientSeeder extends Seeder
{
    // ── Name pools (Filipino) ────────────────────────────────────────────────

    private array $maleFirstNames = [
        'Andres','Ariel','Arnaldo','Arturo','Bonifacio','Carlos','Cesar','Danilo',
        'Dario','Dennis','Eduardo','Efren','Emilio','Emmanuel','Enrique','Ernesto',
        'Federico','Felix','Fernando','Francisco','Gideon','Gilbert','Gonzalo',
        'Gregorio','Hermogenes','Hilario','Ignacio','Isidro','Joel','Jorge',
        'Jose','Juan','Julian','Leandro','Leonardo','Lorenzo','Luisito','Manuel',
        'Marcelo','Marco','Mariano','Maximo','Miguel','Narciso','Nelson','Nicolas',
        'Nilo','Norman','Octavio','Orlando','Pablo','Pedro','Philip','Ramon',
        'Renato','Ricardo','Roberto','Rodolfo','Rolando','Romualdo','Ronaldo',
        'Samuel','Santiago','Sebastian','Sergio','Silvio','Teodoro','Tomas',
        'Urbano','Vicente','Victor','Virgilio','Wilson','Rafael','Roel','Rustico',
        'Alejandro','Alfredo','Amado','Arnold','Edmundo','Edgardo','Edgar','Elmer',
        'Elbert','Florencio','Frederick','Gerardo','Gabriel','Hugo','Hector',
        'Ivan','Michael','Nicanor','Oswaldo','Roderick','Tito',
    ];

    private array $femaleFirstNames = [
        'Alicia','Ana','Analiza','Angelina','Anita','Annaliza','Beatriz','Caridad',
        'Cecilia','Celia','Corazon','Cristina','Dolores','Eleanor','Elena','Elisa',
        'Elsa','Emma','Erlinda','Esmeralda','Esperanza','Estrella','Eufemia',
        'Evelyn','Felicidad','Florinda','Geraldine','Gloria','Grace','Helen',
        'Irene','Irma','Jasmine','Jennifer','Jessica','Josephine','Juanita',
        'Kristine','Leonor','Liezl','Lorraine','Lourdes','Luisa','Luz','Maribel',
        'Maricel','Marilou','Marinela','Marites','Marlene','Mercedes','Michelle',
        'Milagros','Natividad','Nenita','Nena','Paz','Perla','Pilar','Racquel',
        'Rebecca','Remedios','Rhodora','Rosa','Rosario','Rowena','Sheila','Sonia',
        'Susan','Teresita','Teresa','Violeta','Vivian','Wilma','Zenaida',
        'Patricia','Leonora','Jennalyn','Concepcion','Cora','Felisa',
    ];

    private array $lastNames = [
        'Abella','Aguilar','Alcantara','Alvarez','Aquino','Araneta','Ariola',
        'Bacalso','Baluyot','Bautista','Bello','Buenaventura','Cabrera','Cadiz',
        'Canlas','Capuno','Castillo','Catacutan','Centeno','Corpuz','Cruz',
        'Cuizon','Custodio','Dacalos','Dalisay','David','De Guzman','De Jesus',
        'De Leon','De los Santos','Del Rosario','Dela Cruz','Diaz','Dimayuga',
        'Dizon','Domingo','Dungca','Escaño','Espino','Evangelista','Fabian',
        'Fajardo','Fernandez','Flores','Galang','Garcia','Gaviola','Genuino',
        'Gonzales','Gonzalez','Gorospe','Guevarra','Guinto','Herrera','Hernandez',
        'Ilagan','Jacinto','Jimenez','Joson','Lacson','Llamas','Lopez','Lorenzo',
        'Lozada','Macapagal','Maceda','Magno','Mangahas','Marcelino','Marasigan',
        'Medina','Mendoza','Mercado','Molina','Montalvo','Montenegro','Morales',
        'Muñoz','Navarro','Nieva','Noriega','Ocampo','Olan','Paras','Pascual',
        'Paterno','Perez','Peralta','Pineda','Pingol','Planas','Poblete',
        'Quizon','Ramirez','Ramos','Real','Recio','Reyes','Rivera','Robles',
        'Rodriguez','Roman','Sagun','Salazar','Salcedo','Sanchez','Santos',
        'Soriano','Solis','Sotto','Suarez','Tan','Tiongson','Tolentino',
        'Torres','Trinidad','Uy','Valencia','Valdez','Vargas','Vasquez',
        'Velarde','Vergara','Viray','Villanueva','Villalba','Villafuerte',
        'Villanueva','Villareal','Vinuya','Ximenez','Yap','Ybañez','Zamora',
    ];

    private array $addresses = [
        'Lahug, Cebu City','Mabolo, Cebu City','Banilad, Cebu City','Talamban, Cebu City',
        'IT Park, Cebu City','Apas, Cebu City','Guadalupe, Cebu City','Sambag, Cebu City',
        'Pardo, Cebu City','Labangon, Cebu City','Carbon, Cebu City','Punta Princesa, Cebu City',
        'Basak, Cebu City','Bulacao, Cebu City','Inayawan, Cebu City','Kinasang-an, Cebu City',
        'Mandaue City','Mactan, Lapu-Lapu City','Talisay City','Consolacion, Cebu',
        'Liloan, Cebu','Minglanilla, Cebu','Naga, Cebu','San Fernando, Cebu',
        'Cordova, Cebu','Compostela, Cebu','Danao City','Toledo City',
        'Carcar City','Argao, Cebu',
    ];

    private array $medHistories = [
        'Nearsighted since childhood, mild astigmatism.',
        'Presbyopia. Uses reading glasses.',
        'History of dry eyes. Prescribed lubricating drops.',
        'No significant ocular history.',
        'Diabetes Type 2. Regular eye monitoring advised.',
        'Family history of glaucoma. Annual pressure check required.',
        'High myopia. Progressive lens recommended.',
        'Allergic conjunctivitis. Seasonal.',
        'Mild hyperopia. First prescription.',
        'Post-cataract surgery (left eye). Follow-up required.',
        'Referred by family doctor for routine check.',
        'Reports occasional blurred vision. First visit.',
        'Contact lens wearer. Annual check-up.',
    ];

    public function run(): void
    {
        set_time_limit(0);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $recep = User::where('role', 'receptionist')->first()
               ?? User::where('role', 'admin')->first();

        // ── Product pool ─────────────────────────────────────────────────────
        $products = Product::whereIn('sku', [
            'FR-001','FR-002','FR-003','FR-004','FR-005',
            'LN-001','LN-002','LN-003','LN-004',
            'CL-001','CL-002','SG-001','SG-002',
            'AC-001','AC-002','AC-003',
        ])->get();

        if ($products->isEmpty()) {
            $this->command->error('No products found. Run DatabaseSeeder first.');
            return;
        }

        // ── Continue from last patient code ──────────────────────────────────
        $lastCode = Patient::orderByDesc('id')->value('patient_code') ?? 'PT-0000';
        preg_match('/(\d+)$/', $lastCode, $m);
        $codeOffset = (int)($m[1] ?? 0);

        // ── Continue from last receipt number ────────────────────────────────
        $receiptNo = (int) DB::table('sales')
            ->selectRaw("COALESCE(MAX(CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED)), 0) as max_num")
            ->value('max_num');

        $payMethods = ['cash','cash','cash','cash','gcash','gcash','card','maya'];

        // Build unique name combinations
        $combinations = [];
        foreach ($this->femaleFirstNames as $fn) {
            foreach ($this->lastNames as $ln) {
                $combinations[] = [$fn, $ln, 'female'];
            }
        }
        foreach ($this->maleFirstNames as $fn) {
            foreach ($this->lastNames as $ln) {
                $combinations[] = [$fn, $ln, 'male'];
            }
        }
        shuffle($combinations);

        // Use 400 patients
        $batch   = array_slice($combinations, 0, 400);
        $start   = Carbon::create(2021, 1, 1);
        $end     = Carbon::now()->subDays(3);
        $spanDays = (int) $start->diffInDays($end);

        $patientCount = 0;
        $saleCount    = 0;
        $itemCount    = 0;

        foreach ($batch as $idx => [$firstName, $lastName, $gender]) {
            // Spread registration dates across the full 4-year window
            $regDaysAgo   = (int) round($spanDays * (1 - ($idx / count($batch))));
            $registeredAt = Carbon::now()->subDays($regDaysAgo)->subHours(rand(0, 23));

            $dob = Carbon::create(
                rand(1955, 2005),
                rand(1, 12),
                rand(1, 28)
            );

            $patient = Patient::create([
                'patient_code'            => 'PT-' . str_pad($codeOffset + $idx + 1, 4, '0', STR_PAD_LEFT),
                'first_name'              => $firstName,
                'last_name'               => $lastName,
                'date_of_birth'           => $dob->toDateString(),
                'gender'                  => $gender,
                'phone'                   => '09' . rand(100000000, 999999999),
                'email'                   => null,
                'address'                 => $this->addresses[$idx % count($this->addresses)],
                'medical_history'         => $this->medHistories[$idx % count($this->medHistories)],
                'emergency_contact_name'  => null,
                'emergency_contact_phone' => null,
                'created_by'              => $recep->id,
                'created_at'              => $registeredAt,
                'updated_at'              => $registeredAt,
            ]);
            $patientCount++;

            // Assign 1 or 2 transactions randomly
            $numSales = rand(1, 2);

            for ($s = 0; $s < $numSales; $s++) {
                // Sale happens after registration, within the patient's history window
                $maxDaysAfterReg = max(1, (int) $registeredAt->diffInDays(Carbon::now()) - 1);
                $saleOffset      = rand(0, min($maxDaysAfterReg, 365 * 3));
                $saleDate        = $registeredAt->copy()
                                     ->addDays($saleOffset)
                                     ->setHour(rand(8, 18))
                                     ->setMinute(rand(0, 59));

                if ($saleDate->isAfter(Carbon::now())) {
                    $saleDate = Carbon::now()->subHours(rand(1, 48));
                }

                $product  = $products->values()[$idx % $products->count()];
                $qty      = rand(1, 2);
                $subtotal = $product->selling_price * $qty;

                $receiptNo++;
                $sale = Sale::create([
                    'receipt_number'  => 'RCP-' . str_pad($receiptNo, 6, '0', STR_PAD_LEFT),
                    'patient_id'      => $patient->id,
                    'cashier_id'      => $recep->id,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount'      => 0,
                    'total_amount'    => $subtotal,
                    'amount_paid'     => $subtotal,
                    'change_amount'   => 0,
                    'payment_method'  => $payMethods[$idx % count($payMethods)],
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
                $itemCount++;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("✓ Inserted {$patientCount} patients with {$saleCount} transactions ({$itemCount} items).");
    }
}
