<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Services\AppointmentScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

/**
 * Regression coverage for the state that produced the "Online booking is unavailable"
 * banner in production: services configured but zero active dentists. Before this fix,
 * AppointmentScheduler::publicSlots() computed availability as
 * (dentistCount - busy - held) > 0, so zero dentists was indistinguishable from a fully
 * booked day — every slot silently came back unavailable, and a forced booking attempt
 * failed with the misleading "That time is already booked" message.
 */
class ZeroDentistBookingTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_the_booking_page_explains_there_is_no_dentist_instead_of_hiding_it(): void
    {
        [$user] = $this->linkedPatient('no-dentist-page@example.test');
        Service::create(['name' => 'Check-up', 'price' => 800, 'duration_minutes' => 20]);

        $this->actingAs($user)->get(route('patient.appointments.create'))
            ->assertOk()
            ->assertSee('No dentist is accepting bookings yet.')
            ->assertViewHas('hasActiveDentist', false);
    }

    public function test_slots_report_the_specific_no_dentist_reason_not_already_booked(): void
    {
        [$user] = $this->linkedPatient('no-dentist-slots@example.test');
        $service = Service::create(['name' => 'Check-up', 'price' => 800, 'duration_minutes' => 20]);
        $date = now()->addWeek()->toDateString();

        $slots = $this->actingAs($user)->getJson(route('patient.appointments.slots', [
            'date' => $date,
            'service_ids' => [$service->id],
        ]))->assertOk()->json('slots');

        $this->assertNotEmpty($slots);
        $this->assertTrue(collect($slots)->every(fn (array $slot) => $slot['available'] === false
            && str_contains($slot['range_label'], AppointmentScheduler::NO_DENTIST_MESSAGE)));
    }

    public function test_store_fails_with_the_no_dentist_message_not_already_booked(): void
    {
        [$user] = $this->linkedPatient('no-dentist-store@example.test');
        $service = Service::create(['name' => 'Check-up', 'price' => 800, 'duration_minutes' => 20]);
        $date = now()->addWeek()->toDateString();

        $this->actingAs($user)->post(route('patient.appointments.store'), [
            'requested_start_at' => "{$date} 08:00",
            'service_ids' => [$service->id],
        ])->assertSessionHasErrors(['requested_start_at' => AppointmentScheduler::NO_DENTIST_MESSAGE]);

        $this->assertDatabaseCount('appointments', 0);
    }
}
