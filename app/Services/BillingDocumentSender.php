<?php

namespace App\Services;

use App\Mail\BillingDocumentMail;
use App\Models\BillingEmailDelivery;
use Illuminate\Support\Facades\Mail;

class BillingDocumentSender
{
    public function __construct(
        private ResendAppointmentClient $resend,
        private BillingDocument $documents,
    ) {}

    public function send(BillingEmailDelivery $delivery): ?string
    {
        $invoice = $delivery->invoice;
        $data = $this->documents->data($invoice, $delivery->document_type);
        $pdf = $this->documents->pdf($data);
        $filename = $this->documents->filename($invoice, $delivery->document_type);
        $mail = new BillingDocumentMail($data, $pdf, $filename);

        if (config('mail.default') !== 'resend') {
            Mail::to($delivery->recipient)->send($mail);

            return null;
        }

        $parameters = [
            'from' => sprintf('%s <%s>', config('mail.from.name'), config('mail.from.address')),
            'to' => [$delivery->recipient],
            'subject' => $mail->subjectLine(),
            'html' => view('mail.billing.document', $data)->render(),
            'text' => view('mail.billing.document-text', $data)->render(),
            'attachments' => [['filename' => $filename, 'content' => base64_encode($pdf)]],
        ];

        if (filled($data['clinic']->email)) {
            $parameters['reply_to'] = [$data['clinic']->email];
        }

        return $this->resend->send($parameters, ['idempotency_key' => "billing-delivery/{$delivery->idempotency_key}"]);
    }
}
