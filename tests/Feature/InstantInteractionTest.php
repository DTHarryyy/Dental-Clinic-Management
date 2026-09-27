<?php

namespace Tests\Feature;

use App\Auth\CachedEloquentUserProvider;
use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Server side of the "feels instant" work: every query here is a round trip to a remote
 * database, so these pin the round-trip counts and the optimistic-UI response contracts.
 */
class InstantInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function countQueries(callable $callback): int
    {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });
        $callback();

        return $queries;
    }

    public function test_dialog_saves_hand_the_flash_message_to_the_client_toast(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin)
            ->withHeaders(['X-Client-Toast' => '1'])
            ->patchJson(route('patients.status.update', $patient), ['status' => 'inactive'])
            ->assertOk()
            ->assertJson(['message' => 'Patient status updated.']);

        // Taken, not copied: the background refresh must not toast it a second time.
        $this->assertNull(session('status'));
    }

    public function test_without_the_client_toast_header_the_flash_stays_for_the_next_render(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin)
            ->patchJson(route('patients.status.update', $patient), ['status' => 'inactive'])
            ->assertOk()
            ->assertJson(['message' => null]);

        $this->assertSame('Patient status updated.', session('status'));
    }

    public function test_dialog_patient_edits_return_to_the_list_the_user_was_on(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();
        $list = route('patients.index', ['search' => 'ma', 'page' => 2]);

        $this->actingAs($admin)
            ->withHeaders(['Referer' => $list])
            ->patchJson(route('patients.demographics.update', $patient), [
                'first_name' => 'Maria',
                'last_name' => $patient->last_name,
                'gender' => $patient->gender,
            ])
            ->assertOk()
            ->assertJson(['redirect' => $list]);
    }

    public function test_notification_mark_read_answers_json_for_the_optimistic_bell(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'test',
            'data' => ['title' => 'Hello'],
        ]);
        $notification = $admin->notifications()->first();

        $this->actingAs($admin)->patchJson(route('notifications.read', $notification))->assertOk()->assertJson(['ok' => true]);
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($admin)->patchJson(route('notifications.read-all'))->assertOk()->assertJson(['ok' => true]);
    }

    public function test_the_bell_payload_counts_unread_in_the_same_query_as_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (range(1, 12) as $i) {
            $admin->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'test',
                'data' => ['title' => "N{$i}"],
                'read_at' => $i <= 3 ? now() : null,
            ]);
        }

        $this->actingAs($admin);
        $queries = $this->countQueries(function () {
            $this->getJson(route('notifications.index'))
                ->assertOk()
                ->assertJsonPath('unread_count', 9)
                ->assertJsonCount(10, 'notifications');
        });

        $this->assertSame(1, $queries);
    }

    public function test_session_user_is_served_from_cache_and_forgotten_on_save(): void
    {
        $user = User::factory()->admin()->create();
        $provider = Auth::createUserProvider('users');
        $this->assertInstanceOf(CachedEloquentUserProvider::class, $provider);

        $provider->retrieveById($user->id);
        $this->assertSame(0, $this->countQueries(fn () => $provider->retrieveById($user->id)));

        $user->update(['status' => 'inactive']);
        $this->assertFalse(Cache::has(CachedEloquentUserProvider::cacheKey($user->id)));
        $this->assertSame('inactive', $provider->retrieveById($user->id)->status);
    }

    public function test_routine_auth_writes_do_not_flush_shared_caches(): void
    {
        $user = User::factory()->admin()->create();
        User::cachedDentists();
        $this->assertTrue(Cache::has(User::DENTISTS_CACHE_KEY));

        $user->forceFill(['remember_token' => 'rotated'])->save();
        $this->assertTrue(Cache::has(User::DENTISTS_CACHE_KEY));

        $user->update(['name' => 'Renamed']);
        $this->assertFalse(Cache::has(User::DENTISTS_CACHE_KEY));
    }

    public function test_unpaid_invoices_do_not_re_query_their_payment_sums(): void
    {
        $patient = Patient::factory()->create();
        Invoice::create(['patient_id' => $patient->id, 'invoice_date' => today(), 'subtotal' => 500, 'discount' => 0, 'total' => 500, 'payment_status' => 'unpaid']);

        $invoice = Invoice::query()->withPaymentTotals()->firstOrFail();

        $queries = $this->countQueries(function () use ($invoice) {
            $this->assertSame(0.0, $invoice->amount_paid);
            $this->assertSame(500.0, $invoice->balance);
            $invoice->display_status;
        });

        $this->assertSame(0, $queries);
    }

    public function test_fast_paginate_counts_in_the_page_query_and_handles_out_of_range_pages(): void
    {
        Patient::factory()->count(7)->create();

        $queries = $this->countQueries(function () {
            $page = Patient::query()->orderBy('id')->fastPaginate(3);
            $this->assertSame(7, $page->total());
            $this->assertSame(3, $page->lastPage());
            $this->assertCount(3, $page->items());
            $this->assertArrayNotHasKey('__window_total_rows', $page->items()[0]->getAttributes());
        });
        $this->assertSame(1, $queries);

        $this->app['request']->query->set('page', 9);
        $beyond = Patient::query()->orderBy('id')->fastPaginate(3);
        $this->assertSame(7, $beyond->total());
        $this->assertCount(0, $beyond->items());
    }

    public function test_list_pages_fold_relations_into_the_page_query(): void
    {
        $admin = User::factory()->admin()->create();
        $dentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id]);
        $record = DentalRecord::factory()->create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'appointment_id' => $appointment->id]);
        $invoice = Invoice::create(['patient_id' => $patient->id, 'invoice_date' => today(), 'subtotal' => 500, 'discount' => 0, 'total' => 500, 'payment_status' => 'partial']);
        Payment::create(['invoice_id' => $invoice->id, 'amount' => 100, 'method' => 'Cash', 'paid_at' => now()]);

        // Warm the per-namespace caches (summaries, catalogs) so only per-request work counts.
        $this->actingAs($admin);
        foreach (['appointments.index', 'records.index', 'billing.index'] as $route) {
            $this->get(route($route))->assertOk();
        }

        // Page query (rows + total + names) and one eager load (service items / invoice items).
        $budgets = ['appointments.index' => 2, 'records.index' => 1, 'billing.index' => 2];
        foreach ($budgets as $route => $budget) {
            $response = null;
            $queries = $this->countQueries(function () use ($route, &$response) {
                $response = $this->get(route($route));
            });
            $response->assertOk()->assertSee($patient->name);
            $this->assertLessThanOrEqual($budget, $queries, "{$route} executed {$queries} queries.");
        }
    }
}
