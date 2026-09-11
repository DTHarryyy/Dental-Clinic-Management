<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientAccountLinkRequest;
use App\Models\User;

class PatientAccountLinker
{
    public function reconcile(User $user, array $profile = []): string
    {
        $email = self::normalizeEmail($user->email);
        $matches = Patient::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->orderBy('id')
            ->get();

        $active = $matches->where('status', 'active')->values();

        if ($matches->count() === 1 && $active->count() === 1) {
            $user->forceFill(['patient_id' => $active->first()->id])->save();
            PatientAccountLinkRequest::where('user_id', $user->id)->where('status', 'pending')->delete();

            return 'linked';
        }

        if ($matches->isEmpty()) {
            $patient = Patient::create([
                'first_name' => $profile['first_name'] ?? $this->firstNameFromUser($user),
                'last_name' => $profile['last_name'] ?? $this->lastNameFromUser($user),
                'mobile' => $profile['mobile'] ?? $user->phone,
                'email' => $email,
                'status' => 'active',
            ]);

            $user->forceFill(['patient_id' => $patient->id])->save();

            return 'created';
        }

        PatientAccountLinkRequest::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'pending'],
            [
                'normalized_email' => $email,
                'candidate_count' => $matches->count(),
            ],
        );

        return 'review';
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function firstNameFromUser(User $user): string
    {
        return preg_split('/\s+/', trim($user->name), 2)[0] ?: 'Patient';
    }

    private function lastNameFromUser(User $user): string
    {
        return preg_split('/\s+/', trim($user->name), 2)[1] ?? '';
    }
}
