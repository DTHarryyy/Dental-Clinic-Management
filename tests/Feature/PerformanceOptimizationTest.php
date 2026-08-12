<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\FinancialTrends;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_production_responses_expose_safe_server_timing(): void
    {
        $this->get(route('public.book'))
            ->assertOk()
            ->assertHeader('X-Query-Count')
            ->assertHeader('Server-Timing');
    }

    public function test_patient_lookup_is_authenticated_bounded_and_minimal(): void
    {
        Patient::factory()->count(25)->create(['status' => 'active']);

        $this->getJson(route('lookups.patients'))->assertUnauthorized();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('lookups.patients'))
            ->assertOk();

        $this->assertCount(20, $response->json('data'));
        $this->assertSame(['id', 'name', 'email'], array_keys($response->json('data.0')));
    }

    public function test_patient_history_is_lazy_loaded(): void
    {
        $patient = Patient::factory()->create();
        DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'procedure' => 'Unique lazy history procedure',
        ]);
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('patients.index'))
            ->assertOk()
            ->assertDontSee('Unique lazy history procedure');

        $this->actingAs($user)->get(route('patients.detail-frame', $patient))
            ->assertOk()
            ->assertSee('patient-detail-frame')
            ->assertSee('Unique lazy history procedure');
    }

    public function test_six_month_revenue_uses_one_query(): void
    {
        $patient = Patient::factory()->create();
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_date' => today(),
            'subtotal' => 300,
            'discount' => 0,
            'total' => 300,
            'payment_status' => 'paid',
        ]);
        foreach ([100, 100, 100] as $amount) {
            Payment::create(['invoice_id' => $invoice->id, 'amount' => $amount, 'method' => 'Cash', 'paid_at' => now()]);
        }
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $trend = FinancialTrends::sixMonthRevenue();

        $this->assertCount(6, $trend);
        $this->assertSame(1, $queries);
    }

    public function test_authenticated_layout_enables_turbo_behind_the_flag(): void
    {
        config()->set('performance.turbo_enabled', true);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('name="turbo-enabled" content="true"', false)
            ->assertSee('data-turbo-track="reload"', false);
    }

    public function test_dashboard_displays_todays_appointment_snapshot_name(): void
    {
        $start = \Carbon\CarbonImmutable::now('Asia/Manila')->startOfDay()->addHours(9)->utc();
        Appointment::create([
            'dentist_id' => User::factory()->dentist()->create()->id,
            'full_name' => 'Dashboard Patient',
            'contact_number' => '09123456789',
            'appointment_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'appointment_time' => '09:00',
            'preferred_date' => $start->setTimezone('Asia/Manila')->toDateString(),
            'preferred_time_window' => 'morning',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addMinutes(30),
            'service' => 'Cleaning',
            'status' => 'confirmed',
        ]);
        Cache::flush();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Patient');
    }

    #[DataProvider('primaryPageProvider')]
    public function test_primary_pages_stay_within_five_business_queries(string $route): void
    {
        $user = User::factory()->admin()->create();
        Cache::flush();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->actingAs($user)->get(route($route))->assertOk();

        $budget = match ($route) {
            'dashboard' => 7,
            'reports' => 10,
            default => 5,
        };
        $this->assertLessThanOrEqual($budget, $queries, "{$route} executed {$queries} queries.");
    }

    public static function primaryPageProvider(): array
    {
        return [
            ['dashboard'],
            ['patients.index'],
            ['appointments.index'],
            ['records.index'],
            ['billing.index'],
            ['reports'],
        ];
    }
}
