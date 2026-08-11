<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentConfirmationEmailTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointments_have_nullable_confirmation_email_tracking_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('appointments', [
            'confirmation_email_sent_at',
            'confirmation_email_message_id',
            'confirmation_email_error',
        ]));

        $appointment = Appointment::factory()->create([
            'email' => null,
            'confirmation_email_sent_at' => null,
            'confirmation_email_message_id' => null,
            'confirmation_email_error' => null,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'email' => null,
            'confirmation_email_sent_at' => null,
            'confirmation_email_message_id' => null,
            'confirmation_email_error' => null,
        ]);
    }
}
