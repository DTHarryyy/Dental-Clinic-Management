<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\FinancialTrends;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

/**
 * A pending patient submission is a claim, not money received. Every place that
 * sums payments for a balance, a KPI, or a report must filter to verified rows
 * only — this test builds one invoice with both a verified and a pending payment
 * and asserts every one of those call sites agrees on the same (verified-only)
 * numbers. See the payments-verification section of the implementation plan for
 * the full list of sites this exercises.
 */
class PendingPaymentExclusionTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->travelTo(CarbonImmutable::parse('2026-08-13 10:00', 'Asia/Manila'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_a_pending_submission_never_counts_as_collected_anywhere(): void
    {
        [$patientUser, $patient] = $this->linkedPatient('exclusion@example.test');

        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_date' => '2026-08-13',
            'due_date' => '2026-08-20',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_status' => 'unpaid',
        ]);

        // 300 confirmed at the counter…
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 300,
            'method' => 'Cash',
            'status' => PaymentStatus::Verified,
            'paid_at' => CarbonImmutable::parse('2026-08-13 09:00', 'Asia/Manila')->utc(),
        ]);
        $invoice->syncPaymentStatus();

        // …and 700 the patient claims to have sent but nobody has confirmed yet.
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 700,
            'method' => 'GCash',
            'status' => PaymentStatus::Pending,
            'reference' => 'GC-PENDING',
            'paid_at' => CarbonImmutable::parse('2026-08-13 09:30', 'Asia/Manila')->utc(),
        ]);

        // 1) Model accessors
        $invoice->refresh();
        $this->assertSame(300.0, $invoice->amount_paid);
        $this->assertSame(700.0, $invoice->amount_pending);
        $this->assertSame(700.0, $invoice->balance);
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame('Pending verification', $invoice->display_status);

        // 2) Staff billing index summary (leftJoinSub aggregate)
        $summary = $this->actingAs($this->admin())
            ->get(route('billing.index'))
            ->assertOk()
            ->viewData('summary');
        $this->assertSame(300.0, $summary['paid']);
        $this->assertSame(700.0, $summary['unpaid']);

        // 3) Patient billing index summary
        $patientSummary = $this->actingAs($patientUser)
            ->get(route('patient.billing.index'))
            ->assertOk()
            ->viewData('summary');
        $this->assertSame(700.0, $patientSummary['outstanding']);

        // 4) Patient dashboard outstanding balance tile
        $dashboard = $this->actingAs($patientUser)->get(route('patient.dashboard'))->assertOk();
        $this->assertSame(700.0, $dashboard->viewData('outstandingBalance'));

        // 5) Admin dashboard "Collected revenue" KPI
        $kpis = collect(
            $this->actingAs($this->admin())->get(route('dashboard', ['period' => 'today']))->assertOk()->viewData('kpis')
        )->keyBy('label');
        $this->assertSame('₱300.00', $kpis['Collected revenue']['value']);

        // 6) Six-month revenue trend
        $trend = FinancialTrends::sixMonthRevenue();
        $this->assertSame(300.0, $trend->last()['value']);
    }
}
