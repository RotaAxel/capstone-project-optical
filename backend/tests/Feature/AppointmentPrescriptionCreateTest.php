<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The API paths behind the Book Appointment and New Prescription forms:
 * - an Admin must pick the optometrist for a new prescription (the form now
 *   shows that field), while an optometrist is assigned automatically;
 * - a newly booked appointment can be found by the list's highlight lookup,
 *   which the page now uses to jump to and show it after saving.
 */
class AppointmentPrescriptionCreateTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private User $optometrist;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin       = User::factory()->create(['role' => 'admin']);
        $this->optometrist = User::factory()->create(['role' => 'optometrist']);
        $this->patient     = Patient::create([
            'patient_code' => 'PT-TEST-1', 'first_name' => 'Ana', 'last_name' => 'Reyes',
            'date_of_birth' => '1990-01-01', 'gender' => 'female', 'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_creates_a_prescription_for_the_chosen_optometrist(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/prescriptions', ['patient_id' => $this->patient->id, 'exam_date' => now()->toDateString()])
            ->assertStatus(422)->assertJsonValidationErrors('optometrist_id');

        $this->postJson('/api/prescriptions', [
            'patient_id' => $this->patient->id, 'exam_date' => now()->toDateString(),
            'optometrist_id' => $this->optometrist->id,
        ])->assertCreated()->assertJsonPath('optometrist.id', $this->optometrist->id);
    }

    public function test_optometrist_is_assigned_automatically_without_the_field(): void
    {
        Sanctum::actingAs($this->optometrist);

        $this->postJson('/api/prescriptions', ['patient_id' => $this->patient->id, 'exam_date' => now()->toDateString()])
            ->assertCreated()->assertJsonPath('optometrist.id', $this->optometrist->id);
    }

    public function test_a_new_appointment_is_returned_on_its_page_by_the_highlight_lookup(): void
    {
        Sanctum::actingAs($this->admin);

        // 20 earlier appointments fill page 1 (15 per page, sorted by date).
        for ($i = 0; $i < 20; $i++) {
            Appointment::create([
                'patient_id' => $this->patient->id, 'optometrist_id' => $this->optometrist->id,
                'created_by' => $this->admin->id, 'appointment_date' => now()->subDays(30 - $i),
                'type' => 'eye_exam', 'status' => 'completed',
            ]);
        }

        $id = $this->postJson('/api/appointments', [
            'patient_id' => $this->patient->id, 'optometrist_id' => $this->optometrist->id,
            'appointment_date' => now()->addDays(3)->format('Y-m-d H:i'), 'type' => 'eye_exam',
        ])->assertCreated()->json('id');

        // A plain reload of page 1 does not contain it — that was the bug.
        $this->assertNotContains($id, array_column($this->getJson('/api/appointments?page=1')->json('data'), 'id'));

        // The highlight lookup returns the page that does.
        $page = $this->getJson('/api/appointments?highlight_id=' . $id)->assertOk();
        $this->assertContains($id, array_column($page->json('data'), 'id'));
        $this->assertSame(2, $page->json('current_page'));
    }
}
