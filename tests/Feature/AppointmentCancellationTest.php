<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionalEmail;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AppointmentCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancellation_requires_a_reason_and_leaves_appointment_active_when_missing(): void
    {
        $appointment = Appointment::factory()->create(['status' => 'confirmed']);
        $this->actingAs(User::factory()->admin()->create())->post(route('appointments.status', $appointment), ['status' => 'cancelled'])
            ->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_cancellation_can_create_a_pending_reschedule_with_service_snapshots_and_email(): void
    {
        Queue::fake();
        $service = Service::create(['name' => 'Cleaning', 'price' => 1500, 'duration_minutes' => 45]);
        $appointment = Appointment::factory()->create(['status' => 'confirmed', 'email' => 'patient@example.test']);
        $appointment->serviceItems()->create(['service_id' => $service->id, 'name_snapshot' => $service->name,
            'price_snapshot' => $service->price, 'duration_minutes_snapshot' => 45, 'display_order' => 0]);

        $this->actingAs(User::factory()->admin()->create())->post(route('appointments.status', $appointment), [
            'status' => 'cancelled', 'cancellation_reason' => 'Dentist became unavailable.',
            'reschedule_requested' => 1, 'reschedule_date' => now()->addWeek()->toDateString(), 'reschedule_window' => 'afternoon',
        ])->assertRedirect();

        $appointment->refresh();
        $replacement = $appointment->rescheduledAppointment()->with('serviceItems')->firstOrFail();
        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame('Dentist became unavailable.', $appointment->cancellation_reason);
        $this->assertSame('pending', $replacement->status);
        $this->assertSame('Cleaning', $replacement->service_names);
        $this->assertDatabaseHas('email_deliveries', ['event_type' => 'appointment_cancelled', 'related_id' => $appointment->id]);
        Queue::assertPushed(SendTransactionalEmail::class);
    }

    public function test_cancellation_reschedule_stores_an_exact_held_range(): void
    {
        Queue::fake();
        $service = Service::create(['name' => 'Cleaning', 'price' => 1500, 'duration_minutes' => 60]);
        $appointment = Appointment::factory()->create(['status' => 'confirmed']);
        $appointment->serviceItems()->create(['service_id' => $service->id, 'name_snapshot' => $service->name,
            'price_snapshot' => $service->price, 'duration_minutes_snapshot' => 60, 'display_order' => 0]);
        $date = now()->addWeek()->toDateString();

        $this->actingAs(User::factory()->admin()->create())->post(route('appointments.status', $appointment), [
            'status' => 'cancelled', 'cancellation_reason' => 'Patient requested another time.',
            'reschedule_requested' => 1, 'reschedule_date' => $date,
            'reschedule_start_at' => "{$date} 13:30", 'reschedule_duration_minutes' => 60,
        ])->assertRedirect();

        $replacement = $appointment->fresh()->rescheduledAppointment;
        $this->assertSame('1:30 PM', $replacement->requested_start_at->setTimezone('Asia/Manila')->format('g:i A'));
        $this->assertSame('2:30 PM', $replacement->requested_end_at->setTimezone('Asia/Manila')->format('g:i A'));
        $this->assertSame('pending', $replacement->status);
    }
}
