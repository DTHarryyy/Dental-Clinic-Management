<?php

namespace Tests\Concerns;

use App\Models\Patient;
use App\Models\User;

trait CreatesPatientAccounts
{
    /**
     * Creates an active Patient row and a verified, linked patient-role User sharing its
     * email — the fixture every patient-portal test needs to log in as a real patient.
     *
     * @return array{0: User, 1: Patient}
     */
    protected function linkedPatient(string $email, array $patientAttributes = [], array $userAttributes = []): array
    {
        $patient = Patient::factory()->create([
            ...$patientAttributes,
            'email' => $email,
            'status' => 'active',
        ]);

        $user = User::factory()->patient()->create([
            ...$userAttributes,
            'email' => $email,
            'patient_id' => $patient->id,
        ]);

        return [$user, $patient];
    }
}
