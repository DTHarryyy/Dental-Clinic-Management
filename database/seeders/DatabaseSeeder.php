<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with realistic dev data
     * so page load / filter / pagination performance is testable.
     */
    public function run(): void
    {
        // Log-in accounts (password for all seeded users is "password").
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'receptionist',
            'status' => 'active',
        ]);

        $dentists = User::factory()->count(4)->dentist()->create();

        // Idempotent default catalog — the single source of truth for services.
        $services = (new ServiceSeeder)->run();

        DB::transaction(function () use ($dentists, $services) {
            Patient::factory()->count(120)->create()->each(function (Patient $patient) use ($dentists, $services) {
                // Appointments
                Appointment::factory()->count(rand(0, 4))->create([
                    'patient_id' => $patient->id,
                    'dentist_id' => $dentists->random()->id,
                    'full_name' => $patient->name,
                    'contact_number' => $patient->mobile,
                    'email' => $patient->email,
                    'service' => $services->random()->name,
                ]);

                // Dental records, ~70% of which get an invoice
                for ($i = 0; $i < rand(0, 3); $i++) {
                    $record = DentalRecord::factory()->create([
                        'patient_id' => $patient->id,
                        'dentist_id' => $dentists->random()->id,
                    ]);

                    if (rand(1, 100) <= 70) {
                        $this->createInvoice($patient, $record, $services);
                    }
                }
            });
        });
    }

    private function createInvoice(Patient $patient, DentalRecord $record, $services): void
    {
        $lineItems = collect(range(1, rand(1, 4)))->map(function () use ($services) {
            $service = $services->random();

            return [
                'description' => $service->name,
                'qty' => rand(1, 2),
                'price' => $service->price,
            ];
        });

        $subtotal = $lineItems->sum(fn ($item) => $item['qty'] * $item['price']);
        $discount = fake()->randomElement([0, 0, 0, 200, 500]);
        $total = max($subtotal - $discount, 0);

        $desiredStatus = fake()->randomElement(['unpaid', 'paid', 'paid', 'partial', 'pending']);
        $method = fake()->randomElement(\App\Enums\PaymentMethod::values());
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'dental_record_id' => $record->id,
            'invoice_date' => $record->treatment_date,
            'due_date' => $record->treatment_date->copy()->addDays(15),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'payment_status' => 'unpaid',
        ]);

        foreach ($lineItems as $item) {
            InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
        }

        // 'pending' seeds an unverified patient submission so the verification
        // queue is non-empty on a fresh migrate:fresh --seed.
        if ($desiredStatus === 'pending' && $total > 0) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => round($total / 2, 2),
                'method' => 'GCash',
                'status' => \App\Enums\PaymentStatus::Pending,
                'paid_at' => $record->treatment_date->copy()->addDays(rand(0, 10)),
                'reference' => 'Seeded pending payment',
            ]);
        } elseif ($desiredStatus !== 'unpaid' && $total > 0) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $desiredStatus === 'paid' ? $total : round($total / 2, 2),
                'method' => $method,
                'paid_at' => $record->treatment_date->copy()->addDays(rand(0, 10)),
                'reference' => 'Seeded payment',
            ]);
            $invoice->syncPaymentStatus();
        }
    }
}
