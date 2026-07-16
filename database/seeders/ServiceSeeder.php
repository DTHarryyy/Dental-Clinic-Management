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
        ['name' => 'Check-up', 'price' => 800, 'duration' => '20 min'],
        ['name' => 'Dental Cleaning', 'price' => 1500, 'duration' => '45 min'],
        ['name' => 'Dental Filling', 'price' => 2000, 'duration' => '45 min'],
        ['name' => 'Tooth Extraction', 'price' => 2500, 'duration' => '30 min'],
        ['name' => 'Braces Adjustment', 'price' => 3500, 'duration' => '40 min'],
        ['name' => 'Teeth Whitening', 'price' => 6000, 'duration' => '60 min'],
        ['name' => 'Root Canal', 'price' => 8000, 'duration' => '90 min'],
        ['name' => 'Dental Crown', 'price' => 12000, 'duration' => '75 min'],
    ];

    /**
     * Idempotent: firstOrCreate keyed on name means re-running never duplicates.
     * Returns the full catalog so callers (DatabaseSeeder) can reuse it.
     */
    public function run(): Collection
    {
        return collect(self::DEFAULTS)->map(fn ($s) => Service::firstOrCreate(
            ['name' => $s['name']],
            ['price' => $s['price'], 'duration' => $s['duration']],
        ));
    }
}
