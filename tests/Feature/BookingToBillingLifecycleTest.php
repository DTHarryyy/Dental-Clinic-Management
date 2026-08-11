<?php

namespace Tests\Feature;

use App\Jobs\SendAppointmentConfirmationEmail;
use App\Jobs\SendBillingDocumentEmail;
use App\Models\Appointment;
use App\Models\BillingEmailDelivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\BillingDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingToBillingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->admin()->create();
    }

    private function unlinkedAppointment(): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => null,
            'status' => 'pending',
            'email' => 'booking@example.com',
        ]);
    }

    private function invoice(?Patient $patient = null): Invoice
    {
        $invoice = Invoice::create([
            'patient_id' => ($patient ?? Patient::factory()->create())->id,
            'invoice_date' => now()->toDateString(),
            'subtotal' => 2000,
            'discount' => 0,
            'total' => 2000,
            'payment_status' => 'unpaid',
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'description' => 'Dental Filling', 'qty' => 1, 'price' => 2000]);

        return $invoice;
    }

    public function test_confirmation_automatically_creates_and_links_a_patient(): void
    {
        Queue::fake();
        $appointment = $this->unlinkedAppointment();

        $this->actingAs($this->staff())
            ->post(route('appointments.status', $appointment), ['status' => 'confirmed'])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNotNull($appointment->patient_id);
        $this->assertSame('booking@example.com', $appointment->patient->email);
        $this->assertSame($appointment->full_name, $appointment->patient->name);
    }

    public function test_confirmation_automatically_links_an_existing_patient_by_email(): void
    {
        Queue::fake();
        $appointment = $this->unlinkedAppointment();
        $patient = Patient::factory()->create(['email' => 'BOOKING@example.com', 'status' => 'active']);

        $this->actingAs($this->staff())->post(route('appointments.status', $appointment), [
            'status' => 'confirmed',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertDatabaseCount('patients', 1);
        Queue::assertPushed(SendAppointmentConfirmationEmail::class);
    }

    public function test_confirmation_uses_phone_when_the_booking_has_no_email(): void
    {
        Queue::fake();
        $patient = Patient::factory()->create(['email' => null, 'mobile' => '0917-123-4567']);
        $appointment = $this->unlinkedAppointment();
        $appointment->update(['email' => null, 'contact_number' => '0917 123 4567']);

        $this->actingAs($this->staff())->post(route('appointments.status', $appointment), [
            'status' => 'confirmed',
        ])->assertRedirect();

        $this->assertDatabaseCount('patients', 1);
        $this->assertSame($patient->id, $appointment->fresh()->patient_id);
    }

    public function test_treatment_rejects_an_unconfirmed_or_mismatched_appointment(): void
    {
        $service = Service::create(['name' => 'Consultation', 'price' => 500]);
        $appointment = Appointment::factory()->create(['status' => 'pending', 'service' => $service->name]);
        $otherPatient = Patient::factory()->create();
        $payload = [
            'appointment_id' => $appointment->id,
            'patient_id' => $otherPatient->id,
            'treatment_date' => now()->toDateString(),
            'procedure' => $service->name,
            'clinical_notes' => 'Private notes',
        ];

        $this->actingAs(User::factory()->dentist()->create())
            ->postJson(route('records.store'), $payload)
            ->assertJsonValidationErrors('appointment_id');

        $appointment->update(['status' => 'confirmed']);
        $this->actingAs(User::factory()->dentist()->create())
            ->postJson(route('records.store'), $payload)
            ->assertJsonValidationErrors('appointment_id');
    }

    public function test_invoice_creation_queues_an_invoice_and_validates_totals(): void
    {
        Queue::fake();
        $patient = Patient::factory()->create(['email' => 'patient@example.com', 'status' => 'active']);
        $payload = [
            'patient_id' => $patient->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'discount' => 100,
            'items' => [['description' => 'Cleaning', 'qty' => 2, 'price' => 600]],
        ];

        $this->actingAs($this->staff())->post(route('billing.store'), $payload)->assertRedirect();

        $invoice = Invoice::sole();
        $this->assertEquals(1100, $invoice->total);
        $this->assertDatabaseHas('billing_email_deliveries', ['invoice_id' => $invoice->id, 'document_type' => 'invoice', 'status' => 'queued']);
        Queue::assertPushed(SendBillingDocumentEmail::class);

        $payload['discount'] = 1300;
        $this->actingAs($this->staff())->post(route('billing.store'), $payload)->assertSessionHasErrors('discount');
    }

    public function test_partial_then_final_payments_update_balance_and_queue_correct_documents(): void
    {
        Queue::fake();
        $invoice = $this->invoice();
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('billing.payments.store', $invoice), [
            'amount' => 500,
            'method' => 'GCash',
            'reference' => 'GC-123',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertEquals(500, $invoice->amount_paid);
        $this->assertEquals(1500, $invoice->balance);
        $this->assertSame('invoice', BillingEmailDelivery::latest('id')->value('document_type'));

        $this->actingAs($staff)->post(route('billing.payments.store', $invoice), [
            'amount' => 1500,
            'method' => 'Cash',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(0, $invoice->balance);
        $this->assertSame('receipt', BillingEmailDelivery::latest('id')->value('document_type'));
    }

    public function test_overpayment_is_rejected_without_changing_the_invoice(): void
    {
        $invoice = $this->invoice();

        $this->actingAs($this->staff())->post(route('billing.payments.store', $invoice), [
            'amount' => 2001,
            'method' => 'Cash',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('unpaid', $invoice->fresh()->payment_status);
    }

    public function test_manual_send_requires_patient_email(): void
    {
        Queue::fake();
        $invoice = $this->invoice(Patient::factory()->create(['email' => null]));

        $this->actingAs($this->staff())->post(route('billing.send', $invoice))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('billing_email_deliveries', 0);
    }

    public function test_pdf_is_generated_without_clinical_content(): void
    {
        $invoice = $this->invoice();
        $data = app(BillingDocument::class)->data($invoice, 'invoice');
        $html = view('billing.pdf', $data)->render();
        $pdf = app(BillingDocument::class)->pdf($data);

        $this->assertStringContainsString('Dental Filling', $html);
        $this->assertStringNotContainsString('clinical_notes', $html);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
