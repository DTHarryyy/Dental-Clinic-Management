<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_registry_fails_closed_for_every_role_status_and_unknown_value(): void
    {
        $common = [
            Permission::DashboardView,
            Permission::SearchUse,
            Permission::ProfileView,
            Permission::ProfileUpdate,
            Permission::NotificationsManage,
            Permission::PatientsView,
            Permission::PatientsCreate,
            Permission::AppointmentsView,
            Permission::AppointmentsCreate,
            Permission::AppointmentsManage,
        ];

        $expected = [
            'admin' => Permission::cases(),
            'dentist' => [...$common,
                Permission::PatientsUpdateClinical,
                Permission::PatientsViewClinical,
                Permission::RecordsView,
                Permission::RecordsCreate,
                Permission::TreatmentSummariesPublish,
            ],
            'receptionist' => [...$common,
                Permission::PatientsCreateExtendedDemographics,
                Permission::PatientsUpdateDemographics,
                Permission::PatientsChangeStatus,
                Permission::PatientsViewBilling,
                Permission::BillingView,
                Permission::BillingManage,
                Permission::PatientAccountsView,
                Permission::PatientAccountsManage,
                Permission::AppointmentChangeRequestsView,
                Permission::AppointmentChangeRequestsManage,
            ],
        ];

        foreach ($expected as $role => $allowed) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            foreach (Permission::cases() as $permission) {
                $this->assertSame(
                    in_array($permission, $allowed, true),
                    $user->hasPermission($permission),
                    "Unexpected {$role} grant for {$permission->value}",
                );
            }
        }

        $inactive = User::factory()->admin()->create(['status' => 'inactive']);
        $invalid = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $patient = User::factory()->create(['role' => 'patient', 'status' => 'active']);

        foreach (Permission::cases() as $permission) {
            $this->assertFalse($inactive->hasPermission($permission));
            $this->assertFalse($invalid->hasPermission($permission));
            $this->assertFalse($patient->hasPermission($permission));
            $this->assertFalse(Gate::forUser(null)->allows($permission->value));
        }
    }

    public function test_every_staff_business_route_has_session_denial_audit_and_explicit_ability_middleware(): void
    {
        $protectedNames = [
            'dashboard', 'search.', 'profile.', 'lookups.', 'notifications.', 'patients.',
            'appointments.', 'records.', 'billing.', 'reports', 'users.', 'settings.',
        ];

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! collect($protectedNames)->contains(fn (string $prefix) => $name === $prefix || str_starts_with($name, $prefix))) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, "{$name} is missing auth middleware");
            $this->assertContains('active.staff', $middleware, "{$name} is missing active-staff middleware");
            $this->assertContains('audit.denials', $middleware, "{$name} is missing denial auditing");
            $this->assertTrue(
                collect($middleware)->contains(fn (string $entry) => str_starts_with($entry, 'can:')),
                "{$name} is missing an explicit ability",
            );
        }
    }

    public function test_patient_detail_loading_and_output_are_isolated_by_discipline(): void
    {
        $patient = Patient::factory()->create([
            'allergies' => 'CLINICAL-ALLERGY-SECRET',
            'notes' => 'CLINICAL-HISTORY-SECRET',
        ]);
        DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'clinical_notes' => 'TREATMENT-NOTE-SECRET',
            'treatment_fee' => 987654,
        ]);
        Invoice::create([
            'patient_id' => $patient->id,
            'invoice_date' => today(),
            'subtotal' => 987654,
            'discount' => 0,
            'total' => 987654,
            'payment_status' => 'unpaid',
        ]);

        $receptionist = User::factory()->create();
        $this->actingAs($receptionist)->get(route('patients.detail-frame', $patient))
            ->assertOk()
            ->assertDontSee('CLINICAL-ALLERGY-SECRET')
            ->assertDontSee('CLINICAL-HISTORY-SECRET')
            ->assertDontSee('TREATMENT-NOTE-SECRET')
            ->assertSee('Billing History')
            ->assertSee('987,654.00');

        $dentist = User::factory()->dentist()->create();
        $this->actingAs($dentist)->get(route('patients.detail-frame', $patient))
            ->assertOk()
            ->assertSee('CLINICAL-ALLERGY-SECRET')
            ->assertSee('CLINICAL-HISTORY-SECRET')
            ->assertSee('TREATMENT-NOTE-SECRET')
            ->assertDontSee('Billing History')
            ->assertDontSee('987,654.00')
            ->assertViewHas('patient', fn (Patient $loaded) => ! array_key_exists(
                'treatment_fee',
                $loaded->dentalRecords->first()->toArray(),
            ));
    }

    public function test_patient_directory_cache_does_not_leak_last_visit_metadata_to_receptionists(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Cache', 'last_name' => 'Isolation']);
        DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'treatment_date' => '2025-01-02',
        ]);

        $this->actingAs(User::factory()->dentist()->create())->get(route('patients.index'))
            ->assertOk()
            ->assertSee('Last Visit')
            ->assertSee('Jan 2, 2025');

        $this->actingAs(User::factory()->create())->get(route('patients.index'))
            ->assertOk()
            ->assertDontSee('Last Visit')
            ->assertDontSee('Jan 2, 2025');
    }

    public function test_patient_mutation_endpoints_reject_forged_cross_discipline_fields(): void
    {
        $patient = Patient::factory()->create(['allergies' => 'Original allergy']);
        $receptionist = User::factory()->create();

        $this->actingAs($receptionist)->patchJson(route('patients.demographics.update', $patient), [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'allergies' => 'Forged allergy',
        ])->assertForbidden();
        $this->assertSame('Original allergy', $patient->fresh()->allergies);

        $dentist = User::factory()->dentist()->create();
        $this->actingAs($dentist)->patchJson(route('patients.clinical.update', $patient), [
            'allergies' => 'Authorized clinical value',
            'first_name' => 'Forged demographic value',
        ])->assertForbidden();
        $this->assertNotSame('Forged demographic value', $patient->fresh()->first_name);

        $this->actingAs($dentist)->postJson(route('patients.store'), [
            'first_name' => 'Forged',
            'last_name' => 'Extended Demographics',
            'occupation' => 'Should be receptionist managed',
        ])->assertForbidden();
        $this->actingAs($receptionist)->postJson(route('patients.store'), [
            'first_name' => 'Forged',
            'last_name' => 'Initial Status',
            'status' => 'inactive',
        ])->assertForbidden();

        $this->actingAs($receptionist)->get(route('records.index'))->assertForbidden();
        $this->actingAs($dentist)->get(route('billing.index'))->assertForbidden();
    }

    public function test_dentist_appointment_queries_objects_and_assignment_are_scoped_to_self(): void
    {
        $dentist = User::factory()->dentist()->create();
        $other = User::factory()->dentist()->create();
        $own = Appointment::factory()->create(['dentist_id' => $dentist->id, 'full_name' => 'OWN-SCHEDULE-PATIENT']);
        $foreign = Appointment::factory()->create(['dentist_id' => $other->id, 'full_name' => 'FOREIGN-SCHEDULE-PATIENT', 'status' => 'confirmed']);
        $own->serviceItems()->create([
            'name_snapshot' => 'Private price snapshot',
            'price_snapshot' => 7654,
            'duration_minutes_snapshot' => 30,
            'display_order' => 0,
        ]);

        $this->actingAs($dentist)->get(route('appointments.index', ['search' => 'SCHEDULE-PATIENT']))
            ->assertOk()
            ->assertSee('OWN-SCHEDULE-PATIENT')
            ->assertDontSee('FOREIGN-SCHEDULE-PATIENT')
            ->assertDontSee('7,654')
            ->assertViewHas('appointments', fn ($appointments) => ! array_key_exists(
                'price_snapshot',
                $appointments->first()->serviceItems->first()->toArray(),
            ));

        $this->actingAs($dentist)->getJson(route('search.index', ['q' => 'FOREIGN-SCHEDULE-PATIENT']))
            ->assertOk()
            ->assertJsonMissing(['title' => 'FOREIGN-SCHEDULE-PATIENT']);

        $this->actingAs($dentist)->post(route('appointments.status', $foreign), [
            'status' => 'cancelled',
            'cancellation_reason' => 'Forged access.',
        ])->assertNotFound();

        $this->actingAs($dentist)->postJson(route('appointments.store'), [
            'dentist_id' => $other->id,
        ])->assertForbidden();

        $patient = Patient::factory()->create(['status' => 'active']);
        $service = Service::create([
            'name' => 'Dentist self-assigned booking',
            'price' => 500,
            'duration' => '30 min',
            'duration_minutes' => 30,
        ]);
        $date = now()->addMonth()->toDateString();
        $this->actingAs($dentist)->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'preferred_date' => $date,
            'preferred_time_window' => 'morning',
            'requested_start_at' => "{$date} 08:00",
            'service_ids' => [$service->id],
        ])->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', ['id' => $own->id, 'dentist_id' => $dentist->id]);
        $this->assertDatabaseHas('appointments', ['id' => $foreign->id, 'dentist_id' => $other->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'status' => 'pending',
        ]);
    }

    public function test_dentist_record_price_is_server_derived_and_never_creates_or_exposes_an_invoice(): void
    {
        $dentist = User::factory()->dentist()->create();
        $other = User::factory()->dentist()->create();
        $patient = Patient::factory()->create();
        $service = Service::create(['name' => 'Server Priced Procedure', 'price' => 4321, 'duration' => 30]);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'service' => $service->name,
            'status' => 'confirmed',
        ]);
        $payload = [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'treatment_date' => today()->toDateString(),
            'procedure' => $service->name,
            'clinical_notes' => 'Clinical continuity note.',
        ];

        $this->actingAs($dentist)->postJson(route('records.store'), [...$payload, 'dentist_id' => $other->id])
            ->assertForbidden();

        $this->actingAs($dentist)->postJson(route('records.store'), $payload)->assertOk();

        $record = DentalRecord::sole();
        $this->assertEquals(4321, $record->treatment_fee);
        $this->assertDatabaseCount('invoices', 0);
        $this->actingAs($dentist)->get(route('records.show', $record))
            ->assertOk()
            ->assertDontSee('Billing')
            ->assertDontSee('4,321.00')
            ->assertViewHas('record', fn (DentalRecord $loaded) => ! array_key_exists('treatment_fee', $loaded->toArray()));
    }

    public function test_billing_handoff_is_safe_adjustable_consistent_and_single_use(): void
    {
        Queue::fake();
        $receptionist = User::factory()->create();
        $patient = Patient::factory()->create(['status' => 'inactive', 'email' => 'billing@example.test']);
        $otherPatient = Patient::factory()->create(['status' => 'active']);
        $record = DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'procedure' => 'Safe billing procedure',
            'clinical_notes' => 'NEVER-SHOW-CLINICAL-NOTE',
            'prescription' => 'NEVER-SHOW-PRESCRIPTION',
            'treatment_fee' => 1350,
        ]);

        $this->actingAs($receptionist)->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Safe billing procedure')
            ->assertSee('1,350.00')
            ->assertDontSee('NEVER-SHOW-CLINICAL-NOTE')
            ->assertDontSee('NEVER-SHOW-PRESCRIPTION');
        $this->actingAs($receptionist)->get(route('billing.create', ['record' => $record]))
            ->assertOk()
            ->assertSee('Safe billing procedure')
            ->assertDontSee('NEVER-SHOW-CLINICAL-NOTE')
            ->assertDontSee('NEVER-SHOW-PRESCRIPTION');

        $payload = [
            'dental_record_id' => $record->id,
            'patient_id' => $otherPatient->id,
            'invoice_date' => today()->toDateString(),
            'items' => [['description' => 'Adjusted billing item', 'qty' => 1, 'price' => 1400]],
        ];
        $this->actingAs($receptionist)->post(route('billing.store'), $payload)
            ->assertSessionHasErrors('patient_id');
        $this->assertDatabaseCount('invoices', 0);

        $payload['patient_id'] = $patient->id;
        $this->actingAs($receptionist)->post(route('billing.store'), $payload)->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'dental_record_id' => $record->id,
            'patient_id' => $patient->id,
            'total' => 1400,
        ]);

        $this->actingAs($receptionist)->post(route('billing.store'), $payload)
            ->assertSessionHasErrors('dental_record_id');
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_inactive_and_invalid_sessions_are_revoked_and_authorization_denials_are_audited(): void
    {
        $inactive = User::factory()->admin()->create(['status' => 'inactive']);
        $this->actingAs($inactive)->getJson(route('dashboard'))->assertUnauthorized();
        $this->assertGuest();
        $this->assertDatabaseHas('security_audit_logs', [
            'actor_user_id' => $inactive->id,
            'event' => 'session.blocked',
            'result' => 'denied',
        ]);
        $this->actingAs($inactive)->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $invalid = User::factory()->create(['role' => 'owner']);
        $this->actingAs($invalid)->getJson(route('dashboard'))->assertUnauthorized();
        $this->assertGuest();
        $this->assertDatabaseHas('security_audit_logs', [
            'actor_user_id' => $invalid->id,
            'event' => 'session.blocked',
            'result' => 'denied',
        ]);

        $receptionist = User::factory()->create();
        $this->actingAs($receptionist)->get(route('records.index'))->assertForbidden();
        $this->assertDatabaseHas('security_audit_logs', [
            'actor_user_id' => $receptionist->id,
            'event' => 'authorization.denied',
            'ability' => 'viewAny',
            'result' => 'denied',
            'route_name' => 'records.index',
        ]);
    }

    public function test_staff_cannot_remove_or_change_self_access_and_an_active_admin_always_remains(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Primary Admin']);

        $this->actingAs($admin)->put(route('users.update', $admin), $this->staffPayload($admin, [
            'role' => 'dentist',
        ]))->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);

        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'status' => 'active']);

        $second = User::factory()->admin()->create(['name' => 'Secondary Admin']);
        $this->actingAs($admin)->put(route('users.update', $second), $this->staffPayload($second, [
            'role' => 'receptionist',
        ]))->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('security_audit_logs', [
            'event' => 'user.access_changed',
            'actor_user_id' => $admin->id,
            'target_id' => (string) $second->id,
            'result' => 'allowed',
        ]);

        $this->assertSame(1, User::query()->where('role', 'admin')->where('status', 'active')->count());
        $this->actingAs($admin)->put(route('users.update', $admin), $this->staffPayload($admin, [
            'status' => 'inactive',
        ]))->assertSessionHasErrors('role');
        $this->assertSame(1, User::query()->where('role', 'admin')->where('status', 'active')->count());

        $staff = User::factory()->create(['supabase_uid' => null]);
        $this->actingAs($admin)->delete(route('users.destroy', $staff))->assertRedirect(route('users.index'));
        $this->assertModelMissing($staff);
        $this->assertDatabaseHas('security_audit_logs', [
            'event' => 'user.deleted',
            'actor_user_id' => $admin->id,
            'target_id' => (string) $staff->id,
            'result' => 'allowed',
        ]);
    }

    public function test_staff_creation_records_a_non_secret_security_audit_event(): void
    {
        $admin = User::factory()->admin()->create();
        $supabase = \Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('adminCreateUser')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => 'audit-created-supabase-id'],
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Audit',
            'last_name' => 'Created',
            'email' => 'audit-created@example.test',
            'role' => 'dentist',
            'status' => 'inactive',
        ])->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'audit-created@example.test')->sole();
        $this->assertDatabaseHas('security_audit_logs', [
            'event' => 'user.created',
            'actor_user_id' => $admin->id,
            'target_id' => (string) $created->id,
            'result' => 'allowed',
        ]);
        $context = (string) \Illuminate\Support\Facades\DB::table('security_audit_logs')
            ->where('event', 'user.created')
            ->value('context');
        $this->assertStringNotContainsString('password', strtolower($context));
    }

    private function staffPayload(User $user, array $overrides = []): array
    {
        $parts = explode(' ', $user->name, 2);

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'Staff',
            'email' => $user->email,
            'phone' => $user->phone,
            'license_no' => $user->license_no,
            'role' => $user->role,
            'status' => $user->status,
            ...$overrides,
        ];
    }
}
