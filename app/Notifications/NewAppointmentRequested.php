<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Ramsey\Uuid\Uuid;

class NewAppointmentRequested extends Notification
{
    // Id is keyed per (appointment, recipient): NotificationSender reuses a preset id across
    // every notifiable, so a fixed appointment-only id would collide on the notifications
    // table's single-column primary key once more than one recipient is notified.
    public function __construct(public Appointment $appointment, User $recipient)
    {
        $this->id = (string) Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            "appointment-requested/{$appointment->id}/user/{$recipient->id}",
        );
    }

    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(User $notifiable): string
    {
        return 'appointment_requested';
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'type' => 'appointment_requested',
            'appointment_id' => $this->appointment->id,
            'patient_name' => $this->appointment->full_name,
            'services' => $this->appointment->service_names,
            'requested_start_at' => $this->appointment->requested_start_at?->toIso8601String(),
            'requested_end_at' => $this->appointment->requested_end_at?->toIso8601String(),
            'url' => route('appointments.index', ['appointment' => $this->appointment->id], false),
        ];
    }
}
