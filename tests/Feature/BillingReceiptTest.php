<?php

namespace Tests\Feature;

use App\Models\ClinicSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function invoice(string $status, ?string $dueDate = null): Invoice
    {
        $invoice = Invoice::create([
            'patient_id' => Patient::factory()->create()->id,
            'invoice_date' => '2026-01-10',
            'due_date' => $dueDate,
            'subtotal' => 2000,
            'discount' => 0,
            'total' => 2000,
            'payment_status' => $status,
            'payment_method' => 'Cash',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Dental Filling',
            'qty' => 1,
            'price' => 2000,
        ]);

        return $invoice;
    }

    public function test_a_paid_invoice_is_presented_as_a_receipt(): void
    {
        $invoice = $this->invoice('paid');

        $this->actingAs($this->admin())
            ->get(route('billing.receipt', $invoice))
            ->assertOk()
            ->assertSee('RECEIPT')
            ->assertSee('Payment Received')
            ->assertDontSee('Balance Due');
    }

    /**
     * A receipt is proof of payment, so an unpaid bill must not present itself as
     * one or claim the money has been received.
     */
    public function test_an_unpaid_invoice_is_not_presented_as_a_receipt(): void
    {
        $invoice = $this->invoice('unpaid', '2026-02-20');

        $this->actingAs($this->admin())
            ->get(route('billing.receipt', $invoice))
            ->assertOk()
            ->assertSee('INVOICE')
            ->assertSee('Balance Due')
            ->assertDontSee('Payment Received')
            ->assertDontSee('Paid in full');
    }

    public function test_an_overdue_invoice_is_flagged_as_overdue(): void
    {
        $invoice = $this->invoice('unpaid', now()->subWeek()->toDateString());

        $this->actingAs($this->admin())
            ->get(route('billing.receipt', $invoice))
            ->assertOk()
            ->assertSee('Overdue since')
            ->assertSee('Balance Due')
            ->assertDontSee('Payment Received');
    }

    public function test_a_partial_invoice_reports_a_remaining_balance(): void
    {
        $invoice = $this->invoice('partial', '2026-02-20');

        $this->actingAs($this->admin())
            ->get(route('billing.receipt', $invoice))
            ->assertOk()
            ->assertSee('Partial Payment')
            ->assertSee('A balance remains on this invoice')
            ->assertDontSee('Paid in full');
    }

    public function test_the_receipt_uses_the_configured_clinic_details(): void
    {
        ClinicSetting::current()->update([
            'clinic_name' => 'Bright Smile Dental Clinic',
            'phone' => '(02) 8555-0142',
            'address' => '42 Katipunan Ave, Quezon City',
        ]);
        ClinicSetting::forgetCache();

        $this->actingAs($this->admin())
            ->get(route('billing.receipt', $this->invoice('paid')))
            ->assertOk()
            ->assertSee('Bright Smile Dental Clinic')
            ->assertSee('42 Katipunan Ave, Quezon City')
            ->assertSee('(02) 8555-0142', false);
    }

    public function test_receipt_can_be_rendered_as_an_embedded_dialog_preview(): void
    {
        $invoice = $this->invoice('paid');

        $this->actingAs($this->admin())
            ->get(route('billing.receipt', ['invoice' => $invoice, 'embedded' => 1]))
            ->assertOk()
            ->assertSee('receipt-embedded', false)
            ->assertSee('RECEIPT')
            ->assertDontSee('Back to Billing')
            ->assertDontSee('Send Receipt Again');
    }
}
