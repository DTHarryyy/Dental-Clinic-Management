<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function verifyPayment(User $user, Payment $payment): bool
    {
        return $user->hasPermission(Permission::BillingManage);
    }

    /** Staff with billing view, or the patient who owns the invoice this payment belongs to. */
    public function viewProof(User $user, Payment $payment): bool
    {
        if ($user->hasPermission(Permission::BillingView)) {
            return true;
        }

        return $user->patient_id !== null
            && $user->patient_id === $payment->invoice?->patient_id;
    }
}
