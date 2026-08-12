<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::UsersView);
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersView);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::UsersManage);
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersManage);
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::UsersManage);
    }
}
