<?php

namespace App\Services;

use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Mail;

class AppointmentConfirmationSender
{
    public function __construct(private ResendAppointmentClient $resend) {}

    public function send(Appointment $appointment, ClinicSetting $clinic): ?string
    {
        $mailable = new AppointmentConfirmed($appointment, $clinic);

        if (config('mail.default') !== 'resend') {
            Mail::to($appointment->email)->send($mailable);

            return null;
        }

        $parameters = [
            'from' => sprintf('%s <%s>', config('mail.from.name'), config('mail.from.address')),
            'to' => [$appointment->email],
            'subject' => 'Appointment Confirmed — Aquilizan Dental Clinic',
            'html' => view('mail.appointments.confirmed', compact('appointment', 'clinic'))->render(),
            'text' => view('mail.appointments.confirmed-text', compact('appointment', 'clinic'))->render(),
        ];

        if (filled($clinic->email)) {
            $parameters['reply_to'] = [$clinic->email];
        }

        return $this->resend->send($parameters, ['idempotency_key' => "appointment-confirmation/{$appointment->id}"]);
    }
}
