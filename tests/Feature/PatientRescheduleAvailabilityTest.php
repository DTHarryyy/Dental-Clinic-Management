<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

/**
 * Regression coverage for the reschedule-request UX bug: the modal used to offer
 * a bare datetime picker with no availability info, so patients routinely picked
 * an already-booked time and were rejected with no way to know why. These tests
 * cover the slot-picker endpoints backing the fix and the field-key correction on
 * the "That time is already booked" error (it used to attach to the booking
 * form's field name, not the reschedule form's).
 */
class PatientRescheduleAvailabilityTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    // dentist_id is always explicit: AppointmentFactory defaults to a brand new
    // User::factory()->dentist() per call when omitted, which silently inflates
    // active-dentist capacity and hides real double-booking conflicts in tests.
    private function confirmedAppointment(Patient $patient, User $patientUser, CarbonImmutable $start, ?int $dentistId = null): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentistId,
            'requested_by_user_id' => $patientUser->id,
            'full_name' => $patient->name,
            'email' => $patient->email,
            'status' => 'confirmed',
            'appointment_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'appointment_time' => $start->setTimezone('Asia/Manila')->format('g:i A'),
            'preferred_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'preferred_time_window' => 'morning',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addMinutes(30),
            'duration_minutes' => 30,
        ]);
    }

    public function test_the_appointments_own_current_slot_is_offered_as_available(): void
    {
        $dentist = User::factory()->dentist()->create(['status' => 'active']);
        [$patientUser, $patient] = $this->linkedPatient('reschedule-own-slot@example.test');
        $start = CarbonImmutable::now('Asia/Manila')->addWeek()->setTime(9, 0)->utc();
        $appointment = $this->confirmedAppointment($patient, $patientUser, $start, $dentist->id);

        $response = $this->actingAs($patientUser)
            ->getJson(route('patient.appointments.reschedule.slots', $appointment).'?date='.$start->setTimezone('Asia/Manila')->toDateString())
            ->assertOk();

        $slot = collect($response->json('slots'))->firstWhere('start', $start->toIso8601String());
        $this->assertNotNull($slot, 'The appointment\'s own current slot should appear in its reschedule options.');
        $this->assertTrue($slot['available']);
    }

    public function test_reschedule_dates_and_slots_are_scoped_to_the_owning_patient(): void
    {
        [$owner] = $this->linkedPatient('reschedule-owner@example.test');
        $otherPatient = Patient::factory()->create(['status' => 'active']);
        $otherUser = User::factory()->patient()->create(['patient_id' => $otherPatient->id]);
        $foreign = $this->confirmedAppointment($otherPatient, $otherUser, CarbonImmutable::now('Asia/Manila')->addWeek()->setTime(9, 0)->utc());

        $this->actingAs($owner)->getJson(route('patient.appointments.reschedule.dates', $foreign).'?start_date='.now()->toDateString())->assertNotFound();
        $this->actingAs($owner)->getJson(route('patient.appointments.reschedule.slots', $foreign).'?date='.now()->toDateString())->assertNotFound();
    }

    public function test_a_pending_appointment_has_no_reschedule_picker(): void
    {
        [$patientUser, $patient] = $this->linkedPatient('reschedule-pending@example.test');
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'requested_by_user_id' => $patientUser->id,
            'status' => 'pending',
        ]);

        $this->actingAs($patientUser)
            ->getJson(route('patient.appointments.reschedule.dates', $appointment).'?start_date='.now()->toDateString())
            ->assertNotFound();
    }

    public function test_requesting_an_already_booked_time_attaches_the_error_to_the_reschedule_field(): void
    {
        $dentist = User::factory()->dentist()->create(['status' => 'active']);
        [$patientUser, $patient] = $this->linkedPatient('reschedule-conflict@example.test');
        $start = CarbonImmutable::now('Asia/Manila')->addWeek()->setTime(9, 0)->utc();
        $appointment = $this->confirmedAppointment($patient, $patientUser, $start, $dentist->id);

        // Another confirmed appointment occupies the only dentist at 10:00.
        $conflictStart = CarbonImmutable::now('Asia/Manila')->addWeek()->setTime(10, 0)->utc();
        $blocker = Patient::factory()->create(['status' => 'active']);
        $this->confirmedAppointment($blocker, User::factory()->patient()->create(['patient_id' => $blocker->id]), $conflictStart, $dentist->id);

        $this->actingAs($patientUser)
            ->postJson(route('patient.appointments.change', $appointment), [
                'type' => 'reschedule',
                'reason' => 'Need a different time this week.',
                'proposed_start_at' => $conflictStart->toIso8601String(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('proposed_start_at')
            ->assertJsonMissingValidationErrors('requested_start_at');
    }
}
