<?php

namespace App\Enums;

enum Permission: string
{
    case DashboardView = 'dashboard.view';
    case SearchUse = 'search.use';
    case ProfileView = 'profile.view';
    case ProfileUpdate = 'profile.update';
    case NotificationsManage = 'notifications.manage';

    case PatientsView = 'patients.view';
    case PatientsCreate = 'patients.create';
    case PatientsCreateExtendedDemographics = 'patients.create-extended-demographics';
    case PatientsSetInitialStatus = 'patients.set-initial-status';
    case PatientsUpdateDemographics = 'patients.update-demographics';
    case PatientsUpdateClinical = 'patients.update-clinical';
    case PatientsChangeStatus = 'patients.change-status';
    case PatientsViewClinical = 'patients.view-clinical';
    case PatientsViewBilling = 'patients.view-billing';

    case AppointmentsView = 'appointments.view';
    case AppointmentsCreate = 'appointments.create';
    case AppointmentsManage = 'appointments.manage';

    case RecordsView = 'records.view';
    case RecordsCreate = 'records.create';

    case BillingView = 'billing.view';
    case BillingManage = 'billing.manage';

    case ReportsView = 'reports.view';
    case ReportsExport = 'reports.export';

    case UsersView = 'users.view';
    case UsersManage = 'users.manage';

    case SettingsView = 'settings.view';
    case SettingsManage = 'settings.manage';
}
