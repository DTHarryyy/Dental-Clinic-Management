<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PatientAppointmentIndexTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_status_tabs_filter_the_appointment_list(): void
    {
        [$user, $patient] = $this->linkedPatient('tabs@example.test');
        Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'pending']);
        Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'confirmed']);
        Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'completed']);
        Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'cancelled']);

        $this->actingAs($user)->get(route('patient.appointments.index', ['status' => 'pending']))
            ->assertOk()->assertViewHas('appointments', fn ($appointments) => $appointments->total() === 1);

        $this->actingAs($user)->get(route('patient.appointments.index', ['status' => 'upcoming']))
            ->assertOk()->assertViewHas('appointments', fn ($appointments) => $appointments->total() === 2);

        $this->actingAs($user)->get(route('patient.appointments.index', ['status' => 'completed']))
            ->assertOk()->assertViewHas('appointments', fn ($appointments) => $appointments->total() === 1);

        $this->actingAs($user)->get(route('patient.appointments.index', ['status' => 'cancelled']))
            ->assertOk()->assertViewHas('appointments', fn ($appointments) => $appointments->total() === 1);
    }

    public function test_an_unknown_status_falls_back_to_upcoming_instead_of_showing_everything(): void
    {
        [$user, $patient] = $this->linkedPatient('unknown-status@example.test');
        Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'pending']);
        Appointment::factory()->create(['patient_id' => $patient->id, 'status' => 'completed']);

        $this->actingAs($user)->get(route('patient.appointments.index', ['status' => 'not-a-real-status']))
            ->assertOk()
            ->assertViewHas('status', 'upcoming')
            ->assertViewHas('appointments', fn ($appointments) => $appointments->total() === 1);
    }

    public function test_the_list_paginates_at_eight(): void
    {
        [$user, $patient] = $this->linkedPatient('paginate@example.test');
        Appointment::factory()->count(9)->create(['patient_id' => $patient->id, 'status' => 'pending']);

        $this->actingAs($user)->get(route('patient.appointments.index', ['status' => 'pending']))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 8 && $appointments->total() === 9);
    }
}
