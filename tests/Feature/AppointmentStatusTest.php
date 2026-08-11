<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentStatusTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(string $status): Appointment
    {
        return Appointment::factory()->create(['status' => $status]);
    }

    private function move(Appointment $appointment, string $to)
    {
        return $this->actingAs(User::factory()->admin()->create())
            ->post(route('appointments.status', $appointment), ['status' => $to, ...($to === 'cancelled' ? ['cancellation_reason' => 'Patient requested cancellation.'] : [])]);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function allowedTransitions(): array
    {
        return [
            'pending -> confirmed' => ['pending', 'confirmed'],
            'pending -> cancelled' => ['pending', 'cancelled'],
            'confirmed -> cancelled' => ['confirmed', 'cancelled'],
            'confirmed -> completed' => ['confirmed', 'completed'],
            'cancelled -> pending (reopen)' => ['cancelled', 'pending'],
            'completed -> confirmed (reopen, no record)' => ['completed', 'confirmed'],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transitions_go_through(string $from, string $to): void
    {
        $appointment = $this->appointment($from);

        $this->move($appointment, $to)->assertRedirect();

        $this->assertSame($to, $appointment->fresh()->status);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function rejectedTransitions(): array
    {
        return [
            'pending cannot jump to completed' => ['pending', 'completed'],
            'cancelled cannot be confirmed directly' => ['cancelled', 'confirmed'],
            'cancelled cannot be completed' => ['cancelled', 'completed'],
            'completed cannot be cancelled' => ['completed', 'cancelled'],
            'completed cannot go back to pending' => ['completed', 'pending'],
            'confirmed cannot go back to pending' => ['confirmed', 'pending'],
        ];
    }

    #[DataProvider('rejectedTransitions')]
    public function test_rejected_transitions_are_refused(string $from, string $to): void
    {
        $appointment = $this->appointment($from);

        $this->move($appointment, $to)->assertSessionHasErrors('appointment');

        $this->assertSame($from, $appointment->fresh()->status);
    }

    public function test_a_completed_appointment_with_a_record_cannot_be_reopened(): void
    {
        $appointment = $this->appointment('completed');
        DentalRecord::factory()->create(['appointment_id' => $appointment->id]);

        $this->move($appointment, 'confirmed')->assertSessionHasErrors('appointment');

        $this->assertSame('completed', $appointment->fresh()->status);
    }

    public function test_cancelling_a_completed_appointment_can_no_longer_strand_its_record(): void
    {
        $appointment = $this->appointment('completed');
        $record = DentalRecord::factory()->create(['appointment_id' => $appointment->id]);

        // The old controller allowed this outright, leaving a treatment record hanging off
        // a cancelled visit.
        $this->move($appointment, 'cancelled')->assertSessionHasErrors('appointment');

        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertSame($appointment->id, $record->fresh()->appointment_id);
    }

    public function test_a_reopened_appointment_can_run_the_lifecycle_again(): void
    {
        $appointment = $this->appointment('confirmed');

        $this->move($appointment, 'cancelled');
        $this->assertSame('cancelled', $appointment->fresh()->status);

        $this->move($appointment, 'pending');
        $this->assertSame('pending', $appointment->fresh()->status);

        $this->move($appointment, 'confirmed');
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $appointment = $this->appointment('pending');

        $this->move($appointment, 'banana')->assertSessionHasErrors('status');

        $this->assertSame('pending', $appointment->fresh()->status);
    }

    public function test_the_cancel_button_opens_a_confirmation_instead_of_posting(): void
    {
        $appointment = $this->appointment('confirmed');

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('appointments.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/data-open-cancel="([^"]*)"/', $html, $matches));
        $payload = json_decode(html_entity_decode($matches[1]), true);

        $this->assertSame(route('appointments.status', $appointment), $payload['action']);
        $this->assertSame($appointment->patient->name, $payload['name']);
        $this->assertStringContainsString($appointment->appointment_date->format('M j, Y'), $payload['when']);
    }

    public function test_a_cancelled_row_offers_a_way_back(): void
    {
        $this->appointment('cancelled');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Reopen');
    }

    public function test_a_completed_row_without_a_record_says_so(): void
    {
        $this->appointment('completed');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('No record');
    }

    public function test_the_status_change_answers_ajax_with_a_redirect_target(): void
    {
        $appointment = $this->appointment('confirmed');

        // The cancel dialog submits through dialog-forms.js, which needs JSON back rather
        // than a 302 it would blindly follow into un-parseable HTML.
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('appointments.status', $appointment), ['status' => 'cancelled', 'cancellation_reason' => 'Patient requested cancellation.'])
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $this->assertSame('cancelled', $appointment->fresh()->status);
    }
}
