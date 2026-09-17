<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicClosure;
use App\Models\ClinicSetting;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the availability-performance refactor: the caching layer added to
 * AppointmentScheduler must stay correct (busts on write, respects the booking horizon, keeps
 * slot-level granularity for partial closures) now that it no longer re-queries per slot.
 */
class AvailabilityCachingTest extends TestCase
{
    use RefreshDatabase;

    private function patientUser(): User
    {
        $patient = Patient::factory()->create(['status' => 'active']);

        return User::factory()->patient()->create(['patient_id' => $patient->id]);
    }

    public function test_date_summary_marks_days_beyond_the_booking_horizon_unavailable(): void
    {
        // Set the horizon before anything calls ClinicSetting::current(), so the memoized
        // cache picks up this value rather than a stale default.
        ClinicSetting::query()->updateOrCreate([], ['booking_horizon_days' => 2]);
        User::factory()->dentist()->create(['status' => 'active']);

        $start = now('Asia/Manila')->toDateString();
        $days = app(AppointmentScheduler::class)->dateSummary($start, 30);

        $inHorizon = $days->firstWhere('date', now('Asia/Manila')->addDay()->toDateString());
        $beyondHorizon = $days->firstWhere('date', now('Asia/Manila')->addDays(5)->toDateString());

        $this->assertTrue($inHorizon['open']);
        $this->assertFalse($beyondHorizon['open']);
        $this->assertFalse($beyondHorizon['available']);
    }

    public function test_a_new_closure_takes_effect_immediately(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $date = now('Asia/Manila')->addWeek()->toDateString();

        // Warm the ClinicClosure::cached() cache before the closure exists.
        app(AppointmentScheduler::class)->dateSummary($date, 30);

        ClinicClosure::create([
            'closure_date' => $date,
            'is_full_day' => true,
            'reason' => 'Staff training day',
        ]);

        $days = app(AppointmentScheduler::class)->dateSummary($date, 30);
        $day = $days->firstWhere('date', $date);

        $this->assertFalse($day['available']);
        $this->assertSame('Staff training day', $day['reason']);
    }

    public function test_a_partial_closure_still_blocks_only_the_overlapping_slots(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $date = now('Asia/Manila')->addWeek()->toDateString();

        ClinicClosure::create([
            'closure_date' => $date,
            'is_full_day' => false,
            'starts_at' => '09:00:00',
            'ends_at' => '10:00:00',
            'reason' => 'Equipment maintenance',
        ]);

        $slots = app(AppointmentScheduler::class)->publicSlots($date, 30);

        $this->assertNull($slots->firstWhere('label', '9:00 AM'), 'the closed slot should be excluded from the grid entirely');
        $this->assertNotNull($slots->firstWhere('label', '10:00 AM'));
        $this->assertTrue($slots->firstWhere('label', '10:00 AM')['available']);
        $this->assertTrue($slots->firstWhere('label', '8:00 AM')['available']);
    }

    public function test_dates_endpoint_returns_slots_per_day_and_honors_the_preferred_selection(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $user = $this->patientUser();
        $service = Service::create(['name' => 'Day Selector Exam', 'price' => 500, 'duration_minutes' => 30]);
        $weekStart = now('Asia/Manila')->addWeek()->toDateString();
        $preferred = now('Asia/Manila')->addWeek()->addDay()->toDateString();

        $response = $this->actingAs($user)->getJson(route('patient.appointments.dates', [
            'start_date' => $weekStart, 'date' => $preferred, 'service_ids' => [$service->id],
        ]))->assertOk();

        $this->assertSame($preferred, $response->json('selected_date'));
        $day = collect($response->json('days'))->firstWhere('date', $preferred);
        $this->assertNotEmpty($day['slots']);

        // A closed preferred date falls back to the first open+available day instead.
        ClinicClosure::create(['closure_date' => $preferred, 'is_full_day' => true, 'reason' => 'Closed for training']);

        $fallback = $this->actingAs($user)->getJson(route('patient.appointments.dates', [
            'start_date' => $weekStart, 'date' => $preferred, 'service_ids' => [$service->id],
        ]))->assertOk();

        $this->assertNotSame($preferred, $fallback->json('selected_date'));
    }

    public function test_available_slots_only_loads_the_requested_day(): void
    {
        $dentist = User::factory()->dentist()->create(['status' => 'active']);
        $service = Service::create(['name' => 'Bounded Exam', 'price' => 500, 'duration_minutes' => 30]);
        $appointment = Appointment::factory()->create(['status' => 'pending', 'dentist_id' => null, 'appointment_time' => null]);
        $appointment->serviceItems()->create([
            'service_id' => $service->id, 'name_snapshot' => $service->name,
            'price_snapshot' => $service->price, 'duration_minutes_snapshot' => 30, 'display_order' => 0,
        ]);

        $farAway = now('Asia/Manila')->addYear();
        Appointment::factory()->create([
            'status' => 'confirmed', 'dentist_id' => $dentist->id,
            'scheduled_start_at' => $farAway, 'scheduled_end_at' => $farAway->clone()->addMinutes(30),
            'appointment_date' => $farAway->toDateString(), 'appointment_time' => $farAway->format('g:i A'),
        ]);

        $date = now('Asia/Manila')->addWeek()->toDateString();

        $sql = [];
        DB::listen(function ($query) use (&$sql): void { $sql[] = $query->sql; });

        $slots = app(AppointmentScheduler::class)->availableSlots($appointment, $dentist->id, $date, 30);

        $appointmentsQuery = collect($sql)->first(fn ($s) => str_contains($s, 'from "appointments"'));
        $this->assertNotNull($appointmentsQuery);
        // The bound predicate must be present - not just whereNotNull('scheduled_start_at'),
        // which alone would still load every confirmed appointment ever for this dentist.
        $this->assertStringContainsString('"scheduled_start_at" < ?', $appointmentsQuery);
        $this->assertStringContainsString('"scheduled_end_at" > ?', $appointmentsQuery);
        $this->assertTrue($slots->contains(fn (array $slot) => $slot['label'] === '8:00 AM'));
    }

    public function test_a_pending_self_booking_does_not_double_count_its_own_dentists_confirmed_slot(): void
    {
        $dentistA = User::factory()->dentist()->create(['status' => 'active']);
        User::factory()->dentist()->create(['status' => 'active']); // dentist B - should still be free

        $date = now('Asia/Manila')->addWeek()->toDateString();
        $start = \Carbon\CarbonImmutable::parse("{$date} 09:00", 'Asia/Manila')->utc();
        $end = $start->addMinutes(30);

        // Dentist A is confirmed-busy for this slot.
        Appointment::factory()->create([
            'status' => 'confirmed', 'dentist_id' => $dentistA->id,
            'scheduled_start_at' => $start, 'scheduled_end_at' => $end,
            'appointment_date' => $date, 'appointment_time' => '9:00 AM',
        ]);

        // Dentist A also has a pending self-booking overlapping the same slot (e.g. booking on
        // their own behalf) - this must not be double-subtracted on top of the confirmed row.
        Appointment::factory()->create([
            'status' => 'pending', 'dentist_id' => $dentistA->id, 'appointment_time' => null,
            'requested_start_at' => $start, 'requested_end_at' => $end,
        ]);

        $slots = app(AppointmentScheduler::class)->publicSlots($date, 30);
        $slot = $slots->firstWhere('label', '9:00 AM');

        $this->assertNotNull($slot);
        $this->assertTrue($slot['available'], 'dentist B is still free for this slot');
    }
}
