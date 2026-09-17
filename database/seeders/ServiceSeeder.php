<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ServiceSeeder extends Seeder
{
    /**
     * The default service catalog every clinic starts with. Admins can freely
     * edit/delete these in Settings — this is only a sensible starting point.
     */
    public const DEFAULTS = [
        ['name' => 'Check-up', 'price' => 800, 'duration' => '20 min', 'duration_minutes' => 20],
        ['name' => 'Dental Cleaning', 'price' => 1500, 'duration' => '45 min', 'duration_minutes' => 45],
        ['name' => 'Dental Filling', 'price' => 2000, 'duration' => '45 min', 'duration_minutes' => 45],
        ['name' => 'Tooth Extraction', 'price' => 2500, 'duration' => '30 min', 'duration_minutes' => 30],
        ['name' => 'Braces Adjustment', 'price' => 3500, 'duration' => '40 min', 'duration_minutes' => 40],
        ['name' => 'Teeth Whitening', 'price' => 6000, 'duration' => '60 min', 'duration_minutes' => 60],
        ['name' => 'Root Canal', 'price' => 8000, 'duration' => '90 min', 'duration_minutes' => 90],
        ['name' => 'Dental Crown', 'price' => 12000, 'duration' => '75 min', 'duration_minutes' => 75],
    ];

    /**
     * Idempotent: firstOrCreate keyed on name means re-running never duplicates.
     * Returns the full catalog so callers (DatabaseSeeder) can reuse it.
     */
    public function run(): Collection
    {
        return collect(self::DEFAULTS)->map(fn ($s) => Service::firstOrCreate(
            ['name' => $s['name']],
            ['price' => $s['price'], 'duration' => $s['duration'], 'duration_minutes' => $s['duration_minutes'], 'is_active' => true],
        ));
    }
}
