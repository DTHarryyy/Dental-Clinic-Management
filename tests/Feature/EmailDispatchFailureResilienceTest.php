<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

/**
 * The current working tree wraps every TransactionalEmailDispatcher call site in a
 * try/catch so a mail-provider outage logs an error instead of rolling back or 500ing a
 * user-facing action. None of those sites had a test proving that — this covers the
 * three the portal plan called out: booking, cancellation, and password reset.
 */
class EmailDispatchFailureResilienceTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_a_booking_still_succeeds_when_the_email_dispatcher_throws(): void
    {
        User::factory()->dentist()->create(['status' => 'active']);
        [$user] = $this->linkedPatient('mail-outage-booking@example.test');
        $service = Service::create(['name' => 'Check-up', 'price' => 800, 'duration_minutes' => 20]);
        $date = now()->addWeek()->toDateString();

        $emails = Mockery::mock(TransactionalEmailDispatcher::class);
        $emails->shouldReceive('dispatchOnce')->once()->andThrow(new RuntimeException('Mail provider is down.'));
        $this->app->instance(TransactionalEmailDispatcher::class, $emails);

        $this->actingAs($user)->post(route('patient.appointments.store'), [
            'requested_start_at' => "{$date} 08:00",
            'service_ids' => [$service->id],
        ])->assertRedirect();

        $this->assertDatabaseCount('appointments', 1);
        $this->assertSame('pending', Appointment::sole()->status);
    }

    public function test_a_cancellation_still_succeeds_when_the_email_dispatcher_throws(): void
    {
        $appointment = Appointment::factory()->create(['status' => 'confirmed', 'email' => 'patient@example.test']);

        $emails = Mockery::mock(TransactionalEmailDispatcher::class);
        $emails->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Mail provider is down.'));
        $this->app->instance(TransactionalEmailDispatcher::class, $emails);

        $this->actingAs(User::factory()->admin()->create())->post(route('appointments.status', $appointment), [
            'status' => 'cancelled', 'cancellation_reason' => 'Dentist became unavailable.',
        ])->assertRedirect();

        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_a_password_reset_request_still_succeeds_when_the_email_dispatcher_throws(): void
    {
        User::factory()->create(['email' => 'reset-outage@example.test', 'status' => 'active', 'supabase_uid' => 'uid-outage-1']);

        $emails = Mockery::mock(TransactionalEmailDispatcher::class);
        $emails->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Mail provider is down.'));
        $this->app->instance(TransactionalEmailDispatcher::class, $emails);

        $this->post(route('password.email'), ['email' => 'reset-outage@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status');
    }
}
