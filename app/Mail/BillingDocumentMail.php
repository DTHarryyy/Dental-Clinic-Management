<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BillingDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $documentData,
        private string $pdf,
        private string $filename,
    ) {}

    public function subjectLine(): string
    {
        return sprintf(
            '%s %s — Aquilizan Dental Clinic',
            $this->documentData['title'],
            $this->documentData['invoice']->invoice_number,
        );
    }

    public function envelope(): Envelope
    {
        $clinic = $this->documentData['clinic'];

        return new Envelope(
            replyTo: filled($clinic->email) ? [new Address($clinic->email, $clinic->clinic_name)] : [],
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.billing.document',
            text: 'mail.billing.document-text',
            with: $this->documentData,
        );
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->pdf, $this->filename)->withMime('application/pdf')];
    }
}
