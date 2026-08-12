<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ResponsiveE2ESeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        Patient::query()->first()?->update([
            'first_name' => 'Alexandria-Marguerite',
            'last_name' => 'Villanueva-Santos-Washington',
            'email' => 'alexandria.very.long.patient.address@example-clinic.test',
            'address' => 'A deliberately long residential address used to verify that realistic content wraps without widening a compact viewport.',
            'notes' => str_repeat('Long clinical context must remain readable on compact screens. ', 4),
        ]);

        User::factory()->create([
            'name' => 'Receptionist Responsive Test',
            'email' => 'responsive-receptionist@example.test',
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $today = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $dentist = User::query()->where('role', 'dentist')->where('status', 'active')->firstOrFail();
        $reportPatient = Patient::factory()->create([
            'first_name' => 'Report',
            'last_name' => 'Fixture',
            'email' => 'report-fixture@example.test',
            'created_at' => $today->addHours(8)->utc(),
        ]);
        $appointment = Appointment::create([
            'patient_id' => $reportPatient->id,
            'dentist_id' => $dentist->id,
            'full_name' => $reportPatient->name,
            'contact_number' => $reportPatient->mobile,
            'email' => $reportPatient->email,
            'appointment_date' => $today->toDateString(),
            'appointment_time' => '09:00',
            'preferred_date' => $today->toDateString(),
            'preferred_time_window' => 'morning',
            'scheduled_start_at' => $today->addHours(9)->utc(),
            'scheduled_end_at' => $today->addHours(9)->addMinutes(30)->utc(),
            'duration_minutes' => 30,
            'service' => 'Cleaning',
            'status' => 'completed',
        ]);
        $record = DentalRecord::create([
            'patient_id' => $reportPatient->id,
            'appointment_id' => $appointment->id,
            'dentist_id' => $dentist->id,
            'treatment_date' => $today->toDateString(),
            'procedure' => 'Cleaning',
            'clinical_notes' => 'Deterministic responsive report fixture.',
            'treatment_fee' => 1200,
        ]);
        $invoice = Invoice::create([
            'patient_id' => $reportPatient->id,
            'dental_record_id' => $record->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->toDateString(),
            'subtotal' => 1200,
            'discount' => 0,
            'total' => 1200,
            'payment_status' => 'partial',
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'method' => 'Cash',
            'reference' => 'E2E report fixture',
            'paid_at' => $today->addHours(10)->utc(),
        ]);
    }
}
