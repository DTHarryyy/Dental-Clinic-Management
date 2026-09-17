<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Ramsey\Uuid\Uuid;

class PaymentSubmitted extends Notification
{
    // Id is keyed per (payment, recipient) — see NewAppointmentRequested for why a
    // fixed payment-only id would collide once more than one recipient is notified.
    public function __construct(public Payment $payment, User $recipient)
    {
        $this->id = (string) Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            "payment-submitted/{$payment->id}/user/{$recipient->id}",
        );
    }

    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(User $notifiable): string
    {
        return 'payment_submitted';
    }

    public function toDatabase(User $notifiable): array
    {
        $invoice = $this->payment->invoice;

        return [
            'type' => 'payment_submitted',
            'title' => 'Payment submitted for verification',
            'patient_name' => $invoice?->patient?->name,
            'message' => 'PHP '.number_format($this->payment->amount, 2).' via '.$this->payment->methodLabel(),
            'url' => route('billing.payments.pending', [], false),
        ];
    }
}
