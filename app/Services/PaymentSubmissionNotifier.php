<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PatientPortalAlert;
use App\Notifications\PaymentSubmitted;

class PaymentSubmissionNotifier
{
    /** Notifies front-desk staff that a patient has submitted a payment for verification. */
    public function notifyStaff(Payment $payment): void
    {
        $recipients = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereIn('role', [Role::Admin->value, Role::Receptionist->value])
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new PaymentSubmitted($payment, $recipient));
        }
    }

    /** Tells the patient whether their submitted payment was verified or rejected. */
    public function notifyPatient(Payment $payment): void
    {
        $invoice = $payment->invoice;
        $patientUser = User::query()->where('patient_id', $invoice?->patient_id)->first();

        if (! $patientUser) {
            return;
        }

        if ($payment->isVerified()) {
            $patientUser->notify(new PatientPortalAlert(
                'payment_verified',
                'Payment verified',
                'Your PHP '.number_format($payment->amount, 2).' payment has been confirmed.',
                route('patient.billing.show', $invoice, false),
                $payment->id,
                (string) $patientUser->id,
            ));

            return;
        }

        $patientUser->notify(new PatientPortalAlert(
            'payment_rejected',
            'Payment could not be verified',
            $payment->rejection_reason ?: 'Please contact the clinic about your recent payment submission.',
            route('patient.billing.show', $invoice, false),
            $payment->id,
            (string) $patientUser->id,
        ));
    }
}
