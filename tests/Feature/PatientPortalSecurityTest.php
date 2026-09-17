<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PatientPortalSecurityTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_unlinked_patient_can_only_see_account_review(): void
    {
        $user = User::factory()->patient()->create(['patient_id' => null]);

        $this->actingAs($user)
            ->get(route('patient.dashboard'))
            ->assertRedirect(route('patient.account-review'));

        $this->actingAs($user)
            ->getJson(route('patient.appointments.dates', [
                'start_date' => now()->toDateString(),
                'service_ids' => [1],
            ]))
            ->assertForbidden();
    }

    public function test_patient_routes_are_scoped_to_the_linked_patient(): void
    {
        [$patientUser] = $this->linkedPatient('owner@example.test');
        $otherPatient = Patient::factory()->create(['status' => 'active']);
        $otherAppointment = Appointment::factory()->create([
            'patient_id' => $otherPatient->id,
            'status' => 'confirmed',
        ]);
        $otherRecord = DentalRecord::factory()->create([
            'patient_id' => $otherPatient->id,
            'patient_summary' => 'Visible only to the other patient.',
            'published_at' => now(),
        ]);
        $otherInvoice = Invoice::create([
            'patient_id' => $otherPatient->id,
            'invoice_date' => today(),
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_status' => 'unpaid',
        ]);
        InvoiceItem::create(['invoice_id' => $otherInvoice->id, 'description' => 'Private invoice item', 'qty' => 1, 'price' => 1000]);

        $this->actingAs($patientUser)->get(route('patient.appointments.show', $otherAppointment))->assertNotFound();
        $this->actingAs($patientUser)->get(route('patient.treatments.show', $otherRecord))->assertNotFound();
        $this->actingAs($patientUser)->get(route('patient.billing.show', $otherInvoice))->assertNotFound();
        $this->actingAs($patientUser)->get(route('patient.billing.receipt', $otherInvoice))->assertNotFound();
    }

    public function test_patient_treatment_summary_hides_internal_clinical_content_and_fee(): void
    {
        [$patientUser, $patient] = $this->linkedPatient('summary@example.test');
        $record = DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'procedure' => 'Root Canal',
            'clinical_notes' => 'Internal clinical note only',
            'patient_summary' => 'Your treatment went well and healing is expected.',
            'aftercare_instructions' => 'Avoid hard food today.',
            'treatment_fee' => 9876.54,
            'published_at' => now(),
        ]);

        $this->actingAs($patientUser)
            ->get(route('patient.treatments.show', $record))
            ->assertOk()
            ->assertSee('Your treatment went well')
            ->assertSee('Avoid hard food today')
            ->assertDontSee('Internal clinical note only')
            ->assertDontSee('9876.54');
    }

    public function test_receptionist_can_approve_patient_cancellation_request(): void
    {
        [$patientUser, $patient] = $this->linkedPatient('change@example.test');
        $receptionist = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);
        $start = CarbonImmutable::now('Asia/Manila')->addWeek()->setTime(9, 0)->utc();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'requested_by_user_id' => $patientUser->id,
            'full_name' => $patient->name,
            'email' => $patient->email,
            'status' => 'confirmed',
            'appointment_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'appointment_time' => '9:00 AM',
            'preferred_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'preferred_time_window' => 'morning',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addMinutes(30),
            'duration_minutes' => 30,
        ]);

        $this->actingAs($patientUser)
            ->post(route('patient.appointments.change', $appointment), [
                'type' => 'cancel',
                'reason' => 'I am unavailable for this appointment.',
            ])->assertRedirect(route('patient.appointments.show', $appointment));

        $changeRequest = $appointment->changeRequests()->where('status', 'pending')->firstOrFail();

        $this->actingAs($receptionist)
            ->patch(route('appointment-change-requests.resolve', $changeRequest), [
                'decision' => 'approve',
                'resolution_note' => 'Cancellation approved by front desk.',
            ])->assertRedirect();

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertDatabaseHas('appointment_change_requests', [
            'id' => $changeRequest->id,
            'status' => 'approved',
            'resolved_by_user_id' => $receptionist->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => 'appointment_change_decision',
            'notifiable_id' => $patientUser->id,
        ]);
    }
}
