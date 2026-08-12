<?php

namespace Tests\Feature;

use App\Jobs\SendBillingDocumentEmail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BillingDialogTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(?Patient $patient = null): Invoice
    {
        $invoice = Invoice::create([
            'patient_id' => ($patient ?? Patient::factory()->create())->id,
            'invoice_date' => today(),
            'due_date' => today()->addWeek(),
            'subtotal' => 2000,
            'discount' => 200,
            'total' => 1800,
            'payment_status' => 'unpaid',
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'description' => 'Dental Filling', 'qty' => 2, 'price' => 1000]);

        return $invoice;
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'receptionist', 'status' => 'active']);
    }

    public function test_billing_list_opens_invoice_details_in_a_dialog(): void
    {
        $invoice = $this->invoice();

        $this->actingAs($this->staff())->get(route('billing.index'))
            ->assertOk()
            ->assertSee('data-invoice-details-url="'.route('billing.details', $invoice).'"', false)
            ->assertSee('data-invoice-details-body', false);
    }

    public function test_details_include_items_totals_history_and_payment_controls(): void
    {
        $invoice = $this->invoice();
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'method' => 'GCash',
            'reference' => 'GC-123',
            'paid_at' => now()->subHour(),
            'received_by' => $this->staff()->id,
            'notes' => 'Deposit',
        ]);
        $invoice->syncPaymentStatus();

        $this->actingAs($this->staff())->get(route('billing.details', $invoice))
            ->assertOk()
            ->assertSee('Dental Filling')
            ->assertSee('GC-123')
            ->assertSee('Deposit')
            ->assertSee('Pay remaining ₱1,300.00')
            ->assertSee('onclick="this.form.elements.amount.value = this.dataset.useFullBalance"', false)
            ->assertSee('data-full-balance-feedback', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('max="1300.00"', false)
            ->assertSee('Record Payment')
            ->assertSee('data-receipt-preview-url="'.route('billing.receipt', ['invoice' => $invoice, 'embedded' => 1]).'"', false)
            ->assertSee('View &amp; Print', false)
            ->assertSee(route('billing.send', $invoice), false);
    }

    public function test_paid_details_hide_payment_form(): void
    {
        $invoice = $this->invoice();
        Payment::create(['invoice_id' => $invoice->id, 'amount' => 1800, 'method' => 'Cash', 'paid_at' => now()]);
        $invoice->syncPaymentStatus();

        $this->actingAs($this->staff())->get(route('billing.details', $invoice))
            ->assertOk()
            ->assertSee('Paid in full')
            ->assertDontSee('data-use-full-balance', false);
    }

    public function test_ajax_payment_updates_status_and_returns_to_current_billing_filter(): void
    {
        Queue::fake();
        $invoice = $this->invoice();

        $this->actingAs($this->staff())
            ->from(route('billing.index', ['status' => 'Unpaid']))
            ->postJson(route('billing.payments.store', $invoice), [
                'amount' => 1800,
                'method' => 'Cash',
                'paid_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertOk()
            ->assertJson(['redirect' => route('billing.index', ['status' => 'Unpaid'])]);

        $this->assertSame('paid', $invoice->fresh()->payment_status);
        Queue::assertPushed(SendBillingDocumentEmail::class, 1);
    }

    public function test_payment_succeeds_without_patient_email_and_does_not_queue_document(): void
    {
        Queue::fake();
        $invoice = $this->invoice(Patient::factory()->create(['email' => null]));

        $response = $this->actingAs($this->staff())->postJson(route('billing.payments.store', $invoice), [
            'amount' => 500,
            'method' => 'Cash',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertOk();

        $this->assertSame('partial', $invoice->fresh()->payment_status);
        $this->assertDatabaseCount('billing_email_deliveries', 0);
        Queue::assertNothingPushed();
        $this->assertStringContainsString('no email was sent', session('status'));
        $this->assertArrayHasKey('redirect', $response->json());
    }

    public function test_receipt_preview_uses_capture_delegation_inside_the_shared_modal(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("event.target.closest('[data-receipt-preview-url]')", $script);
        $this->assertMatchesRegularExpression('/data-print-receipt-preview[\\s\\S]+?\\}, true\\);/', $script);
    }
}
