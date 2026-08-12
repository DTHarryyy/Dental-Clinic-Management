<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_patients_are_offered(): void
    {
        Patient::factory()->create(['first_name' => 'Ana', 'last_name' => 'Active', 'status' => 'active']);
        Patient::factory()->create(['first_name' => 'Ivan', 'last_name' => 'Inactive', 'status' => 'inactive']);

        $this->assertSame(['Ana Active'], Patient::dropdown()->pluck('name')->all());
    }

    public function test_deactivating_a_patient_drops_them_from_the_dropdown(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Leaving', 'last_name' => 'Soon', 'status' => 'active']);

        $this->assertCount(1, Patient::dropdown());

        // Goes through the real deactivate route, which is what staff actually click.
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('patients.status.update', $patient), ['status' => 'inactive'])
            ->assertRedirect();

        $this->assertCount(0, Patient::dropdown());
        $this->assertSame('inactive', $patient->fresh()->status);
    }

    public function test_reactivating_a_patient_brings_them_back(): void
    {
        $patient = Patient::factory()->create(['status' => 'inactive']);

        $this->assertCount(0, Patient::dropdown());

        $patient->update(['status' => 'active']);

        $this->assertCount(1, Patient::dropdown());
    }

    public function test_a_deactivated_patient_keeps_their_history(): void
    {
        $patient = Patient::factory()->create(['status' => 'inactive']);
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

        // Filtering the dropdown must not hide or orphan existing work.
        $this->assertSame($patient->id, $appointment->fresh()->patient->id);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'patient_id' => $patient->id]);
    }

    public function test_the_patient_lookup_lists_only_active_patients(): void
    {
        Patient::factory()->create(['first_name' => 'Bookable', 'last_name' => 'Person', 'status' => 'active']);
        Patient::factory()->create(['first_name' => 'Departed', 'last_name' => 'Person', 'status' => 'inactive']);

        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('lookups.patients', ['q' => 'Person']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Bookable Person')
            ->assertJsonMissing(['name' => 'Departed Person']);
    }
}
