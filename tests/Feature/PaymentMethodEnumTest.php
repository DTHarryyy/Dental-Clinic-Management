<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\ClinicPaymentChannel;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the reported bug: the "New Invoice" dialog, the full-page
 * create form, and the record-payment form each hardcoded their own payment
 * method list, and two of the three disagreed (missing Bank Transfer and Other).
 * All three must now render every PaymentMethod case.
 */
class PaymentMethodEnumTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_method_has_a_label_and_reference_rule(): void
    {
        $this->assertNotEmpty(PaymentMethod::cases());
        foreach (PaymentMethod::cases() as $method) {
            $this->assertNotEmpty($method->label());
            $this->assertIsBool($method->requiresReference());
        }

        $this->assertTrue(PaymentMethod::GCash->requiresReference());
        $this->assertTrue(PaymentMethod::Maya->requiresReference());
        $this->assertTrue(PaymentMethod::Card->requiresReference());
        $this->assertTrue(PaymentMethod::BankTransfer->requiresReference());
        $this->assertFalse(PaymentMethod::Cash->requiresReference());
        $this->assertFalse(PaymentMethod::Other->requiresReference());
    }

    public function test_only_remote_transfer_methods_are_patient_submittable(): void
    {
        // Cash and card are settled in person at the clinic; "Other" is staff-arranged.
        // Only money the patient sends elsewhere (GCash, Maya, bank transfer) can be
        // self-declared online, since only those need a "where do I send it" answer
        // from the portal.
        $this->assertFalse(PaymentMethod::Cash->isPatientSubmittable());
        $this->assertFalse(PaymentMethod::Card->isPatientSubmittable());
        $this->assertFalse(PaymentMethod::Other->isPatientSubmittable());
        $this->assertTrue(PaymentMethod::GCash->isPatientSubmittable());
        $this->assertTrue(PaymentMethod::Maya->isPatientSubmittable());
        $this->assertTrue(PaymentMethod::BankTransfer->isPatientSubmittable());
    }

    public function test_the_three_staff_forms_all_offer_every_method(): void
    {
        $staff = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);
        $labels = collect(PaymentMethod::cases())->map->label();

        $createDialog = $this->actingAs($staff)->get(route('billing.index'));
        $createPage = $this->actingAs($staff)->get(route('billing.create'));

        $invoice = Invoice::create([
            'patient_id' => Patient::factory()->create()->id,
            'invoice_date' => today(),
            'total' => 500,
            'payment_status' => 'unpaid',
        ]);
        $detailsDialog = $this->actingAs($staff)->get(route('billing.details', $invoice));

        foreach ($labels as $label) {
            $createDialog->assertSee($label);
            $createPage->assertSee($label);
            $detailsDialog->assertSee($label);
        }
    }

    public function test_every_method_is_active_by_default(): void
    {
        $this->assertEquals(PaymentMethod::cases(), ClinicPaymentChannel::activeMethods());
    }

    public function test_a_method_disabled_in_settings_disappears_and_is_rejected(): void
    {
        ClinicPaymentChannel::where('method', 'Credit/Debit Card')->update(['is_enabled' => false]);
        ClinicPaymentChannel::forgetCache();

        $active = collect(ClinicPaymentChannel::activeMethods())->map->value;
        $this->assertNotContains('Credit/Debit Card', $active);

        $staff = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);
        $this->actingAs($staff)->get(route('billing.create'))->assertDontSee('Credit/Debit Card');

        $invoice = Invoice::create([
            'patient_id' => Patient::factory()->create()->id,
            'invoice_date' => today(),
            'total' => 500,
            'payment_status' => 'unpaid',
        ]);
        $this->actingAs($staff)->postJson(route('billing.payments.store', $invoice), [
            'amount' => 100,
            'method' => 'Credit/Debit Card',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('method');
    }
}
