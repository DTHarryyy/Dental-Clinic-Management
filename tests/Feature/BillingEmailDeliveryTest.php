<?php

namespace Tests\Feature;

use App\Jobs\SendBillingDocumentEmail;
use App\Models\BillingEmailDelivery;
use App\Models\ClinicSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Services\BillingDocument;
use App\Services\BillingDocumentSender;
use App\Services\ResendAppointmentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BillingEmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function delivery(string $type = 'invoice'): BillingEmailDelivery
    {
        $invoice = Invoice::create([
            'patient_id' => Patient::factory()->create(['email' => 'patient@example.com'])->id,
            'invoice_date' => '2026-08-11',
            'subtotal' => 1500,
            'discount' => 0,
            'total' => 1500,
            'payment_status' => $type === 'receipt' ? 'paid' : 'unpaid',
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'description' => 'Cleaning', 'qty' => 1, 'price' => 1500]);

        return BillingEmailDelivery::create([
            'invoice_id' => $invoice->id,
            'idempotency_key' => '9496e80f-4bc5-48d7-abca-20020bc7d31c',
            'document_type' => $type,
            'recipient' => 'patient@example.com',
            'trigger' => 'automatic',
            'status' => 'queued',
        ]);
    }

    public function test_resend_receives_the_document_and_pdf_attachment(): void
    {
        config()->set('mail.default', 'resend');
        config()->set('mail.from.address', 'appointments@aquilizan.com');
        config()->set('mail.from.name', 'Aquilizan Dental Clinic');
        config()->set('services.resend.key', 'test-key');
        ClinicSetting::current()->update(['email' => 'clinic@aquilizan.com']);
        $delivery = $this->delivery();

        $client = Mockery::mock(ResendAppointmentClient::class);
        $client->shouldReceive('send')->once()->with(
            Mockery::on(fn (array $payload) => $payload['to'] === ['patient@example.com']
                && $payload['from'] === 'Aquilizan Dental Clinic <appointments@aquilizan.com>'
                && $payload['reply_to'] === ['clinic@aquilizan.com']
                && $payload['attachments'][0]['filename'] === 'INV-0001.pdf'
                && base64_decode($payload['attachments'][0]['content'], true) !== false
                && str_contains($payload['html'], 'Invoice')),
            ['idempotency_key' => 'billing-delivery/9496e80f-4bc5-48d7-abca-20020bc7d31c'],
        )->andReturn('resend-billing-id');

        $sender = new BillingDocumentSender($client, app(BillingDocument::class));
        (new SendBillingDocumentEmail($delivery->id))->handle($sender);

        $delivery->refresh();
        $this->assertSame('sent', $delivery->status);
        $this->assertSame('resend-billing-id', $delivery->provider_message_id);
        $this->assertNotNull($delivery->sent_at);
    }

    public function test_delivery_failure_is_audited_without_changing_invoice_state(): void
    {
        $delivery = $this->delivery();
        $sender = Mockery::mock(BillingDocumentSender::class);
        $sender->shouldReceive('send')->once()->andThrow(new RuntimeException('Provider unavailable'));

        try {
            (new SendBillingDocumentEmail($delivery->id))->handle($sender);
            $this->fail('The exception should be rethrown for queue retry.');
        } catch (RuntimeException) {
            // Expected.
        }

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame('Provider unavailable', $delivery->error);
        $this->assertSame('unpaid', $delivery->invoice->payment_status);
    }

    public function test_already_sent_delivery_is_not_sent_twice(): void
    {
        $delivery = $this->delivery();
        $delivery->update(['status' => 'sent', 'sent_at' => now()]);
        $sender = Mockery::mock(BillingDocumentSender::class);
        $sender->shouldNotReceive('send');

        (new SendBillingDocumentEmail($delivery->id))->handle($sender);
    }
}
