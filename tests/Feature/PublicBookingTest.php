<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_gateway_sends_guests_to_unified_login(): void
    {
        Service::create(['name' => 'Consultation', 'price' => 1234.56, 'duration_minutes' => 30]);

        $this->get(route('public.book'))
            ->assertRedirect(route('login'));
    }

    public function test_public_landing_shows_services_without_forcing_guest_booking_form(): void
    {
        Service::create(['name' => 'Consultation', 'price' => 1234.56, 'duration_minutes' => 30, 'show_public_price' => false]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Consultation')
            ->assertDontSee('1,234.56');
    }

    public function test_authenticated_patient_booking_allows_more_than_three_services(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $patient = Patient::factory()->create(['first_name' => 'Multiple', 'last_name' => 'Services', 'email' => 'multiple@example.test', 'status' => 'active']);
        $user = User::factory()->patient()->create(['patient_id' => $patient->id, 'email' => 'multiple@example.test']);
        $services = collect(range(1, 4))->map(fn ($number) => Service::create([
            'name' => "Service {$number}", 'duration_minutes' => 30,
        ]));
        $date = now()->addWeek()->toDateString();

        $this->actingAs($user)->post(route('patient.appointments.store'), [
            'requested_start_at' => "{$date} 08:00",
            'service_ids' => $services->pluck('id')->all(),
        ])->assertRedirect();

        $this->assertDatabaseCount('appointment_services', 4);
    }

    public function test_authenticated_patient_booking_uses_linked_patient_identity(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        Service::create(['name' => 'Consultation', 'is_active' => true]);
        $patient = Patient::factory()->create(['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan@example.com', 'mobile' => '09171234567', 'status' => 'active']);
        $user = User::factory()->patient()->create(['patient_id' => $patient->id, 'email' => 'juan@example.com']);
        $date = now()->addWeek()->toDateString();

        $response = $this->actingAs($user)->post(route('patient.appointments.store'), [
            'requested_start_at' => "{$date} 08:00",
            'service_ids' => [Service::first()->id],
            'patient_id' => Patient::factory()->create()->id,
            'email' => 'forged@example.test',
            'price' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'email' => 'juan@example.com',
            'contact_number' => '09171234567',
            'requested_by_user_id' => $user->id,
        ]);
    }

    public function test_public_booking_post_no_longer_accepts_guest_payloads(): void
    {
        Service::create(['name' => 'Consultation', 'is_active' => true]);

        $this->from(route('public.book'))->post(route('public.book.store'), [
            'full_name' => 'Juan Dela Cruz',
            'appointment_date' => now()->addDay()->toDateString(),
            'service' => 'Consultation',
        ])->assertRedirect(route('public.book'));

        $this->assertDatabaseCount('appointments', 0);
    }
}
