<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PatientsView);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::PatientsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PatientsCreate);
    }

    public function updateDemographics(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::PatientsUpdateDemographics);
    }

    public function updateClinical(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::PatientsUpdateClinical);
    }

    public function changeStatus(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::PatientsChangeStatus);
    }

    public function viewClinical(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::PatientsViewClinical);
    }

    public function viewBilling(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::PatientsViewBilling);
    }
}
