<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPortalMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unverified_patient_is_bounced_to_verify_email(): void
    {
        $user = User::factory()->patient()->create(['email_verified_at' => null]);

        $this->actingAs($user)->get(route('patient.dashboard'))
            ->assertRedirect(route('verify-email'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_cannot_open_the_patient_portal(): void
    {
        $staff = User::factory()->admin()->create();

        $this->actingAs($staff)->get(route('patient.dashboard'))->assertForbidden();
    }

    public function test_a_patient_linked_to_an_inactive_patient_record_is_sent_to_account_review(): void
    {
        $patient = Patient::factory()->create(['status' => 'inactive']);
        $user = User::factory()->patient()->create(['patient_id' => $patient->id]);

        $this->actingAs($user)->get(route('patient.dashboard'))
            ->assertRedirect(route('patient.account-review'));
    }
}
