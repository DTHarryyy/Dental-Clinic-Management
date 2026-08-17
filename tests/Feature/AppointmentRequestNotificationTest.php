<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_notifies_admin_and_receptionist(): void
    {
        $admin = User::factory()->admin()->create();
        $receptionist = User::factory()->create();
        $unassignedDentist = User::factory()->dentist()->create();
        $service = Service::create(['name' => 'Consultation', 'is_active' => true, 'duration_minutes' => 30]);

        $this->post(route('public.book.store'), [
            'full_name' => 'Juan Dela Cruz',
            'email' => 'juan@example.test',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time_window' => 'morning',
            'service_ids' => [$service->id],
        ])->assertRedirect(route('public.book.success'));

        $appointment = Appointment::sole();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'type' => 'appointment_requested',
            'notifiable_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => 'appointment_requested',
            'notifiable_id' => $receptionist->id,
        ]);
        $this->assertSame(0, $unassignedDentist->notifications()->count());

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame($appointment->id, $notification->data['appointment_id']);
        $this->assertSame('Juan Dela Cruz', $notification->data['patient_name']);
    }

    public function test_internal_booking_notifies_staff_and_assigned_dentist_but_not_the_actor(): void
    {
        $admin = User::factory()->admin()->create();
        $receptionist = User::factory()->create();
        $dentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create(['status' => 'active']);
        $service = Service::create(['name' => 'Cleaning', 'price' => 500, 'duration' => '30 min', 'duration_minutes' => 30]);
        $date = now()->addDay()->toDateString();

        $this->actingAs($receptionist)->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'preferred_date' => $date,
            'preferred_time_window' => 'morning',
            'requested_start_at' => "{$date} 08:00",
            'dentist_id' => $dentist->id,
            'service_ids' => [$service->id],
        ])->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', ['type' => 'appointment_requested', 'notifiable_id' => $admin->id]);
        $this->assertDatabaseHas('notifications', ['type' => 'appointment_requested', 'notifiable_id' => $dentist->id]);
        $this->assertSame(0, $receptionist->notifications()->count());
    }

    public function test_bell_endpoint_labels_appointment_request_notifications(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::create(['name' => 'Consultation', 'is_active' => true, 'duration_minutes' => 30]);

        $this->post(route('public.book.store'), [
            'full_name' => 'Maria Santos',
            'email' => 'maria@example.test',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time_window' => 'morning',
            'service_ids' => [$service->id],
        ])->assertRedirect(route('public.book.success'));

        $this->actingAs($admin)->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('notifications.0.type', 'appointment_requested')
            ->assertJsonPath('notifications.0.title', 'New appointment request')
            ->assertJsonPath('notifications.0.patient_name', 'Maria Santos')
            ->assertJsonMissing(['notifications' => [['scheduled_at' => 'Schedule unavailable']]]);
    }
}
