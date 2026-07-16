<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'dob' => fake()->dateTimeBetween('-80 years', '-5 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'occupation' => fake()->jobTitle(),
            'mobile' => fake()->numerify('09#########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_number' => fake()->numerify('09#########'),
            'allergies' => fake()->randomElement([null, 'None', 'Penicillin', 'Latex', 'Aspirin']),
            'medications' => fake()->randomElement([null, 'None', 'Maintenance meds']),
            'conditions' => fake()->randomElements(
                ['Diabetes', 'Hypertension', 'Heart Disease', 'Asthma', 'Bleeding disorder'],
                fake()->numberBetween(0, 2)
            ),
            'notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive']),
        ];
    }
}
