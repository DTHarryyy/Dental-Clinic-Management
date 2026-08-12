<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::AppointmentsView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::AppointmentsCreate);
    }

    public function view(User $user, Appointment $appointment): Response
    {
        return $this->canAccess($user, $appointment, Permission::AppointmentsView);
    }

    public function manage(User $user, Appointment $appointment): Response
    {
        return $this->canAccess($user, $appointment, Permission::AppointmentsManage);
    }

    private function canAccess(User $user, Appointment $appointment, Permission $permission): Response
    {
        if (! $user->hasPermission($permission)) {
            return Response::deny();
        }

        if ($user->roleEnum() === Role::Dentist && (int) $appointment->dentist_id !== (int) $user->id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
