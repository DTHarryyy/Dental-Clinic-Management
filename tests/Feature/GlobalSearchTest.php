<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_is_authenticated_and_validated(): void
    {
        $this->getJson(route('search.index', ['q' => 'maria']))->assertUnauthorized();
        $this->actingAs(User::factory()->admin()->create())->getJson(route('search.index', ['q' => 'x']))->assertUnprocessable();
        $this->actingAs(User::factory()->admin()->create())->getJson(route('search.index', ['q' => str_repeat('x', 101)]))->assertUnprocessable();
    }

    public function test_admin_search_returns_the_stable_group_shape_and_matching_fields(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Maria', 'last_name' => 'Global', 'email' => 'maria.global@example.test']);
        Appointment::factory()->create(['patient_id' => $patient->id, 'full_name' => 'Maria Global', 'service' => 'Searchable Orthodontics']);
        DentalRecord::factory()->create(['patient_id' => $patient->id, 'procedure' => 'Searchable Orthodontics', 'clinical_notes' => 'private phrase']);
        $invoice = Invoice::create(['patient_id' => $patient->id, 'invoice_date' => today(), 'total' => 500, 'subtotal' => 500, 'discount' => 0, 'payment_status' => 'unpaid']);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'description' => 'Searchable Orthodontics', 'qty' => 1, 'price' => 500]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('search.index', ['q' => 'Searchable Orthodontics']))
            ->assertOk()
            ->assertJsonStructure(['query', 'groups' => [['type', 'label', 'results']], 'total']);

        $this->assertSame(['patients', 'appointments', 'records', 'billing'], collect($response->json('groups'))->pluck('type')->all());
        $this->assertGreaterThanOrEqual(3, $response->json('total'));
        $firstResult = collect($response->json('groups'))->flatMap(fn ($group) => $group['results'])->first();
        $this->assertSame(['id', 'title', 'subtitle', 'meta', 'url'], array_keys($firstResult));
    }

    public function test_search_groups_follow_role_permissions(): void
    {
        $dentist = User::factory()->dentist()->create();
        $receptionist = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);

        $dentistGroups = collect($this->actingAs($dentist)->getJson(route('search.index', ['q' => 'example']))->json('groups'))->pluck('type');
        $this->assertSame(['patients', 'appointments', 'records'], $dentistGroups->all());

        $receptionistGroups = collect($this->actingAs($receptionist)->getJson(route('search.index', ['q' => 'example']))->json('groups'))->pluck('type');
        $this->assertSame(['patients', 'appointments', 'billing'], $receptionistGroups->all());
    }

    public function test_invoice_number_variants_find_the_exact_invoice(): void
    {
        $patient = Patient::factory()->create();
        $invoice = Invoice::forceCreate(['id' => 42, 'patient_id' => $patient->id, 'invoice_date' => today(), 'total' => 100, 'subtotal' => 100, 'discount' => 0, 'payment_status' => 'paid']);
        $user = User::factory()->admin()->create();

        foreach (['42', '0042', 'INV-0042'] as $query) {
            $billing = collect($this->actingAs($user)->getJson(route('search.index', ['q' => $query]))->json('groups'))->firstWhere('type', 'billing');
            $this->assertSame('invoice-'.$invoice->id, $billing['results'][0]['id']);
        }
    }

    public function test_sensitive_clinical_free_text_is_not_searched_and_queries_are_bounded(): void
    {
        DentalRecord::factory()->create(['clinical_notes' => 'uniquely-private-clinical-phrase', 'prescription' => 'private-prescription']);
        Appointment::factory()->create(['concern' => 'private-appointment-concern']);
        $user = User::factory()->admin()->create();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $response = $this->actingAs($user)->getJson(route('search.index', ['q' => 'uniquely-private-clinical-phrase']))->assertOk();

        $this->assertSame(0, $response->json('total'), json_encode($response->json('groups')));
        $this->assertLessThanOrEqual(4, $queries);
    }
}
