@props(['variant' => 'sidebar'])

@php
    $user = auth()->user();
    $items = collect([
        ['route' => 'dashboard', 'label' => 'Dashboard', 'short' => 'Home', 'icon' => 'fa-gauge', 'permission' => \App\Enums\Permission::DashboardView, 'primary' => true],
        ['route' => 'patients.index', 'label' => 'Patients', 'short' => 'Patients', 'icon' => 'fa-users', 'permission' => \App\Enums\Permission::PatientsView, 'primary' => true],
        ['route' => 'appointments.index', 'label' => 'Appointments', 'short' => 'Schedule', 'icon' => 'fa-calendar-days', 'permission' => \App\Enums\Permission::AppointmentsView, 'primary' => true],
        ['route' => 'appointment-change-requests.index', 'label' => 'Change Requests', 'short' => 'Requests', 'icon' => 'fa-code-pull-request', 'permission' => \App\Enums\Permission::AppointmentChangeRequestsView, 'primary' => false],
        ['route' => 'patient-accounts.index', 'label' => 'Account Links', 'short' => 'Links', 'icon' => 'fa-user-check', 'permission' => \App\Enums\Permission::PatientAccountsView, 'primary' => false],
        ['route' => 'records.index', 'label' => 'Dental Records', 'short' => 'Records', 'icon' => 'fa-stethoscope', 'permission' => \App\Enums\Permission::RecordsView, 'primary' => true],
        ['route' => 'billing.index', 'label' => 'Billing', 'short' => 'Billing', 'icon' => 'fa-credit-card', 'permission' => \App\Enums\Permission::BillingView, 'primary' => true],
        ['route' => 'reports', 'label' => 'Reports', 'short' => 'Reports', 'icon' => 'fa-chart-bar', 'permission' => \App\Enums\Permission::ReportsView, 'primary' => false],
        ['route' => 'users.index', 'label' => 'Users & Roles', 'short' => 'Users', 'icon' => 'fa-user-gear', 'permission' => \App\Enums\Permission::UsersView, 'primary' => false],
        ['route' => 'settings.index', 'label' => 'Settings', 'short' => 'Settings', 'icon' => 'fa-gear', 'permission' => \App\Enums\Permission::SettingsView, 'primary' => false],
    ])->filter(fn ($item) => $user?->hasPermission($item['permission']));
@endphp

@if ($variant === 'bottom')
    @foreach ($items->where('primary', true)->take(4) as $item)
        @php $active = request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']); @endphp
        <a href="{{ route($item['route']) }}" class="mobile-nav-item {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
            <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['short'] }}</span>
        </a>
    @endforeach
@else
    @foreach ($items as $item)
        @php $active = request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']); @endphp
        <a href="{{ route($item['route']) }}" class="app-nav-item {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
            <span class="app-nav-icon"><i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i></span><span class="font-medium text-sm">{{ $item['label'] }}</span>
        </a>
    @endforeach
@endif
