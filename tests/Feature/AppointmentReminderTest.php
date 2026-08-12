<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionalEmail;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\UpcomingAppointmentReminder;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_upcoming_confirmed_appointment_queues_one_email_and_one_dentist_notification(): void
    {
        Queue::fake();
        $appointment = Appointment::factory()->create([
            'status' => 'confirmed',
            'email' => 'patient@example.test',
        ]);
        $appointment->update([
            'scheduled_start_at' => now()->addHours(20),
            'scheduled_end_at' => now()->addHours(21),
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();
        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertDatabaseHas('email_deliveries', [
            'event_type' => 'appointment_reminder',
            'related_id' => $appointment->id,
            'recipient' => 'patient@example.test',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('email_deliveries', 1);
        $this->assertDatabaseHas('notifications', [
            'type' => 'appointment_reminder',
            'notifiable_id' => $appointment->dentist_id,
            'notifiable_type' => (new User)->getMorphClass(),
        ]);
        $this->assertDatabaseCount('notifications', 1);
        Queue::assertPushed(SendTransactionalEmail::class, 1);

        $notification = $appointment->dentist->notifications()->firstOrFail();
        $this->assertSame($appointment->id, $notification->data['appointment_id']);
        $this->assertSame($appointment->full_name, $notification->data['patient_name']);
        $this->assertSame('appointment_reminder', $notification->data['type']);
        $this->assertSame(route('appointments.index', ['appointment' => $appointment->id], false), $notification->data['url']);
    }

    public function test_reminders_skip_ineligible_appointments(): void
    {
        Queue::fake();

        Appointment::factory()->create(['status' => 'pending'])->update(['scheduled_start_at' => now()->addHours(12)]);
        Appointment::factory()->create(['status' => 'cancelled'])->update(['scheduled_start_at' => now()->addHours(12)]);
        Appointment::factory()->create(['status' => 'completed'])->update(['scheduled_start_at' => now()->addHours(12)]);
        Appointment::factory()->create(['status' => 'confirmed'])->update(['scheduled_start_at' => now()->addDays(2)]);
        Appointment::factory()->create(['status' => 'confirmed'])->update(['scheduled_start_at' => now()->subHour()]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('email_deliveries', 0);
        Queue::assertNothingPushed();
    }

    public function test_missing_email_still_creates_the_dentist_notification(): void
    {
        Queue::fake();
        $appointment = $this->upcomingAppointment(['email' => null]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('email_deliveries', 0);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $appointment->dentist_id]);
        Queue::assertNothingPushed();
    }

    public function test_missing_or_inactive_dentist_skips_notification_but_still_queues_email(): void
    {
        Queue::fake();
        $missing = $this->upcomingAppointment(['dentist_id' => null]);
        $inactiveDentist = User::factory()->dentist()->create(['status' => 'inactive']);
        $inactive = $this->upcomingAppointment(['dentist_id' => $inactiveDentist->id]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('email_deliveries', ['related_id' => $missing->id]);
        $this->assertDatabaseHas('email_deliveries', ['related_id' => $inactive->id]);
        Queue::assertPushed(SendTransactionalEmail::class, 2);
    }

    public function test_reassigning_an_appointment_notifies_the_new_dentist_without_duplicate_email(): void
    {
        Queue::fake();
        $appointment = $this->upcomingAppointment();
        $originalDentistId = $appointment->dentist_id;

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $newDentist = User::factory()->dentist()->create();
        $appointment->update(['dentist_id' => $newDentist->id]);
        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $originalDentistId]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $newDentist->id]);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('email_deliveries', 1);
        Queue::assertPushed(SendTransactionalEmail::class, 1);
    }

    public function test_failed_email_delivery_is_requeued_without_creating_a_second_delivery_record(): void
    {
        Queue::fake();
        $appointment = $this->upcomingAppointment();
        $this->artisan('appointments:send-reminders')->assertSuccessful();
        $appointment->emailDeliveries()->where('event_type', 'appointment_reminder')->update([
            'status' => 'failed',
            'error' => 'Provider unavailable',
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('email_deliveries', 1);
        $this->assertDatabaseHas('email_deliveries', [
            'related_id' => $appointment->id,
            'status' => 'pending',
            'error' => null,
        ]);
        $this->assertDatabaseCount('notifications', 1);
        Queue::assertPushed(SendTransactionalEmail::class, 2);
    }

    public function test_email_channel_failure_does_not_suppress_dentist_notification(): void
    {
        $appointment = $this->upcomingAppointment();
        $dispatcher = Mockery::mock(TransactionalEmailDispatcher::class);
        $dispatcher->shouldReceive('dispatchOnce')->once()->andThrow(new RuntimeException('Email unavailable'));
        $this->app->instance(TransactionalEmailDispatcher::class, $dispatcher);

        $this->artisan('appointments:send-reminders')->assertFailed();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $appointment->dentist_id]);
        $this->assertDatabaseCount('email_deliveries', 0);
    }

    public function test_notification_channel_failure_does_not_suppress_patient_email(): void
    {
        Queue::fake();
        $appointment = $this->upcomingAppointment();
        Event::listen(NotificationSending::class, fn () => throw new RuntimeException('Notification unavailable'));

        $this->artisan('appointments:send-reminders')->assertFailed();

        $this->assertDatabaseHas('email_deliveries', ['related_id' => $appointment->id]);
        $this->assertDatabaseCount('notifications', 0);
        Queue::assertPushed(SendTransactionalEmail::class, 1);
    }

    public function test_notification_bell_shows_recent_unread_appointment_details(): void
    {
        $dentist = User::factory()->dentist()->create();
        $appointment = $this->upcomingAppointment(['dentist_id' => $dentist->id]);
        $dentist->notify(new UpcomingAppointmentReminder($appointment->load('serviceItems')));

        $this->actingAs($dentist)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Appointment notifications');

        $this->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.patient_name', $appointment->full_name)
            ->assertJsonPath('notifications.0.scheduled_at', $appointment->scheduled_start_at->setTimezone('Asia/Manila')->format('M j, Y \a\t g:i A'))
            ->assertJsonCount(1, 'notifications');
    }

    public function test_opening_and_marking_notifications_enforces_ownership(): void
    {
        $dentist = User::factory()->dentist()->create();
        $otherDentist = User::factory()->dentist()->create();
        $appointment = $this->upcomingAppointment(['dentist_id' => $dentist->id]);
        $dentist->notify(new UpcomingAppointmentReminder($appointment->load('serviceItems')));
        $notification = $dentist->notifications()->firstOrFail();

        $this->actingAs($otherDentist)
            ->patch(route('notifications.read', $notification))
            ->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($dentist)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('appointments.index', ['appointment' => $appointment->id]));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_only_updates_the_authenticated_users_notifications(): void
    {
        $dentist = User::factory()->dentist()->create();
        $otherDentist = User::factory()->dentist()->create();
        $first = $this->upcomingAppointment(['dentist_id' => $dentist->id]);
        $second = $this->upcomingAppointment(['dentist_id' => $otherDentist->id]);
        $dentist->notify(new UpcomingAppointmentReminder($first->load('serviceItems')));
        $otherDentist->notify(new UpcomingAppointmentReminder($second->load('serviceItems')));

        $this->actingAs($dentist)
            ->from(route('dashboard'))
            ->patch(route('notifications.read-all'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, $dentist->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $otherDentist->fresh()->unreadNotifications()->count());
    }

    private function upcomingAppointment(array $attributes = []): Appointment
    {
        $appointment = Appointment::factory()->create([
            'status' => 'confirmed',
            'email' => 'patient@example.test',
            ...$attributes,
        ]);

        $appointment->update([
            'scheduled_start_at' => now()->addHours(20),
            'scheduled_end_at' => now()->addHours(21),
        ]);

        return $appointment->fresh();
    }
}
