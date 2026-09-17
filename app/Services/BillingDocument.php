<?php

namespace App\Services;

use App\Models\ClinicSetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class BillingDocument
{
    public function data(Invoice $invoice, string $type): array
    {
        // A receipt/invoice PDF must never list a payment the clinic has not confirmed.
        $invoice->loadMissing(['patient', 'items', 'verifiedPayments.receiver', 'dentalRecord.dentist']);

        return [
            'invoice' => $invoice,
            'clinic' => ClinicSetting::current(),
            'documentType' => $type,
            'title' => $type === 'receipt' ? 'Receipt' : 'Invoice',
            'amountPaid' => $invoice->amount_paid,
            'balance' => $invoice->balance,
        ];
    }

    public function pdf(array $data): string
    {
        return Pdf::loadView('billing.pdf', $data)->setPaper('a4')->output();
    }

    public function filename(Invoice $invoice, string $type): string
    {
        return $type === 'receipt'
            ? "RECEIPT-{$invoice->invoice_number}.pdf"
            : "{$invoice->invoice_number}.pdf";
    }
}
