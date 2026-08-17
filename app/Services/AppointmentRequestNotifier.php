<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\NewAppointmentRequested;

class AppointmentRequestNotifier
{
    /** Notifies front-desk staff (and the assigned dentist, if any) that a new booking needs review. */
    public function notify(Appointment $appointment, ?User $actor = null): void
    {
        $recipients = User::query()
            ->where('status', UserStatus::Active->value)
            ->where(function ($query) use ($appointment) {
                $query->whereIn('role', [Role::Admin->value, Role::Receptionist->value]);

                if ($appointment->dentist_id) {
                    $query->orWhere('id', $appointment->dentist_id);
                }
            })
            ->when($actor, fn ($query) => $query->whereKeyNot($actor->id))
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new NewAppointmentRequested($appointment, $recipient));
        }
    }
}
