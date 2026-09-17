<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
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
        $this->get(route('home'))
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
            // +1 over the default: the New Invoice dialog needs the active payment
            // methods list (ClinicPaymentChannel::activeMethods()), one cold-cache query.
            'billing.index' => 6,
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

    private function patientUser(): User
    {
        $patient = Patient::factory()->create(['status' => 'active']);

        return User::factory()->patient()->create(['patient_id' => $patient->id]);
    }

    /** @return array{0: User, 1: \Illuminate\Support\Collection<int, Service>} */
    private function bookingFixture(int $serviceCount = 8): array
    {
        User::factory()->dentist()->create(['status' => 'active']);
        $services = collect(range(1, $serviceCount))->map(fn (int $i) => Service::create([
            'name' => "Perf Service {$i}", 'price' => 100 * $i, 'duration_minutes' => 30,
        ]));

        return [$this->patientUser(), $services];
    }

    public function test_seven_day_availability_needs_no_closure_service_or_dentist_queries_once_warm(): void
    {
        [$user, $services] = $this->bookingFixture();
        $date = now('Asia/Manila')->addWeek()->toDateString();
        $params = ['start_date' => $date, 'service_ids' => $services->pluck('id')->all()];

        // Warm every cache the endpoint reads (closures, business hours, dentists, services,
        // settings) before counting queries.
        $this->actingAs($user)->getJson(route('patient.appointments.dates', $params))->assertOk();

        $sql = [];
        DB::listen(function ($query) use (&$sql): void { $sql[] = $query->sql; });

        $this->actingAs($user)->getJson(route('patient.appointments.dates', $params))->assertOk();

        $matching = fn (string $needle) => collect($sql)->filter(fn ($s) => str_contains($s, $needle))->count();

        $this->assertSame(0, $matching('clinic_closures'), 'closures must be served from cache once warm');
        $this->assertSame(0, $matching('from "services"'), 'service_ids validation must not query per id');
        $this->assertSame(0, $matching('from "users"'), 'dentist lookup must be served from cache once warm');
        $this->assertLessThanOrEqual(2, $matching('from "appointments"'), 'the whole week should cost one appointment query pair');
    }

    public function test_seven_day_availability_stays_within_a_cold_query_budget(): void
    {
        [$user, $services] = $this->bookingFixture();
        $date = now('Asia/Manila')->addWeek()->toDateString();

        Cache::flush();

        $queries = 0;
        DB::listen(function () use (&$queries): void { $queries++; });

        $this->actingAs($user)->getJson(route('patient.appointments.dates', [
            'start_date' => $date, 'service_ids' => $services->pluck('id')->all(),
        ]))->assertOk();

        // Settings/business-hours/closures/dentists/services caches all cold, plus the two
        // week-spanning appointment queries and the request-user's patient lookup. clinic_settings
        // itself costs a SELECT + INSERT the very first time (firstOrCreate) - still a small,
        // fixed budget, nowhere near the 127 queries this replaced.
        $this->assertLessThanOrEqual(10, $queries, "Cold /dates executed {$queries} queries.");
    }

    public function test_availability_validation_does_not_query_per_service_id(): void
    {
        [$user, $services] = $this->bookingFixture();
        $date = now('Asia/Manila')->addWeek()->toDateString();
        $params = ['date' => $date, 'service_ids' => $services->pluck('id')->all()];

        // Warm the services cache with one request first - the regression this guards against
        // is one query PER service id (8, via Rule::exists), not the single shared cache read.
        $this->actingAs($user)->getJson(route('patient.appointments.slots', $params))->assertOk();

        $sql = [];
        DB::listen(function ($query) use (&$sql): void { $sql[] = $query->sql; });

        $this->actingAs($user)->getJson(route('patient.appointments.slots', $params))->assertOk();

        $servicesQueries = collect($sql)->filter(fn ($s) => str_contains($s, 'from "services"'))->count();
        $this->assertSame(0, $servicesQueries, 'validating 8 service_ids must not issue any services query once warm');
    }
}
