<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PatientAppointmentWithdrawTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_a_pending_appointment_can_be_withdrawn_and_notifies_staff(): void
    {
        $admin = User::factory()->admin()->create();
        [$user, $patient] = $this->linkedPatient('withdraw@example.test');
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'pending']);

        $this->actingAs($user)->patch(route('patient.appointments.withdraw', $appointment))
            ->assertRedirect(route('patient.appointments.index'));

        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame('Withdrawn by patient.', $appointment->cancellation_reason);
        $this->assertDatabaseHas('notifications', [
            'type' => 'appointment_withdrawn',
            'notifiable_id' => $admin->id,
        ]);
    }

    public function test_withdrawing_resolves_any_pending_change_request(): void
    {
        [$user, $patient] = $this->linkedPatient('withdraw-change@example.test');
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'pending']);
        $change = AppointmentChangeRequest::create([
            'appointment_id' => $appointment->id,
            'patient_user_id' => $user->id,
            'patient_id' => $patient->id,
            'type' => 'reschedule',
            'reason' => 'Need a different time slot please.',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->patch(route('patient.appointments.withdraw', $appointment))->assertRedirect();

        $this->assertSame('rejected', $change->fresh()->status);
        $this->assertNotNull($change->fresh()->resolved_at);
    }

    public function test_a_confirmed_appointment_cannot_be_withdrawn_directly(): void
    {
        [$user, $patient] = $this->linkedPatient('confirmed-withdraw@example.test');
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'confirmed']);

        $this->actingAs($user)->patch(route('patient.appointments.withdraw', $appointment))
            ->assertSessionHasErrors('appointment');

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_a_completed_appointment_cannot_be_withdrawn(): void
    {
        [$user, $patient] = $this->linkedPatient('completed-withdraw@example.test');
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'completed']);

        $this->actingAs($user)->patch(route('patient.appointments.withdraw', $appointment))
            ->assertSessionHasErrors('appointment');

        $this->assertSame('completed', $appointment->fresh()->status);
    }

    public function test_a_patient_cannot_withdraw_another_patients_appointment(): void
    {
        [$user] = $this->linkedPatient('other-withdraw@example.test');
        [, $otherPatient] = $this->linkedPatient('victim-withdraw@example.test');
        $appointment = Appointment::factory()->create(['patient_id' => $otherPatient->id, 'status' => 'pending']);

        $this->actingAs($user)->patch(route('patient.appointments.withdraw', $appointment))->assertNotFound();

        $this->assertSame('pending', $appointment->fresh()->status);
    }
}
