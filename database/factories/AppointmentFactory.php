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
    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Appointment $appointment) {
            if (blank($appointment->appointment_time)) {
                $appointment->scheduled_start_at = null;
                $appointment->scheduled_end_at = null;
                return;
            }
            $start = \Carbon\CarbonImmutable::parse($appointment->appointment_date->toDateString().' '.$appointment->appointment_time, 'Asia/Manila')->utc();
            $appointment->scheduled_start_at = $start;
            $appointment->scheduled_end_at = $start->addMinutes(30);
        });
    }

    public function definition(): array
    {
        // Draw from the canonical default catalog so seeded data matches the real services.
        $services = array_column(ServiceSeeder::DEFAULTS, 'name');

        $date = fake()->dateTimeBetween('-2 months', '+2 months')->format('Y-m-d');
        $time = fake()->randomElement(['09:00', '10:30', '11:00', '13:00', '14:30', '16:00']);
        $start = \Carbon\CarbonImmutable::parse("{$date} {$time}", 'Asia/Manila')->utc();
        $status = fake()->randomElement(['pending', 'confirmed', 'completed', 'cancelled']);
        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => User::factory()->dentist(),
            'full_name' => fake()->name(),
            'contact_number' => fake()->numerify('09#########'),
            'email' => fake()->safeEmail(),
            'appointment_date' => $date,
            'appointment_time' => $time,
            'preferred_date' => $date,
            'preferred_time_window' => ((int) substr($time, 0, 2)) < 12 ? 'morning' : 'afternoon',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addMinutes(30),
            'confirmed_at' => $status === 'confirmed' ? now() : null,
            'service' => fake()->randomElement($services),
            'concern' => fake()->optional()->sentence(),
            'status' => $status,
        ];
    }
}
