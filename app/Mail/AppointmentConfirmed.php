<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public ClinicSetting $clinic,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: filled($this->clinic->email)
                ? [new Address($this->clinic->email, $this->clinic->clinic_name)]
                : [],
            subject: 'Appointment Confirmed — Aquilizan Dental Clinic',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.appointments.confirmed',
            text: 'mail.appointments.confirmed-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
