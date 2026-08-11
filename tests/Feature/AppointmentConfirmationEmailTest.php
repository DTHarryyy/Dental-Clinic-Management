<?php

namespace Tests\Feature;

use App\Jobs\SendAppointmentConfirmationEmail;
use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use App\Services\AppointmentConfirmationSender;
use App\Services\ResendAppointmentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AppointmentConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_confirmation_queues_one_email_job(): void
    {
        Queue::fake();
        $appointment = Appointment::factory()->create(['status' => 'pending']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('appointments.status', $appointment), ['status' => 'confirmed'])
            ->assertRedirect();

        Queue::assertPushed(SendAppointmentConfirmationEmail::class, 1);
    }

    public function test_reopening_a_completed_appointment_does_not_queue_email(): void
    {
        Queue::fake();
        $appointment = Appointment::factory()->create(['status' => 'completed']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('appointments.status', $appointment), ['status' => 'confirmed'])
            ->assertRedirect();

        Queue::assertNotPushed(SendAppointmentConfirmationEmail::class);
    }

    public function test_an_appointment_without_email_is_confirmed_without_queueing_email(): void
    {
        Queue::fake();
        $appointment = Appointment::factory()->create(['status' => 'pending', 'email' => null]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('appointments.status', $appointment), ['status' => 'confirmed'])
            ->assertRedirect();

        $this->assertSame('confirmed', $appointment->fresh()->status);
        Queue::assertNotPushed(SendAppointmentConfirmationEmail::class);
    }

    public function test_templates_show_booking_details_and_exclude_the_concern(): void
    {
        $dentist = User::factory()->dentist()->create(['name' => 'Dr. Maria Santos']);
        $appointment = Appointment::factory()->create([
            'dentist_id' => $dentist->id,
            'full_name' => 'Juan Dela Cruz',
            'service' => 'Consultation',
            'appointment_date' => '2026-08-20',
            'appointment_time' => null,
            'concern' => 'Private clinical concern',
            'status' => 'confirmed',
        ])->load('dentist');
        $clinic = ClinicSetting::current();
        $clinic->update([
            'clinic_name' => 'Aquilizan Dental Clinic',
            'phone' => '09123456789',
            'email' => 'clinic@aquilizan.com',
            'address' => 'Clinic Address',
            'website' => 'https://aquilizan.com',
        ]);

        $mail = new AppointmentConfirmed($appointment, $clinic);
        $html = $mail->render();
        $text = view('mail.appointments.confirmed-text', compact('appointment', 'clinic'))->render();

        foreach (['Juan Dela Cruz', 'Consultation', 'August 20, 2026', 'Time to be arranged', 'Dr. Maria Santos', 'APT-'.str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT)] as $detail) {
            $this->assertStringContainsString($detail, $html);
            $this->assertStringContainsString($detail, $text);
        }

        $this->assertStringNotContainsString('Private clinical concern', $html);
        $this->assertStringNotContainsString('Private clinical concern', $text);
    }

    public function test_resend_payload_and_delivery_tracking_are_recorded(): void
    {
        config()->set('mail.default', 'resend');
        config()->set('mail.from.address', 'appointments@aquilizan.com');
        config()->set('mail.from.name', 'Aquilizan Dental Clinic');
        config()->set('services.resend.key', 'test-key');

        $appointment = Appointment::factory()->create([
            'status' => 'confirmed',
            'email' => 'juan@example.com',
            'confirmation_email_error' => 'Previous failure',
        ])->load('dentist');
        $clinic = ClinicSetting::current();
        $clinic->update(['email' => 'clinic@aquilizan.com']);

        $client = Mockery::mock(ResendAppointmentClient::class);
        $client->shouldReceive('send')->once()->with(
            Mockery::on(function (array $payload): bool {
                return $payload['from'] === 'Aquilizan Dental Clinic <appointments@aquilizan.com>'
                    && $payload['to'] === ['juan@example.com']
                    && $payload['reply_to'] === ['clinic@aquilizan.com']
                    && $payload['subject'] === 'Appointment Confirmed — Aquilizan Dental Clinic'
                    && str_contains($payload['html'], 'Your appointment is confirmed')
                    && ! str_contains($payload['html'], (string) 'Previous failure');
            }),
            ['idempotency_key' => "appointment-confirmation/{$appointment->id}"],
        )->andReturn('resend-message-id');

        $sender = new AppointmentConfirmationSender($client);
        (new SendAppointmentConfirmationEmail($appointment->id))->handle($sender);

        $appointment->refresh();
        $this->assertSame('resend-message-id', $appointment->confirmation_email_message_id);
        $this->assertNotNull($appointment->confirmation_email_sent_at);
        $this->assertNull($appointment->confirmation_email_error);
    }

    public function test_a_successfully_sent_confirmation_is_not_sent_twice(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => 'confirmed',
            'confirmation_email_sent_at' => now(),
        ]);
        $sender = Mockery::mock(AppointmentConfirmationSender::class);
        $sender->shouldNotReceive('send');

        (new SendAppointmentConfirmationEmail($appointment->id))->handle($sender);
    }

    public function test_delivery_failure_is_recorded_without_reverting_confirmation(): void
    {
        $appointment = Appointment::factory()->create(['status' => 'confirmed']);
        $sender = Mockery::mock(AppointmentConfirmationSender::class);
        $sender->shouldReceive('send')->once()->andThrow(new RuntimeException('Provider unavailable'));

        try {
            (new SendAppointmentConfirmationEmail($appointment->id))->handle($sender);
            $this->fail('The provider exception should be rethrown for a queue retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Provider unavailable', $exception->getMessage());
        }

        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame('Provider unavailable', $appointment->confirmation_email_error);
        $this->assertNull($appointment->confirmation_email_sent_at);
    }
}
