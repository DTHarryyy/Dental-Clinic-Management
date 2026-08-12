<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\Role;

final class PermissionMatrix
{
    /** @var array<string, list<Role>> */
    private const GRANTS = [
        Permission::DashboardView->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::SearchUse->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::ProfileView->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::ProfileUpdate->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::NotificationsManage->value => [Role::Admin, Role::Dentist, Role::Receptionist],

        Permission::PatientsView->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::PatientsCreate->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::PatientsCreateExtendedDemographics->value => [Role::Admin, Role::Receptionist],
        Permission::PatientsSetInitialStatus->value => [Role::Admin],
        Permission::PatientsUpdateDemographics->value => [Role::Admin, Role::Receptionist],
        Permission::PatientsUpdateClinical->value => [Role::Admin, Role::Dentist],
        Permission::PatientsChangeStatus->value => [Role::Admin, Role::Receptionist],
        Permission::PatientsViewClinical->value => [Role::Admin, Role::Dentist],
        Permission::PatientsViewBilling->value => [Role::Admin, Role::Receptionist],

        Permission::AppointmentsView->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::AppointmentsCreate->value => [Role::Admin, Role::Dentist, Role::Receptionist],
        Permission::AppointmentsManage->value => [Role::Admin, Role::Dentist, Role::Receptionist],

        Permission::RecordsView->value => [Role::Admin, Role::Dentist],
        Permission::RecordsCreate->value => [Role::Admin, Role::Dentist],

        Permission::BillingView->value => [Role::Admin, Role::Receptionist],
        Permission::BillingManage->value => [Role::Admin, Role::Receptionist],

        Permission::ReportsView->value => [Role::Admin],
        Permission::ReportsExport->value => [Role::Admin],
        Permission::UsersView->value => [Role::Admin],
        Permission::UsersManage->value => [Role::Admin],
        Permission::SettingsView->value => [Role::Admin],
        Permission::SettingsManage->value => [Role::Admin],
    ];

    public static function allows(Role $role, Permission $permission): bool
    {
        return in_array($role, self::GRANTS[$permission->value] ?? [], true);
    }

    /**
     * The admin-facing summary is derived from the same grants policies use.
     *
     * @return list<array{module: string, permissions: list<Permission>, scope: array<string, string>, access: array<string, bool>}>
     */
    public static function displayRows(): array
    {
        $rows = [
            ['module' => 'Dashboard', 'permissions' => [Permission::DashboardView], 'scope' => ['admin' => 'Clinic-wide', 'dentist' => 'Own clinical', 'receptionist' => 'Operations & billing']],
            ['module' => 'Patients', 'permissions' => [Permission::PatientsView], 'scope' => ['admin' => 'Full', 'dentist' => 'Clinical', 'receptionist' => 'Demographics & billing']],
            ['module' => 'Appointments', 'permissions' => [Permission::AppointmentsView], 'scope' => ['admin' => 'Clinic-wide', 'dentist' => 'Assigned only', 'receptionist' => 'Clinic-wide']],
            ['module' => 'Dental Records', 'permissions' => [Permission::RecordsView], 'scope' => ['admin' => 'Full', 'dentist' => 'Read all; write own', 'receptionist' => 'None']],
            ['module' => 'Billing', 'permissions' => [Permission::BillingView], 'scope' => ['admin' => 'Full', 'dentist' => 'None', 'receptionist' => 'Full']],
            ['module' => 'Reports', 'permissions' => [Permission::ReportsView], 'scope' => ['admin' => 'Full', 'dentist' => 'None', 'receptionist' => 'None']],
            ['module' => 'Users', 'permissions' => [Permission::UsersView], 'scope' => ['admin' => 'Full', 'dentist' => 'None', 'receptionist' => 'None']],
            ['module' => 'Settings', 'permissions' => [Permission::SettingsView], 'scope' => ['admin' => 'Full', 'dentist' => 'None', 'receptionist' => 'None']],
        ];

        return collect($rows)->map(function (array $row): array {
            $row['access'] = collect(Role::cases())->mapWithKeys(fn (Role $role) => [
                $role->value => self::roleHasAny($role, $row['permissions']),
            ])->all();

            return $row;
        })->all();
    }

    public static function roleHasAny(Role $role, array $permissions): bool
    {
        return collect($permissions)->contains(fn (Permission $permission) => self::allows($role, $permission));
    }
}
