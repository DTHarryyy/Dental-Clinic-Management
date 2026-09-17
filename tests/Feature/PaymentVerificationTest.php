<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Jobs\SendBillingDocumentEmail;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'receptionist'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function invoiceWithPendingPayment(float $total = 1000, float $pending = 400): array
    {
        $invoice = Invoice::create([
            'patient_id' => Patient::factory()->create()->id,
            'invoice_date' => today(),
            'due_date' => today()->addDays(14),
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'payment_status' => 'unpaid',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $pending,
            'method' => 'GCash',
            'status' => PaymentStatus::Pending,
            'reference' => 'GC-1',
            'paid_at' => now(),
        ]);

        return [$invoice, $payment];
    }

    public function test_staff_can_verify_a_pending_payment(): void
    {
        Queue::fake();
        [$invoice, $payment] = $this->invoiceWithPendingPayment(1000, 1000);

        $this->actingAs($this->staff())
            ->post(route('billing.payments.verify', $payment))
            ->assertRedirect();

        $payment->refresh();
        $this->assertTrue($payment->isVerified());
        $this->assertNotNull($payment->verified_at);
        $this->assertNotNull($payment->verified_by);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        Queue::assertPushed(SendBillingDocumentEmail::class, 1);
    }

    public function test_verifying_twice_is_rejected(): void
    {
        [, $payment] = $this->invoiceWithPendingPayment();
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('billing.payments.verify', $payment))->assertRedirect();
        $this->actingAs($staff)->post(route('billing.payments.verify', $payment))->assertStatus(409);
    }

    public function test_verify_fails_when_the_balance_has_since_shrunk(): void
    {
        [$invoice, $payment] = $this->invoiceWithPendingPayment(1000, 900);

        // Staff recorded a counter payment after the patient's submission, leaving
        // less balance than the pending claim now needs.
        $invoice->payments()->create([
            'amount' => 800,
            'method' => 'Cash',
            'status' => PaymentStatus::Verified,
            'paid_at' => now(),
        ]);
        $invoice->syncPaymentStatus();

        $this->actingAs($this->staff())
            ->postJson(route('billing.payments.verify', $payment))
            ->assertStatus(422);

        $this->assertTrue($payment->fresh()->isPending());
    }

    public function test_reject_requires_a_reason_and_leaves_balance_untouched(): void
    {
        [$invoice, $payment] = $this->invoiceWithPendingPayment(1000, 400);

        $this->actingAs($this->staff())
            ->postJson(route('billing.payments.reject', $payment), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rejection_reason');

        $this->actingAs($this->staff())
            ->post(route('billing.payments.reject', $payment), ['rejection_reason' => 'No transaction found.'])
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Rejected, $payment->status);
        $this->assertSame('No transaction found.', $payment->rejection_reason);

        $invoice->refresh();
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertSame(1000.0, $invoice->balance);
    }

    public function test_a_dentist_cannot_verify_payments(): void
    {
        [, $payment] = $this->invoiceWithPendingPayment();

        $this->actingAs($this->staff('dentist'))
            ->post(route('billing.payments.verify', $payment))
            ->assertForbidden();
    }

    public function test_pending_queue_is_not_reachable_by_a_patient(): void
    {
        [, $payment] = $this->invoiceWithPendingPayment();
        $patientUser = User::factory()->patient()->create();

        // EnsureActiveStaff logs out and redirects non-staff sessions rather than
        // aborting — the staff-only shell as a whole is off-limits, not just this route.
        $this->actingAs($patientUser)->get(route('billing.payments.pending'))->assertRedirect(route('login'));
    }
}
