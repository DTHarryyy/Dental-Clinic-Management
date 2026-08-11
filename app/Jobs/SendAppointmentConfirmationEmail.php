<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Services\AppointmentConfirmationSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Str;
use Throwable;

class SendAppointmentConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $appointmentId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("appointment-confirmation-{$this->appointmentId}"))
                ->releaseAfter(30)
                ->expireAfter(300),
        ];
    }

    public function handle(AppointmentConfirmationSender $sender): void
    {
        $appointment = Appointment::with(['dentist', 'serviceItems'])->find($this->appointmentId);

        if (! $appointment || $appointment->status !== 'confirmed' || blank($appointment->email) || $appointment->confirmation_email_sent_at) {
            return;
        }

        try {
            $messageId = $sender->send($appointment, ClinicSetting::current());

            $appointment->update([
                'confirmation_email_sent_at' => now(),
                'confirmation_email_message_id' => $messageId,
                'confirmation_email_error' => null,
            ]);
        } catch (Throwable $exception) {
            $appointment->update([
                'confirmation_email_error' => $this->safeError($exception),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if (! $exception) {
            return;
        }

        Appointment::whereKey($this->appointmentId)->update([
            'confirmation_email_error' => $this->safeError($exception),
        ]);
    }

    private function safeError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return Str::limit($message, 2000, '…');
    }
}
