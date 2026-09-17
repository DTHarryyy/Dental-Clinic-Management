<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferredTimeSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private string $testDate;

    protected function setUp(): void
    {
        parent::setUp();

        // A week out clears both the booking lead time and, unlike a hardcoded literal,
        // never drifts into the past as the calendar moves forward.
        $this->testDate = now()->addWeek()->toDateString();
    }

    private function pending(Service $service, array $attributes = []): Appointment
    {
        $appointment = Appointment::factory()->create([...$attributes, 'status' => 'pending',
            'preferred_date' => $this->testDate, 'preferred_time_window' => 'morning']);
        $appointment->serviceItems()->create(['service_id' => $service->id, 'name_snapshot' => $service->name,
            'price_snapshot' => $service->price, 'duration_minutes_snapshot' => $service->duration_minutes, 'display_order' => 0]);
        return $appointment;
    }

    private function confirm(Appointment $appointment, User $dentist, string $start, array $extra = [])
    {
        return $this->actingAs(User::factory()->admin()->create())->post(route('appointments.status', $appointment), [
            'status' => 'confirmed', 'dentist_id' => $dentist->id, 'scheduled_start_at' => $start, ...$extra,
        ]);
    }

    public function test_patient_booking_snapshots_multiple_services_and_totals(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $patient = Patient::factory()->create(['first_name' => 'Multi', 'last_name' => 'Patient', 'email' => 'multi@example.test', 'status' => 'active']);
        $user = User::factory()->patient()->create(['patient_id' => $patient->id, 'email' => 'multi@example.test']);
        $services = collect([
            Service::create(['name' => 'Exam', 'price' => 500, 'duration' => '30 min', 'duration_minutes' => 30]),
            Service::create(['name' => 'Cleaning', 'price' => 1500, 'duration' => '45 min', 'duration_minutes' => 45]),
        ]);
        $date = now()->addWeek()->toDateString();
        $this->actingAs($user)->post(route('patient.appointments.store'), [
            'requested_start_at' => "{$date} 08:00",
            'service_ids' => $services->pluck('id')->all(),
        ])->assertRedirect();
        $appointment = Appointment::with('serviceItems')->where('email', 'multi@example.test')->firstOrFail();
        $this->assertSame(75, $appointment->total_duration_minutes);
        $this->assertSame(2000.0, $appointment->estimated_total);
    }

    public function test_confirmation_requires_dentist_and_exact_start(): void
    {
        $appointment = $this->pending(Service::create(['name' => 'Exam', 'price' => 500, 'duration_minutes' => 30]), ['dentist_id' => null, 'appointment_time' => null, 'scheduled_start_at' => null, 'scheduled_end_at' => null]);
        $this->actingAs(User::factory()->admin()->create())->post(route('appointments.status', $appointment), ['status' => 'confirmed'])
            ->assertSessionHasErrors(['dentist_id', 'scheduled_start_at']);
        $this->assertSame('pending', $appointment->fresh()->status);
    }

    public function test_same_dentist_overlap_is_rejected_but_different_dentist_is_allowed(): void
    {
        $service = Service::create(['name' => 'Cleaning', 'price' => 1500, 'duration_minutes' => 60]);
        $first = $this->pending($service);
        $second = $this->pending($service, ['created_at' => now()->addSecond()]);
        $third = $this->pending($service, ['created_at' => now()->addSeconds(2)]);
        $dentist = User::factory()->dentist()->create();
        $other = User::factory()->dentist()->create();
        $this->confirm($first, $dentist, "{$this->testDate} 08:00")->assertRedirect();
        $this->confirm($second, $dentist, "{$this->testDate} 08:30", ['priority_override_reason' => 'Patient urgency'])
            ->assertSessionHasErrors('scheduled_start_at');
        $this->confirm($third, $other, "{$this->testDate} 08:30", ['priority_override_reason' => 'Other dentist available'])->assertRedirect();
        $this->assertSame('confirmed', $third->fresh()->status);
    }

    public function test_availability_respects_combined_duration_and_lunch(): void
    {
        $service = Service::create(['name' => 'Long visit', 'price' => 2000, 'duration_minutes' => 90]);
        $appointment = $this->pending($service);
        $dentist = User::factory()->dentist()->create();
        $slots = $this->actingAs(User::factory()->admin()->create())->getJson(route('appointments.availability', $appointment).'?dentist_id='.$dentist->id.'&date='.$this->testDate)
            ->assertOk()->json('slots');
        $labels = collect($slots)->pluck('label');
        $this->assertTrue($labels->contains('10:30 AM'));
        $this->assertFalse($labels->contains('11:00 AM'));
        $this->assertTrue($labels->contains('3:30 PM'));
        $this->assertFalse($labels->contains('4:00 PM'));
    }

    public function test_patient_booking_uses_exact_ranges_and_pending_requests_hold_capacity(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $patient = Patient::factory()->create(['first_name' => 'First', 'last_name' => 'Patient', 'email' => 'first@example.test', 'status' => 'active']);
        $user = User::factory()->patient()->create(['patient_id' => $patient->id, 'email' => 'first@example.test']);
        $service = Service::create(['name' => 'One hour visit', 'price' => 1000, 'duration_minutes' => 60]);
        $date = now()->addWeek()->toDateString();
        $start = "{$date} 08:00";

        $this->actingAs($user)->post(route('patient.appointments.store'), [
            'requested_start_at' => $start,
            'service_ids' => [$service->id],
        ])->assertRedirect();

        $appointment = Appointment::where('email', 'first@example.test')->firstOrFail();
        $this->assertSame('8:00 AM', $appointment->requested_start_at->setTimezone('Asia/Manila')->format('g:i A'));
        $this->assertSame('9:00 AM', $appointment->requested_end_at->setTimezone('Asia/Manila')->format('g:i A'));

        $slots = $this->getJson(route('public.book.availability', ['date' => $date, 'service_ids' => [$service->id]]))
            ->assertOk()->json('slots');
        $this->assertFalse(collect($slots)->firstWhere('label', '8:00 AM')['available']);
        $this->assertFalse(collect($slots)->firstWhere('label', '8:30 AM')['available']);
        $this->assertTrue(collect($slots)->firstWhere('label', '9:00 AM')['available']);
    }

    public function test_patient_date_and_slot_endpoints_feed_the_seven_day_selector(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $patient = Patient::factory()->create(['email' => 'selector@example.test', 'status' => 'active']);
        $user = User::factory()->patient()->create(['patient_id' => $patient->id, 'email' => 'selector@example.test']);
        $service = Service::create(['name' => 'Selector Exam', 'price' => 800, 'duration_minutes' => 30]);
        $date = now('Asia/Manila')->addWeek()->toDateString();

        $days = $this->actingAs($user)->getJson(route('patient.appointments.dates', [
            'start_date' => $date,
            'service_ids' => [$service->id],
        ]))->assertOk()
            ->assertJsonCount(7, 'days')
            ->json('days');

        $this->assertTrue(collect($days)->contains(fn (array $day) => $day['date'] === $date && $day['open']));

        $slots = $this->actingAs($user)->getJson(route('patient.appointments.slots', [
            'date' => $date,
            'service_ids' => [$service->id],
        ]))->assertOk()
            ->assertJsonPath('duration', 30)
            ->assertJsonPath('estimated_total', 800)
            ->json('slots');

        $this->assertTrue(collect($slots)->contains(fn (array $slot) => $slot['available'] && $slot['label'] === '8:00 AM'));
    }

    public function test_staff_can_override_duration_in_thirty_minute_steps(): void
    {
        $service = Service::create(['name' => 'Exam', 'price' => 500, 'duration_minutes' => 30]);
        $appointment = $this->pending($service, [
            'requested_start_at' => "{$this->testDate} 00:00:00", 'requested_end_at' => "{$this->testDate} 00:30:00",
        ]);
        $dentist = User::factory()->dentist()->create();

        $this->confirm($appointment, $dentist, "{$this->testDate} 08:00", [
            'duration_minutes' => 60, 'preference_change_acknowledged' => 1,
        ])->assertRedirect();

        $this->assertSame(60, $appointment->fresh()->duration_minutes);
        $this->assertEquals(60, $appointment->fresh()->scheduled_start_at->diffInMinutes($appointment->fresh()->scheduled_end_at));
    }

    public function test_staff_can_confirm_a_duration_that_is_not_a_multiple_of_thirty(): void
    {
        // Two services can legitimately sum to a duration like 65 minutes; staff must still be
        // able to confirm it as long as the START lands on a 30-minute boundary.
        $service = Service::create(['name' => 'Odd duration visit', 'price' => 900, 'duration_minutes' => 65]);
        $appointment = $this->pending($service, [
            'requested_start_at' => "{$this->testDate} 00:00:00", 'requested_end_at' => "{$this->testDate} 01:05:00",
        ]);
        $dentist = User::factory()->dentist()->create();

        $this->confirm($appointment, $dentist, "{$this->testDate} 08:00", [
            'duration_minutes' => 65, 'preference_change_acknowledged' => 1,
        ])->assertRedirect();

        $this->assertSame(65, $appointment->fresh()->duration_minutes);
        $this->assertEquals(65, $appointment->fresh()->scheduled_start_at->diffInMinutes($appointment->fresh()->scheduled_end_at));
    }

    public function test_matching_fcfs_sessions_share_a_dentist_but_block_exact_appointments(): void
    {
        $service = Service::create(['name' => 'Queue visit', 'price' => 500, 'duration_minutes' => 30]);
        $first = $this->pending($service);
        $second = $this->pending($service, ['created_at' => now()->addSecond()]);
        $exact = $this->pending($service, ['created_at' => now()->addSeconds(2)]);
        $dentist = User::factory()->dentist()->create();
        $session = ['scheduling_mode' => 'first_come', 'session_end_at' => "{$this->testDate} 12:00"];

        $this->confirm($first, $dentist, "{$this->testDate} 08:00", $session)->assertRedirect();
        $this->confirm($second, $dentist, "{$this->testDate} 08:00", $session)->assertRedirect();
        $this->confirm($exact, $dentist, "{$this->testDate} 09:00", [
            'duration_minutes' => 30, 'preference_change_acknowledged' => 1, 'priority_override_reason' => 'Urgent',
        ])->assertSessionHasErrors('scheduled_start_at');

        $this->assertSame('first_come', $first->fresh()->scheduling_mode);
        $this->assertNull($first->fresh()->duration_minutes);
        $this->assertSame('confirmed', $second->fresh()->status);
        $this->assertSame('pending', $exact->fresh()->status);
    }
}
