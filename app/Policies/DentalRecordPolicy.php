<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\DentalRecord;
use App\Models\User;

class DentalRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::RecordsView);
    }

    public function view(User $user, DentalRecord $record): bool
    {
        return $user->hasPermission(Permission::RecordsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::RecordsCreate);
    }

    public function publish(User $user, DentalRecord $record): bool
    {
        if (! $user->hasPermission(Permission::TreatmentSummariesPublish)) {
            return false;
        }

        return $user->roleEnum() === Role::Admin || (int) $record->dentist_id === (int) $user->id;
    }
}
