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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicReportSuiteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->travelTo(CarbonImmutable::parse('2026-08-13 10:00', 'Asia/Manila'));
        $this->admin = User::factory()->admin()->create();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_overview_uses_distinct_financial_and_operational_definitions(): void
    {
        [$patient, $dentist] = $this->clinicPeople();
        $appointment = $this->appointment($patient, $dentist, 'completed', '2026-08-13 09:00');
        $record = DentalRecord::factory()->create([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'appointment_id' => $appointment->id,
            'treatment_date' => '2026-08-13', 'procedure' => 'Cleaning', 'treatment_fee' => 1500,
        ]);
        $invoice = Invoice::create([
            'patient_id' => $patient->id, 'dental_record_id' => $record->id, 'invoice_date' => '2026-08-13',
            'due_date' => '2026-08-12', 'subtotal' => 1500, 'discount' => 100, 'total' => 1400, 'payment_status' => 'partial',
        ]);
        Payment::create(['invoice_id' => $invoice->id, 'amount' => 500, 'method' => 'Cash', 'paid_at' => CarbonImmutable::parse('2026-08-13 09:30', 'Asia/Manila')->utc(), 'received_by' => $this->admin->id]);
        Cache::flush();

        $response = $this->actingAs($this->admin)->get(route('reports', ['period' => 'today']))->assertOk();
        $overview = $response->viewData('report')['overview'];
        $kpis = collect($overview['kpis'])->keyBy('label');

        $this->assertSame('₱500.00', $kpis['Collected payments']['value']);
        $this->assertSame('₱1,400.00', $kpis['Invoiced total']['value']);
        $this->assertSame('₱900.00', $kpis['Outstanding balance']['value']);
        $this->assertSame('1', $kpis['Appointments']['value']);
        $this->assertSame('100.0%', $kpis['Completion rate']['value']);
        $this->assertSame('Cleaning', collect($overview['insights'])->firstWhere('label', 'Leading treatment')['value']);
    }

    public function test_effective_schedule_precedence_local_midnight_and_future_rate_are_correct(): void
    {
        [$patient, $dentist] = $this->clinicPeople();
        $this->appointment($patient, $dentist, 'completed', '2026-08-13 00:15');
        $this->appointment($patient, $dentist, 'confirmed', '2026-08-13 18:00');
        Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'full_name' => $patient->name,
            'contact_number' => $patient->mobile, 'appointment_date' => '2026-08-13', 'appointment_time' => null,
            'preferred_date' => '2026-08-13', 'requested_start_at' => CarbonImmutable::parse('2026-08-12 10:00', 'Asia/Manila')->utc(),
            'scheduled_start_at' => CarbonImmutable::parse('2026-08-14 10:00', 'Asia/Manila')->utc(), 'service' => 'Extraction', 'status' => 'completed',
        ]);
        Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'full_name' => $patient->name,
            'contact_number' => $patient->mobile, 'appointment_date' => '2026-08-13', 'appointment_time' => null,
            'preferred_date' => null, 'scheduled_start_at' => null, 'requested_start_at' => null, 'service' => 'Legacy service', 'status' => 'cancelled',
        ]);
        Cache::flush();

        $section = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'appointments', 'period' => 'today']))->assertOk()->viewData('report')['appointments'];
        $kpis = collect($section['kpis'])->keyBy('label');

        $this->assertSame('3', $kpis['Total appointments']['value']);
        $this->assertSame('50.0%', $kpis['Completion rate']['value']);
        $this->assertSame('50.0%', $kpis['Cancellation rate']['value']);
        $this->assertCount(3, $section['rows']);
    }

    public function test_tabs_legacy_dates_invalid_ranges_and_access_control_work(): void
    {
        $this->actingAs($this->admin)->get('/reports?from=2026-08-01&to=2026-08-13&tab=financial')
            ->assertOk()->assertViewHas('activeTab', 'financial')->assertSee('Financial');
        $this->actingAs($this->admin)->get('/reports?tab=unknown')->assertOk()->assertViewHas('activeTab', 'overview');
        $this->actingAs($this->admin)->get('/reports?period=custom&from=2026-08-14&to=2026-08-13')
            ->assertRedirect(route('reports'))->assertSessionHasErrors('date_range');

        foreach ([User::factory()->dentist()->create(['status' => 'active']), User::factory()->create(['role' => 'receptionist', 'status' => 'active'])] as $user) {
            $this->actingAs($user)->get(route('reports'))->assertForbidden();
            $this->actingAs($user)->get(route('reports.export.pdf'))->assertForbidden();
            $this->actingAs($user)->get(route('reports.export.csv', ['dataset' => 'appointments']))->assertForbidden();
        }
    }

    public function test_returning_patients_missing_demographics_inactive_dentists_and_unassigned_work_are_reported(): void
    {
        $patient = Patient::factory()->create(['dob' => null, 'gender' => null, 'created_at' => CarbonImmutable::parse('2025-01-01', 'Asia/Manila')->utc()]);
        $dentist = User::factory()->dentist()->create(['name' => 'Dr Historical', 'status' => 'inactive']);
        $this->appointment($patient, $dentist, 'completed', '2026-07-01 09:00');
        $current = $this->appointment($patient, $dentist, 'completed', '2026-08-13 09:00');
        DentalRecord::factory()->create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'appointment_id' => $current->id, 'treatment_date' => '2026-08-13', 'procedure' => 'Restoration', 'treatment_fee' => 2000]);
        Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => null, 'full_name' => $patient->name, 'contact_number' => $patient->mobile,
            'appointment_date' => '2026-08-13', 'appointment_time' => '09:30', 'preferred_date' => '2026-08-13',
            'scheduled_start_at' => CarbonImmutable::parse('2026-08-13 09:30', 'Asia/Manila')->utc(), 'service' => 'Cleaning', 'status' => 'confirmed',
        ]);
        Cache::flush();

        $patients = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'patients-services', 'period' => 'today']))->assertOk()->viewData('report')['patientsServices'];
        $this->assertSame('1', collect($patients['kpis'])->firstWhere('label', 'Returning patients')['value']);
        $this->assertContains('Unknown', $patients['charts']['patients-age']['labels']);
        $this->assertContains('Not recorded', $patients['charts']['patients-gender']['labels']);

        $dentists = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'dentists', 'period' => 'today']))->assertOk()->viewData('report')['dentists'];
        $historical = collect($dentists['rows'])->firstWhere('dentist', 'Dr Historical');
        $this->assertSame('Inactive', $historical['status']);
        $this->assertSame(1, $historical['sessions']);
        $this->assertSame(1, $dentists['unassigned']['assigned']);
    }

    public function test_report_presentation_combines_redundant_charts_and_exposes_balanced_layout_metadata(): void
    {
        [$patient, $dentist] = $this->clinicPeople();
        $appointment = $this->appointment($patient, $dentist, 'completed', '2026-08-13 09:00');
        DentalRecord::factory()->create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'appointment_id' => $appointment->id, 'treatment_date' => '2026-08-13', 'procedure' => 'Cleaning', 'treatment_fee' => 1200]);
        Cache::flush();

        $appointments = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'appointments', 'period' => 'today']))->assertOk()->viewData('report')['appointments'];
        $this->assertCount(4, $appointments['charts']);
        $this->assertArrayNotHasKey('appointments-workload', $appointments['charts']);
        $this->assertSame('wide', $appointments['charts']['appointments-trend']['layout']);
        $this->assertSame('compact', $appointments['charts']['appointments-status']['layout']);
        $this->assertCount(3, $appointments['insights']);

        $patients = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'patients-services', 'period' => 'today']))->assertOk()->viewData('report')['patientsServices'];
        $this->assertArrayHasKey('services-demand', $patients['charts']);
        $this->assertArrayNotHasKey('services-booked', $patients['charts']);
        $this->assertArrayNotHasKey('services-treatment', $patients['charts']);
        $this->assertSame(['Booked appointments', 'Completed sessions'], collect($patients['charts']['services-demand']['datasets'])->pluck('label')->all());
        $this->assertSame('full', $patients['charts']['services-demand']['layout']);

        $dentists = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'dentists', 'period' => 'today']))->assertOk()->viewData('report')['dentists'];
        $this->assertSame(['dentists-activity'], array_keys($dentists['charts']));
        $this->assertCount(3, $dentists['charts']['dentists-activity']['datasets']);
        $this->assertSame('comparison', $dentists['charts']['dentists-activity']['variant']);
        $this->assertCount(4, $dentists['insights']);
    }

    public function test_sparse_and_empty_report_visuals_keep_useful_presentation_states(): void
    {
        $empty = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'dentists', 'period' => 'today']))
            ->assertOk()
            ->assertSee('No dentist workload or clinical activity in this period.')
            ->viewData('report')['dentists'];
        $this->assertSame('full', $empty['charts']['dentists-activity']['layout']);
        $this->assertSame([], $empty['charts']['dentists-activity']['labels']);

        [$patient, $dentist] = $this->clinicPeople();
        $dentist->update(['name' => 'Dr Alexandria-Marguerite Villanueva-Santos-Washington']);
        $this->appointment($patient, $dentist, 'completed', '2026-08-13 09:00');
        Cache::flush();

        $sparse = $this->actingAs($this->admin)->get(route('reports', ['tab' => 'dentists', 'period' => 'today']))->assertOk()->viewData('report')['dentists'];
        $this->assertSame(['Dr Alexandria-Marguerite Villanueva-Santos-Washington'], $sparse['charts']['dentists-activity']['labels']);
        $this->assertSame('horizontal', $sparse['charts']['dentists-activity']['orientation']);
        $this->assertSame('number', $sparse['charts']['dentists-activity']['format']);
    }

    public function test_pdf_and_all_csv_exports_are_downloadable_private_and_formula_safe(): void
    {
        [$patient, $dentist] = $this->clinicPeople(['first_name' => '=Formula']);
        $appointment = $this->appointment($patient, $dentist, 'completed', '2026-08-13 09:00');
        DentalRecord::factory()->create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'appointment_id' => $appointment->id, 'treatment_date' => '2026-08-13', 'procedure' => '+Cleaning', 'treatment_fee' => 1000]);
        $invoice = Invoice::create(['patient_id' => $patient->id, 'invoice_date' => '2026-08-13', 'due_date' => '2026-08-13', 'subtotal' => 1000, 'discount' => 0, 'total' => 1000, 'payment_status' => 'partial']);
        Payment::create(['invoice_id' => $invoice->id, 'amount' => 300, 'method' => '@Cash', 'reference' => '-REF', 'paid_at' => CarbonImmutable::parse('2026-08-13 09:30', 'Asia/Manila')->utc(), 'received_by' => $this->admin->id]);
        Cache::flush();

        $pdf = $this->actingAs($this->admin)->get(route('reports.export.pdf', ['period' => 'today']))->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('clinic-performance-2026-08-13-to-2026-08-13.pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        foreach (['payments', 'invoices', 'appointments', 'patients', 'services', 'dentists'] as $dataset) {
            $response = $this->actingAs($this->admin)->get(route('reports.export.csv', ['dataset' => $dataset, 'period' => 'today']))
                ->assertOk()->assertDownload("{$dataset}-2026-08-13-to-2026-08-13.csv");
            $content = $response->streamedContent();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
            $this->assertStringNotContainsString('clinical_notes', strtolower($content));
            if ($dataset === 'payments') {
                $this->assertStringContainsString("'@Cash", $content);
                $this->assertStringContainsString("'-REF", $content);
            }
        }
        $this->actingAs($this->admin)->get(route('reports.export.csv', ['dataset' => 'unknown']))->assertNotFound();
    }

    public function test_cached_reports_have_a_two_query_warm_budget_and_mutations_invalidate_cache(): void
    {
        $url = route('reports', ['period' => 'today']);
        $this->actingAs($this->admin)->get($url)->assertOk();
        $queries = 0;
        DB::listen(function ($query) use (&$queries): void {
            if (! str_contains(strtolower($query->sql), 'sessions')) {
                $queries++;
            }
        });
        $this->actingAs($this->admin)->get($url)->assertOk();
        $this->assertLessThanOrEqual(2, $queries);

        Patient::factory()->create(['created_at' => now()->utc()]);
        $response = $this->actingAs($this->admin)->get($url)->assertOk();
        $this->assertSame('1', collect($response->viewData('report')['overview']['kpis'])->firstWhere('label', 'New patients')['value']);
    }

    private function clinicPeople(array $patientAttributes = []): array
    {
        return [Patient::factory()->create(array_merge(['created_at' => now()->utc()], $patientAttributes)), User::factory()->dentist()->create()];
    }

    private function appointment(Patient $patient, User $dentist, string $status, string $localStart): Appointment
    {
        $start = CarbonImmutable::parse($localStart, 'Asia/Manila');

        return Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'full_name' => $patient->name,
            'contact_number' => $patient->mobile, 'appointment_date' => $start->toDateString(), 'appointment_time' => $start->format('H:i'),
            'preferred_date' => $start->toDateString(), 'preferred_time_window' => $start->hour < 12 ? 'morning' : 'afternoon',
            'scheduled_start_at' => $start->utc(), 'scheduled_end_at' => $start->addMinutes(30)->utc(),
            'duration_minutes' => 30, 'service' => 'Cleaning', 'status' => $status,
        ]);
    }
}
