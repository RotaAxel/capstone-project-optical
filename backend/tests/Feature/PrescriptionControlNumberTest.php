<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Prescription control numbers: RX-<exam date>-<sequence of that day>.
 * Two visits on the same day (morning and afternoon) get different numbers,
 * and a sale attaches the later one.
 */
class PrescriptionControlNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $optometrist;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optometrist = User::factory()->create(['role' => 'optometrist']);
        $this->patient = Patient::create([
            'patient_code' => 'PT-CN-1', 'first_name' => 'Ana', 'last_name' => 'Reyes',
            'date_of_birth' => '1990-01-01', 'gender' => 'female', 'created_by' => $this->optometrist->id,
        ]);
        Sanctum::actingAs($this->optometrist);
    }

    private function newRx(string $examDate)
    {
        return $this->postJson('/api/prescriptions', ['patient_id' => $this->patient->id, 'exam_date' => $examDate])->assertCreated();
    }

    public function test_same_day_visits_get_sequential_control_numbers(): void
    {
        $morning   = $this->newRx('2026-09-22')->json('control_number');
        $afternoon = $this->newRx('2026-09-22')->json('control_number');
        $nextDay   = $this->newRx('2026-09-23')->json('control_number');

        $this->assertSame('RX-20260922-001', $morning);
        $this->assertSame('RX-20260922-002', $afternoon);
        $this->assertSame('RX-20260923-001', $nextDay);
    }

    public function test_rows_inserted_directly_get_numbers_continuing_the_day(): void
    {
        $this->newRx('2026-09-22'); // RX-20260922-001
        DB::table('prescriptions')->insert([
            ['patient_id' => $this->patient->id, 'optometrist_id' => $this->optometrist->id, 'exam_date' => '2026-09-22', 'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => $this->patient->id, 'optometrist_id' => $this->optometrist->id, 'exam_date' => '2026-09-22', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame(2, Prescription::assignMissingControlNumbers());
        $this->assertSame(
            ['RX-20260922-001', 'RX-20260922-002', 'RX-20260922-003'],
            Prescription::orderBy('id')->pluck('control_number')->all()
        );
    }

    public function test_prescriptions_can_be_searched_by_control_number(): void
    {
        $this->newRx('2026-09-22');
        $second = $this->newRx('2026-09-22')->json('id');

        $found = $this->getJson('/api/prescriptions?search=RX-20260922-002')->assertOk()->json('data');
        $this->assertSame([$second], array_column($found, 'id'));
    }

    public function test_a_sale_attaches_the_afternoon_visit_of_the_same_day(): void
    {
        $this->newRx('2026-09-22');
        $afternoon = $this->newRx('2026-09-22')->json('id');

        $cashier  = User::factory()->create(['role' => 'receptionist']);
        $category = ProductCategory::create(['name' => 'Frames', 'type' => 'frame']);
        $product  = Product::create([
            'sku' => 'TST-CN-1', 'name' => 'Frame', 'category_id' => $category->id,
            'cost_price' => 500, 'selling_price' => 1200, 'stock_quantity' => 5, 'reorder_point' => 1,
        ]);
        Sanctum::actingAs($cashier);

        $this->postJson('/api/sales', [
            'patient_id' => $this->patient->id, 'payment_method' => 'cash', 'amount_paid' => 1200,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1200]],
        ])->assertCreated()
          ->assertJsonPath('prescription.id', $afternoon)
          ->assertJsonPath('prescription.control_number', 'RX-20260922-002');
    }
}
