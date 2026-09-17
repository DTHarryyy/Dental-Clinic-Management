@php
    $tabs = [
        ['route' => 'settings.clinic',   'icon' => 'fa-hospital', 'label' => 'Clinic Info', 'desc' => 'Name, contact & address'],
        ['route' => 'settings.public',   'icon' => 'fa-globe',    'label' => 'Public Website', 'desc' => 'Landing page content'],
        ['route' => 'settings.hours',    'icon' => 'fa-clock',    'label' => 'Business Hours', 'desc' => 'Weekly schedule'],
        ['route' => 'settings.closures', 'icon' => 'fa-calendar-xmark', 'label' => 'Closure Dates', 'desc' => 'Holiday blocks'],
        ['route' => 'settings.services', 'icon' => 'fa-tooth',    'label' => 'Services',    'desc' => 'Catalog & pricing'],
        ['route' => 'settings.payment-channels', 'icon' => 'fa-qrcode', 'label' => 'Payment Channels', 'desc' => 'GCash, Maya & bank details'],
        ['route' => 'settings.team',     'icon' => 'fa-user-doctor', 'label' => 'Team Profiles', 'desc' => 'Published dentists'],
        ['route' => 'settings.faqs',     'icon' => 'fa-circle-question', 'label' => 'FAQs', 'desc' => 'Public questions'],
    ];
@endphp

{{-- Rail nav for the settings dialog. Active state is a single `is-active` class so
     settings-popover.js can re-apply it on tab switch without rewriting class lists —
     the rail renders once and stays put while only the right pane swaps. --}}
<nav class="settings-nav">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}" data-turbo="false"
           class="settings-nav-item {{ request()->routeIs($tab['route']) ? 'is-active' : '' }}">
            <span class="settings-nav-icon"><i class="fa-solid {{ $tab['icon'] }}"></i></span>
            <span class="min-w-0">
                <span class="settings-nav-label">{{ $tab['label'] }}</span>
                <span class="settings-nav-desc">{{ $tab['desc'] }}</span>
            </span>
        </a>
    @endforeach
</nav>
