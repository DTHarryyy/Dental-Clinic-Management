<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PatientBillingTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    private function invoice(int $patientId, array $attributes = []): Invoice
    {
        return Invoice::create([...$attributes, 'patient_id' => $patientId, 'invoice_date' => today(), 'due_date' => today()->addDays(14)]);
    }

    public function test_the_summary_totals_span_every_invoice_not_just_the_current_page(): void
    {
        [$user, $patient] = $this->linkedPatient('billing-summary@example.test');
        $unpaid = $this->invoice($patient->id, ['subtotal' => 1000, 'discount' => 0, 'total' => 1000, 'payment_status' => 'unpaid']);
        $paid = $this->invoice($patient->id, ['subtotal' => 500, 'discount' => 0, 'total' => 500, 'payment_status' => 'paid']);
        Payment::create(['invoice_id' => $paid->id, 'amount' => 500, 'method' => 'Cash', 'paid_at' => now()]);

        $response = $this->actingAs($user)->get(route('patient.billing.index'))->assertOk();

        $summary = $response->viewData('summary');
        $this->assertSame(1000.0, $summary['outstanding']);
        $this->assertSame(500.0, $summary['paid']);
    }

    public function test_an_unknown_status_filter_falls_back_to_all(): void
    {
        [$user, $patient] = $this->linkedPatient('billing-filter@example.test');
        $this->invoice($patient->id, ['subtotal' => 200, 'discount' => 0, 'total' => 200, 'payment_status' => 'unpaid']);

        $this->actingAs($user)->get(route('patient.billing.index', ['status' => 'not-a-real-status']))
            ->assertOk()
            ->assertViewHas('status', 'all')
            ->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 1);
    }

    public function test_show_and_receipt_are_scoped_to_the_owning_patient(): void
    {
        [$user] = $this->linkedPatient('billing-owner@example.test');
        [, $otherPatient] = $this->linkedPatient('billing-victim@example.test');
        $foreign = $this->invoice($otherPatient->id, ['subtotal' => 300, 'discount' => 0, 'total' => 300]);

        $this->actingAs($user)->get(route('patient.billing.show', $foreign))->assertNotFound();
        $this->actingAs($user)->get(route('patient.billing.receipt', $foreign))->assertNotFound();
    }

    public function test_show_renders_for_the_owning_patient(): void
    {
        [$user, $patient] = $this->linkedPatient('billing-view@example.test');
        $invoice = $this->invoice($patient->id, ['subtotal' => 750, 'discount' => 0, 'total' => 750]);

        $this->actingAs($user)->get(route('patient.billing.show', $invoice))->assertOk();
    }
}
