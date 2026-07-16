<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DentalRecord>
 */
class DentalRecordFactory extends Factory
{
    public function definition(): array
    {
        $treatmentDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => User::factory()->dentist(),
            'treatment_date' => $treatmentDate->format('Y-m-d'),
            'procedure' => fake()->randomElement(array_column(ServiceSeeder::DEFAULTS, 'name')),
            'tooth_area' => fake()->randomElement(['Upper Left', 'Upper Right', 'Lower Left', 'Lower Right', 'Front Teeth']),
            'next_appointment_date' => fake()->optional()->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'clinical_notes' => fake()->sentence(),
            'prescription' => fake()->optional()->randomElement(['Amoxicillin 500mg', 'Mefenamic acid 500mg', 'Ibuprofen 400mg']),
            'treatment_fee' => fake()->randomFloat(2, 500, 15000),
        ];
    }
}
