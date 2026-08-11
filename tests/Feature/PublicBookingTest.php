<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_does_not_expose_service_prices(): void
    {
        Service::create(['name' => 'Consultation', 'price' => 1234.56, 'duration_minutes' => 30]);

        $this->get(route('public.book'))->assertOk()
            ->assertSee('Consultation')
            ->assertDontSee('1,234.56')
            ->assertDontSee('Estimated total')
            ->assertDontSee('Estimated visit duration')
            ->assertDontSee('30 min');
    }

    public function test_public_booking_button_has_a_double_submit_loading_state(): void
    {
        Service::create(['name' => 'Consultation', 'duration_minutes' => 30]);

        $this->get(route('public.book'))->assertOk()
            ->assertSee('x-on:submit="submitting = true"', escape: false)
            ->assertSee(':disabled="submitting"', escape: false)
            ->assertSee('Submitting request…');
    }

    public function test_public_booking_allows_more_than_three_services(): void
    {
        $services = collect(range(1, 4))->map(fn ($number) => Service::create([
            'name' => "Service {$number}", 'duration_minutes' => 30,
        ]));

        $this->post(route('public.book.store'), [
            'full_name' => 'Multiple Services', 'email' => 'multiple@example.test',
            'preferred_date' => now()->addDay()->toDateString(), 'preferred_time_window' => 'morning',
            'service_ids' => $services->pluck('id')->all(),
        ])->assertRedirect(route('public.book.success'));

        $this->assertDatabaseCount('appointment_services', 4);
    }

    public function test_public_booking_uses_email_as_the_only_contact_method(): void
    {
        Service::create(['name' => 'Consultation', 'is_active' => true]);

        $response = $this->post(route('public.book.store'), [
            'full_name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => 'Morning (8 AM – 12 PM)',
            'service' => 'Consultation',
        ]);

        $response->assertRedirect(route('public.book.success'));
        $this->assertDatabaseHas('appointments', [
            'email' => 'juan@example.com',
            'contact_number' => '',
        ]);
    }

    public function test_public_booking_requires_an_email_address(): void
    {
        Service::create(['name' => 'Consultation', 'is_active' => true]);

        $this->from(route('public.book'))->post(route('public.book.store'), [
            'full_name' => 'Juan Dela Cruz',
            'appointment_date' => now()->addDay()->toDateString(),
            'service' => 'Consultation',
        ])->assertRedirect(route('public.book'))->assertSessionHasErrors('email');
    }

    public function test_public_booking_rejects_an_invalid_email_address(): void
    {
        Service::create(['name' => 'Consultation', 'is_active' => true]);

        $this->from(route('public.book'))->post(route('public.book.store'), [
            'full_name' => 'Juan Dela Cruz',
            'email' => 'not-an-email',
            'appointment_date' => now()->addDay()->toDateString(),
            'service' => 'Consultation',
        ])->assertRedirect(route('public.book'))->assertSessionHasErrors('email');
    }
}
