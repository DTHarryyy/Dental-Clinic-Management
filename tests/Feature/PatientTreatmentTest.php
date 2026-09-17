<?php

namespace Tests\Feature;

use App\Models\DentalRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PatientTreatmentTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_an_unpublished_record_is_not_found(): void
    {
        [$user, $patient] = $this->linkedPatient('unpublished@example.test');
        $record = DentalRecord::factory()->create(['patient_id' => $patient->id, 'published_at' => null]);

        $this->actingAs($user)->get(route('patient.treatments.show', $record))->assertNotFound();
    }

    public function test_the_index_only_lists_published_records(): void
    {
        [$user, $patient] = $this->linkedPatient('index-treatments@example.test');
        $published = DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'procedure' => 'Published Cleaning',
            'published_at' => now(),
        ]);
        DentalRecord::factory()->create([
            'patient_id' => $patient->id,
            'procedure' => 'Unpublished Filling',
            'published_at' => null,
        ]);

        $this->actingAs($user)->get(route('patient.treatments.index'))
            ->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 1 && $records->first()->is($published));
    }
}
