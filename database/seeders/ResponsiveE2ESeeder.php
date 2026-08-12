<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
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
    }
}
