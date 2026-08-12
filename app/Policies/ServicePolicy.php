<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::SettingsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::SettingsManage);
    }

    public function update(User $user, Service $service): bool
    {
        return $user->hasPermission(Permission::SettingsManage);
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasPermission(Permission::SettingsManage);
    }
}
