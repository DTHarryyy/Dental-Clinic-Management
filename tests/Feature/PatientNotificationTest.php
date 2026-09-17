<?php

namespace Tests\Feature;

use App\Notifications\PatientPortalAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PatientNotificationTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_index_lists_only_the_authenticated_patients_notifications(): void
    {
        [$user] = $this->linkedPatient('bell-owner@example.test');
        [$otherUser] = $this->linkedPatient('bell-stranger@example.test');
        $user->notify(new PatientPortalAlert('appointment_confirmed', 'Confirmed', 'Your visit is confirmed.', '/patient/appointments/1', 1));
        $otherUser->notify(new PatientPortalAlert('appointment_confirmed', 'Confirmed', 'Not yours.', '/patient/appointments/2', 2));

        $this->actingAs($user)->get(route('patient.notifications.index'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 1);
    }

    public function test_read_marks_a_single_notification_and_is_scoped_to_the_owner(): void
    {
        [$user] = $this->linkedPatient('bell-read@example.test');
        [$otherUser] = $this->linkedPatient('bell-read-stranger@example.test');
        $user->notify(new PatientPortalAlert('appointment_confirmed', 'Confirmed', 'Your visit is confirmed.', '/patient/appointments/1', 1));
        $otherUser->notify(new PatientPortalAlert('appointment_confirmed', 'Confirmed', 'Not yours.', '/patient/appointments/2', 2));
        $mine = $user->notifications()->firstOrFail();
        $theirs = $otherUser->notifications()->firstOrFail();

        $this->actingAs($user)->patch(route('patient.notifications.read', $mine))->assertRedirect();
        $this->assertNotNull($mine->fresh()->read_at);

        $this->actingAs($user)->patch(route('patient.notifications.read', $theirs))->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_read_all_marks_every_unread_notification_for_the_current_user_only(): void
    {
        [$user] = $this->linkedPatient('bell-readall@example.test');
        [$otherUser] = $this->linkedPatient('bell-readall-stranger@example.test');
        $user->notify(new PatientPortalAlert('appointment_confirmed', 'A', 'A', '/patient/appointments/1', 1));
        $user->notify(new PatientPortalAlert('appointment_cancelled', 'B', 'B', '/patient/appointments/2', 2));
        $otherUser->notify(new PatientPortalAlert('appointment_confirmed', 'C', 'C', '/patient/appointments/3', 3));

        $this->actingAs($user)->patch(route('patient.notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $otherUser->fresh()->unreadNotifications()->count());
    }

    public function test_open_redirects_to_the_stored_relative_url_and_marks_it_read(): void
    {
        [$user] = $this->linkedPatient('bell-open@example.test');
        $url = route('patient.appointments.index', [], false);
        $user->notify(new PatientPortalAlert('appointment_confirmed', 'Confirmed', 'Your visit is confirmed.', $url, 42));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->get(route('patient.notifications.open', $notification))
            ->assertRedirect($url);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_open_refuses_to_redirect_to_an_external_host(): void
    {
        [$user] = $this->linkedPatient('bell-open-external@example.test');
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'appointment_confirmed',
            'data' => ['title' => 'Confirmed', 'message' => 'Check this', 'url' => 'https://evil.example.test/phish'],
        ]);

        $this->actingAs($user)->get(route('patient.notifications.open', $notification))
            ->assertRedirect(route('patient.notifications.index'));
    }
}
