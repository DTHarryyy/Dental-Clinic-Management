<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PaymentSubmissionTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    private function invoice(int $patientId, float $total = 1000): Invoice
    {
        return Invoice::create([
            'patient_id' => $patientId,
            'invoice_date' => today(),
            'due_date' => today()->addDays(14),
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_patient_can_submit_a_gcash_payment_with_a_reference(): void
    {
        Notification::fake();
        [$user, $patient] = $this->linkedPatient('submit-gcash@example.test');
        $invoice = $this->invoice($patient->id);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'GCash',
            'amount' => 400,
            'reference' => 'GC-999',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 400,
            'method' => 'GCash',
            'status' => PaymentStatus::Pending->value,
            'submitted_by' => $user->id,
        ]);

        // Pending money must never move the invoice or its balance.
        $invoice->refresh();
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertSame(1000.0, $invoice->balance);
    }

    public function test_reference_is_required_for_gcash(): void
    {
        [$user, $patient] = $this->linkedPatient('no-ref@example.test');
        $invoice = $this->invoice($patient->id);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'GCash',
            'amount' => 400,
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('reference');
    }

    public function test_cash_cannot_be_submitted_online(): void
    {
        [$user, $patient] = $this->linkedPatient('cash-online@example.test');
        $invoice = $this->invoice($patient->id);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'Cash',
            'amount' => 400,
            'reference' => 'whatever',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('method');
    }

    public function test_amount_cannot_exceed_the_balance(): void
    {
        [$user, $patient] = $this->linkedPatient('over-balance@example.test');
        $invoice = $this->invoice($patient->id, 500);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'GCash',
            'amount' => 600,
            'reference' => 'GC-1',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('amount');
    }

    public function test_a_second_submission_is_rejected_while_one_is_pending(): void
    {
        [$user, $patient] = $this->linkedPatient('double-submit@example.test');
        $invoice = $this->invoice($patient->id, 1000);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'GCash', 'amount' => 300, 'reference' => 'GC-1', 'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertOk();

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'Maya', 'amount' => 300, 'reference' => 'MY-1', 'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('amount');

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_a_duplicate_reference_is_rejected(): void
    {
        [$user, $patient] = $this->linkedPatient('dup-ref@example.test');
        $invoice = $this->invoice($patient->id, 1000);
        Payment::create([
            'invoice_id' => $invoice->id, 'amount' => 200, 'method' => 'GCash',
            'status' => PaymentStatus::Verified, 'reference' => 'GC-SAME', 'paid_at' => now(),
        ]);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'GCash', 'amount' => 100, 'reference' => 'GC-SAME', 'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertStatus(422)->assertJsonValidationErrors('reference');
    }

    public function test_patient_cannot_submit_against_another_patients_invoice(): void
    {
        [$user] = $this->linkedPatient('owner@example.test');
        [, $otherPatient] = $this->linkedPatient('victim@example.test');
        $foreign = $this->invoice($otherPatient->id);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $foreign), [
            'method' => 'GCash', 'amount' => 100, 'reference' => 'GC-1', 'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertNotFound();
    }

    public function test_a_paid_invoice_rejects_new_submissions(): void
    {
        [$user, $patient] = $this->linkedPatient('already-paid@example.test');
        $invoice = $this->invoice($patient->id, 500);
        $invoice->update(['payment_status' => 'paid']);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'GCash', 'amount' => 100, 'reference' => 'GC-1', 'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertForbidden();
    }

    public function test_proof_upload_lands_on_the_private_proofs_disk_not_public(): void
    {
        Storage::fake('proofs');
        Storage::fake('public');
        [$user, $patient] = $this->linkedPatient('proof-upload@example.test');
        $invoice = $this->invoice($patient->id, 1000);

        $this->actingAs($user)->postJson(route('patient.billing.payments.store', $invoice), [
            'method' => 'GCash',
            'amount' => 300,
            'reference' => 'GC-42',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'proof' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertOk();

        $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertNotNull($payment->proof_path);
        Storage::disk('proofs')->assertExists($payment->proof_path);
        Storage::disk('public')->assertMissing($payment->proof_path);
    }

    public function test_proof_route_is_restricted_to_the_owning_patient_and_staff(): void
    {
        Storage::fake('proofs');
        [$owner, $ownerPatient] = $this->linkedPatient('proof-owner@example.test');
        [$other] = $this->linkedPatient('proof-other@example.test');
        $invoice = $this->invoice($ownerPatient->id, 1000);
        $payment = Payment::create([
            'invoice_id' => $invoice->id, 'amount' => 300, 'method' => 'GCash', 'status' => PaymentStatus::Pending,
            'reference' => 'GC-9', 'paid_at' => now(), 'proof_path' => 'invoice-1/proof.jpg',
        ]);
        Storage::disk('proofs')->put($payment->proof_path, 'fake-image-bytes');

        $this->actingAs($owner)->get(route('payments.proof', $payment))->assertOk();
        $this->actingAs($other)->get(route('payments.proof', $payment))->assertForbidden();

        $staff = \App\Models\User::factory()->create(['role' => 'receptionist', 'status' => 'active']);
        $this->actingAs($staff)->get(route('payments.proof', $payment))->assertOk();
    }
}
