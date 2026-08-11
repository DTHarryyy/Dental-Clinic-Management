<?php

namespace App\Services;

use App\Jobs\SendBillingDocumentEmail;
use App\Models\BillingEmailDelivery;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BillingEmailDispatcher
{
    public function queue(Invoice $invoice, string $documentType, string $trigger = 'automatic'): ?BillingEmailDelivery
    {
        $invoice->loadMissing('patient');
        $recipient = $invoice->patient?->email;

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            if ($trigger === 'manual') {
                throw ValidationException::withMessages([
                    'email' => 'Add a valid email to the patient record before sending this document.',
                ]);
            }

            return null;
        }

        $delivery = $invoice->emailDeliveries()->create([
            'idempotency_key' => (string) Str::uuid(),
            'document_type' => $documentType,
            'recipient' => $recipient,
            'trigger' => $trigger,
            'status' => 'queued',
        ]);

        SendBillingDocumentEmail::dispatch($delivery->id)->afterCommit();

        return $delivery;
    }
}
