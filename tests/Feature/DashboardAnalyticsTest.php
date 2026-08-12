<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_dashboard_calculates_period_financial_and_operational_metrics(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create(['created_at' => CarbonImmutable::parse('2026-08-13 08:00', 'Asia/Manila')->utc()]);
        $appointment = $this->appointment($patient, User::factory()->dentist()->create(), 'completed', '2026-08-13 09:00');
        $record = DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'dentist_id' => $appointment->dentist_id,
            'treatment_date' => '2026-08-13',
            'procedure' => 'Cleaning',
            'treatment_fee' => 1200,
        ]);
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'dental_record_id' => $record->id,
            'invoice_date' => '2026-08-13',
            'due_date' => '2026-08-12',
            'subtotal' => 1200,
            'discount' => 0,
            'total' => 1200,
            'payment_status' => 'partial',
        ]);
        Payment::create(['invoice_id' => $invoice->id, 'amount' => 500, 'method' => 'Cash', 'paid_at' => CarbonImmutable::parse('2026-08-13 09:30', 'Asia/Manila')->utc()]);

        $response = $this->actingAs($admin)->get(route('dashboard', ['period' => 'today']))->assertOk();
        $kpis = collect($response->viewData('kpis'))->keyBy('label');

        $this->assertSame('₱500.00', $kpis['Collected revenue']['value']);
        $this->assertSame('1', $kpis['Appointments']['value']);
        $this->assertSame('1', $kpis['New patients']['value']);
        $this->assertSame('100.0%', $kpis['Completion rate']['value']);
        $this->assertSame('₱700.00', collect($response->viewData('attention'))->firstWhere('label', 'Outstanding balance')['value']);
        $this->assertSame([1], $response->viewData('charts')['breakdown']['datasets'][0]['data']);
    }

    public function test_dentist_dashboard_is_scoped_and_contains_no_financial_content_or_actions(): void
    {
        $dentist = User::factory()->dentist()->create();
        $otherDentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create();
        $this->appointment($patient, $dentist, 'completed', '2026-08-13 09:00');
        $this->appointment($patient, $otherDentist, 'confirmed', '2026-08-13 11:00');
        DentalRecord::factory()->create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'treatment_date' => '2026-08-13']);
        DentalRecord::factory()->create(['patient_id' => $patient->id, 'dentist_id' => $otherDentist->id, 'treatment_date' => '2026-08-13']);

        $response = $this->actingAs($dentist)->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Assigned appointments')
            ->assertSee('Treatment sessions')
            ->assertDontSee('Collected revenue')
            ->assertDontSee('recorded fees')
            ->assertDontSee('Create invoice')
            ->assertDontSee(route('billing.create'), false);
        $kpis = collect($response->viewData('kpis'))->keyBy('label');
        $this->assertSame('1', $kpis['Assigned appointments']['value']);
        $this->assertSame('1', $kpis['Treatment sessions']['value']);
        $this->assertCount(1, $response->viewData('todaysAppointments'));
    }

    public function test_receptionist_sees_billing_actions_but_not_clinical_record_actions(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($receptionist)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Collected payments')
            ->assertSee('Create invoice')
            ->assertSee(route('billing.create'), false)
            ->assertDontSee('Add record')
            ->assertDontSee(route('records.create'), false);
    }

    public function test_custom_range_is_validated_and_limited(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard', ['period' => 'custom', 'from' => '2026-08-14', 'to' => '2026-08-13']))
            ->assertSessionHasErrors('to');
        $this->actingAs($admin)->get(route('dashboard', ['period' => 'custom', 'from' => '2025-01-01', 'to' => '2026-01-02']))
            ->assertSessionHasErrors('to');
    }

    public function test_a_manila_midnight_appointment_appears_in_the_local_day_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();
        $dentist = User::factory()->dentist()->create();
        $this->appointment($patient, $dentist, 'confirmed', '2026-08-13 00:30', 'Midnight Patient');
        Cache::flush();

        $this->actingAs($admin)->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Midnight Patient')
            ->assertSee('12:30 AM');
    }

    private function appointment(Patient $patient, User $dentist, string $status, string $localStart, ?string $name = null): Appointment
    {
        $start = CarbonImmutable::parse($localStart, 'Asia/Manila')->utc();

        return Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'full_name' => $name ?? $patient->name,
            'contact_number' => $patient->mobile,
            'email' => $patient->email,
            'appointment_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'appointment_time' => $start->setTimezone('Asia/Manila')->format('H:i'),
            'preferred_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'preferred_time_window' => $start->setTimezone('Asia/Manila')->hour < 12 ? 'morning' : 'afternoon',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addMinutes(30),
            'service' => 'Cleaning',
            'status' => $status,
        ]);
    }
}
