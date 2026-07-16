<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        // Draw from the canonical default catalog so seeded data matches the real services.
        $services = array_column(ServiceSeeder::DEFAULTS, 'name');

        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => User::factory()->dentist(),
            'full_name' => fake()->name(),
            'contact_number' => fake()->numerify('09#########'),
            'email' => fake()->safeEmail(),
            'appointment_date' => fake()->dateTimeBetween('-2 months', '+2 months')->format('Y-m-d'),
            'appointment_time' => fake()->randomElement(['09:00', '10:30', '11:00', '13:00', '14:30', '16:00']),
            'service' => fake()->randomElement($services),
            'concern' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
        ];
    }
}
