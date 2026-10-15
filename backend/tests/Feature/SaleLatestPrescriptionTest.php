<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A sale for a patient is linked automatically to that patient's latest
 * prescription (by exam date); staff no longer choose it.
 */
class SaleLatestPrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private User $optometrist;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier     = User::factory()->create(['role' => 'receptionist']);
        $this->optometrist = User::factory()->create(['role' => 'optometrist']);
        $category = ProductCategory::create(['name' => 'Frames', 'type' => 'frame']);
        $this->product = Product::create([
            'sku' => 'TST-SALE-1', 'name' => 'Test Frame', 'category_id' => $category->id,
            'cost_price' => 500, 'selling_price' => 1200, 'stock_quantity' => 10, 'reorder_point' => 2,
        ]);
        Sanctum::actingAs($this->cashier);
    }

    private function patient(string $code): Patient
    {
        return Patient::create([
            'patient_code' => $code, 'first_name' => 'Ana', 'last_name' => 'Reyes',
            'date_of_birth' => '1990-01-01', 'gender' => 'female', 'created_by' => $this->cashier->id,
        ]);
    }

    private function prescription(Patient $p, string $examDate): Prescription
    {
        return Prescription::create(['patient_id' => $p->id, 'optometrist_id' => $this->optometrist->id, 'exam_date' => $examDate]);
    }

    private function sell(?int $patientId, array $extra = [])
    {
        return $this->postJson('/api/sales', array_merge([
            'patient_id' => $patientId, 'payment_method' => 'cash', 'amount_paid' => 1200,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1200]],
        ], $extra))->assertCreated();
    }

    public function test_the_latest_prescription_by_exam_date_is_attached(): void
    {
        $patient = $this->patient('PT-1');
        $latest  = $this->prescription($patient, '2026-08-01');
        $older   = $this->prescription($patient, '2025-03-15'); // entered later, but an older exam

        // Even if a client still sends an older prescription, the latest one is used.
        $this->sell($patient->id, ['prescription_id' => $older->id])
            ->assertJsonPath('prescription.id', $latest->id);
    }

    public function test_no_prescription_for_walk_ins_or_patients_without_one(): void
    {
        $this->sell(null)->assertJsonPath('prescription', null);
        $this->sell($this->patient('PT-2')->id)->assertJsonPath('prescription', null);
    }
}
