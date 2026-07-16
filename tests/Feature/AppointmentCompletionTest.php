<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function service(): Service
    {
        return Service::create(['name' => 'Tooth Extraction', 'price' => 1500, 'duration' => '45 min']);
    }

    private function confirmedAppointment(): Appointment
    {
        return Appointment::factory()->create([
            'status' => 'confirmed',
            'service' => $this->service()->name,
        ]);
    }

    /** @return array{0: User, 1: Appointment} */
    private function dentistAndAppointment(): array
    {
        return [User::factory()->dentist()->create(), $this->confirmedAppointment()];
    }

    private function payload(Appointment $appointment, array $overrides = []): array
    {
        return [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'dentist_id' => $appointment->dentist_id,
            'treatment_date' => $appointment->appointment_date->toDateString(),
            'procedure' => $appointment->service,
            'clinical_notes' => 'Extracted #16, no complications.',
            'treatment_fee' => 1500,
            ...$overrides,
        ];
    }

    public function test_filing_the_record_completes_the_appointment_and_links_the_two(): void
    {
        [$dentist, $appointment] = $this->dentistAndAppointment();

        $this->actingAs($dentist)
            ->postJson(route('records.store'), $this->payload($appointment))
            ->assertOk()
            ->assertJson(['redirect' => route('appointments.index')]);

        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertDatabaseHas('dental_records', [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'procedure' => 'Tooth Extraction',
        ]);
    }

    public function test_an_appointment_cannot_be_completed_twice(): void
    {
        [$dentist, $appointment] = $this->dentistAndAppointment();
        DentalRecord::factory()->create(['appointment_id' => $appointment->id]);

        $this->actingAs($dentist)
            ->postJson(route('records.store'), $this->payload($appointment))
            ->assertStatus(422)
            ->assertJsonValidationErrors('appointment_id');

        $this->assertSame(1, DentalRecord::where('appointment_id', $appointment->id)->count());
    }

    public function test_a_cancelled_appointment_cannot_be_completed_by_filing_a_record(): void
    {
        [$dentist, $appointment] = $this->dentistAndAppointment();
        $appointment->update(['status' => 'cancelled']);

        $this->actingAs($dentist)
            ->postJson(route('records.store'), $this->payload($appointment))
            ->assertStatus(422)
            ->assertJsonValidationErrors('appointment_id');

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertDatabaseCount('dental_records', 0);
    }

    public function test_a_rejected_record_leaves_the_appointment_untouched(): void
    {
        [$dentist, $appointment] = $this->dentistAndAppointment();

        $this->actingAs($dentist)
            ->postJson(route('records.store'), $this->payload($appointment, ['clinical_notes' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('clinical_notes');

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertDatabaseCount('dental_records', 0);
    }

    public function test_completing_can_raise_the_invoice_in_the_same_step(): void
    {
        [$dentist, $appointment] = $this->dentistAndAppointment();

        $this->actingAs($dentist)
            ->postJson(route('records.store'), $this->payload($appointment, ['create_invoice' => 1]))
            ->assertOk();

        $record = DentalRecord::where('appointment_id', $appointment->id)->sole();

        $this->assertDatabaseHas('invoices', [
            'dental_record_id' => $record->id,
            'patient_id' => $appointment->patient_id,
            'total' => 1500,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_a_walk_in_record_has_no_appointment_and_still_lands_on_the_record(): void
    {
        $dentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create();
        $this->service();

        $this->actingAs($dentist)
            ->postJson(route('records.store'), [
                'patient_id' => $patient->id,
                'treatment_date' => now()->toDateString(),
                'procedure' => 'Tooth Extraction',
                'clinical_notes' => 'Walk-in, no prior booking.',
            ])
            ->assertOk();

        $record = DentalRecord::sole();

        $this->assertNull($record->appointment_id);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_receptionists_complete_appointments_without_a_record(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);
        $appointment = $this->confirmedAppointment();

        $this->actingAs($receptionist)
            ->post(route('appointments.status', $appointment), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertDatabaseCount('dental_records', 0);
    }

    public function test_receptionists_cannot_reach_the_record_writing_route(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);
        $appointment = $this->confirmedAppointment();

        $this->actingAs($receptionist)
            ->postJson(route('records.store'), $this->payload($appointment))
            ->assertForbidden();

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_the_complete_button_carries_the_rows_prefill_for_dentists_only(): void
    {
        $appointment = $this->confirmedAppointment();

        $html = $this->actingAs(User::factory()->dentist()->create())
            ->get(route('appointments.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/data-open-dialog="([^"]*)"/', $html, $matches));
        $payload = json_decode(html_entity_decode($matches[1]), true);

        $this->assertSame('appointment-complete', $payload['id']);
        $this->assertSame($appointment->id, $payload['fields']['appointment_id']);
        $this->assertSame($appointment->patient_id, $payload['fields']['patient_id']);
        $this->assertSame($appointment->dentist_id, $payload['fields']['dentist_id']);
        $this->assertSame($appointment->appointment_date->toDateString(), $payload['fields']['treatment_date']);
        $this->assertSame('Tooth Extraction', $payload['fields']['procedure']);
        // Postgres hands back decimals as strings and SQLite as numbers, and either is fine
        // once it reaches the number input - only the amount itself matters here.
        $this->assertEquals(1500, $payload['fields']['treatment_fee']);
        $this->assertSame($appointment->patient->name, $payload['text']['patient_name']);
        $this->assertSame(route('appointments.status', $appointment), $payload['actions']['skip']);

        $this->actingAs(User::factory()->create(['role' => 'receptionist', 'status' => 'active']))
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertDontSee('data-open-dialog', escape: false);
    }

    public function test_the_prefill_survives_a_service_that_left_the_catalog(): void
    {
        $appointment = $this->confirmedAppointment();
        Service::where('name', 'Tooth Extraction')->delete();

        $html = $this->actingAs(User::factory()->dentist()->create())
            ->get(route('appointments.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/data-open-dialog="([^"]*)"/', $html, $matches));
        $payload = json_decode(html_entity_decode($matches[1]), true);

        // No catalog row means no price to seed; the booked name still rides along so the
        // dialog can explain why the procedure dropdown came up empty.
        $this->assertNull($payload['fields']['treatment_fee']);
        $this->assertSame('Tooth Extraction', $payload['text']['booked_service']);
    }
}
