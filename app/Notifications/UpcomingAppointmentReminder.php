<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Ramsey\Uuid\Uuid;

class UpcomingAppointmentReminder extends Notification
{
    public function __construct(public Appointment $appointment)
    {
        $this->id = (string) Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            "appointment-reminder/{$appointment->id}/dentist/{$appointment->dentist_id}",
        );
    }

    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(User $notifiable): string
    {
        return 'appointment_reminder';
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'type' => 'appointment_reminder',
            'appointment_id' => $this->appointment->id,
            'patient_name' => $this->appointment->full_name,
            'services' => $this->appointment->service_names,
            'scheduled_start_at' => $this->appointment->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $this->appointment->scheduled_end_at?->toIso8601String(),
            'url' => route('appointments.index', ['appointment' => $this->appointment->id], false),
        ];
    }
}
