<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\UpcomingAppointmentReminder;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Queue reminders for confirmed appointments occurring within the next 24 hours';

    public function handle(TransactionalEmailDispatcher $emails): int
    {
        $now = now();
        $emailQueued = 0;
        $notificationsCreated = 0;
        $skipped = 0;
        $failures = 0;

        Appointment::query()
            ->where('status', 'confirmed')
            ->whereNotNull('scheduled_start_at')
            ->where('scheduled_start_at', '>', $now)
            ->where('scheduled_start_at', '<=', $now->copy()->addDay())
            ->with(['dentist:id,name,status', 'serviceItems'])
            ->orderBy('scheduled_start_at')
            ->chunkById(100, function ($appointments) use ($emails, &$emailQueued, &$notificationsCreated, &$skipped, &$failures): void {
                foreach ($appointments as $appointment) {
                    $handled = false;

                    if (filter_var($appointment->email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $emailKey = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, "appointment-reminder-email/{$appointment->id}");
                            if ($emails->dispatchOnce('appointment_reminder', $appointment->email, $appointment, $emailKey)) {
                                $emailQueued++;
                                $handled = true;
                            }
                        } catch (Throwable $exception) {
                            $failures++;
                            $this->recordFailure($appointment, 'email', $exception);
                        }
                    }

                    if ($appointment->dentist?->status === 'active') {
                        try {
                            $notification = new UpcomingAppointmentReminder($appointment);
                            if (! $appointment->dentist->notifications()->whereKey($notification->id)->exists()) {
                                $appointment->dentist->notify($notification);
                                $notificationsCreated++;
                                $handled = true;
                            }
                        } catch (Throwable $exception) {
                            $failures++;
                            $this->recordFailure($appointment, 'in_app', $exception);
                        }
                    }

                    if (! $handled) {
                        $skipped++;
                    }
                }
            });

        $this->info("Patient emails queued: {$emailQueued}");
        $this->info("Dentist notifications created: {$notificationsCreated}");
        $this->info("Appointments skipped: {$skipped}");
        $this->info("Channel failures: {$failures}");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function recordFailure(Appointment $appointment, string $channel, Throwable $exception): void
    {
        Log::error('Unable to create appointment reminder.', [
            'appointment_id' => $appointment->id,
            'channel' => $channel,
            'exception' => $exception,
        ]);

        $this->error("Appointment {$appointment->id} {$channel} reminder failed: {$exception->getMessage()}");
    }
}
