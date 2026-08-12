<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ClinicSetting;
use App\Models\User;

class ClinicSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::SettingsView);
    }

    public function update(User $user, ClinicSetting $setting): bool
    {
        return $user->hasPermission(Permission::SettingsManage);
    }
}
