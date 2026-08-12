@props(['variant' => 'sidebar'])

@php
    $role = auth()->user()->role ?? 'receptionist';
    $items = collect([
        ['route' => 'dashboard', 'label' => 'Dashboard', 'short' => 'Home', 'icon' => 'fa-gauge', 'roles' => ['admin', 'dentist', 'receptionist'], 'primary' => true],
        ['route' => 'patients.index', 'label' => 'Patients', 'short' => 'Patients', 'icon' => 'fa-users', 'roles' => ['admin', 'dentist', 'receptionist'], 'primary' => true],
        ['route' => 'appointments.index', 'label' => 'Appointments', 'short' => 'Schedule', 'icon' => 'fa-calendar-days', 'roles' => ['admin', 'dentist', 'receptionist'], 'primary' => true],
        ['route' => 'records.index', 'label' => 'Dental Records', 'short' => 'Records', 'icon' => 'fa-stethoscope', 'roles' => ['admin', 'dentist'], 'primary' => true],
        ['route' => 'billing.index', 'label' => 'Billing', 'short' => 'Billing', 'icon' => 'fa-credit-card', 'roles' => ['admin', 'receptionist'], 'primary' => true],
        ['route' => 'reports', 'label' => 'Reports', 'short' => 'Reports', 'icon' => 'fa-chart-bar', 'roles' => ['admin'], 'primary' => false],
        ['route' => 'users.index', 'label' => 'Users & Roles', 'short' => 'Users', 'icon' => 'fa-user-gear', 'roles' => ['admin'], 'primary' => false],
        ['route' => 'settings.index', 'label' => 'Settings', 'short' => 'Settings', 'icon' => 'fa-gear', 'roles' => ['admin'], 'primary' => false],
    ])->filter(fn ($item) => in_array($role, $item['roles'], true));
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
