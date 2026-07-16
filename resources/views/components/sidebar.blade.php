@php
    $segment = request()->segment(1); // first URL segment
    $role = auth()->user()->role ?? 'receptionist';

    $navItem = function (string $href, string $label, string $icon, string $matchSegment) use ($segment) {
        $active = ($segment === $matchSegment);
        $cls = $active
            ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
        return "<a href=\"/{$href}\" class=\"flex items-center gap-3 px-3 py-2.5 rounded-xl transition {$cls}\">
                    <span class=\"text-lg leading-none\">{$icon}</span>
                    <span class=\"font-medium text-sm\">{$label}</span>
                </a>";
    };
@endphp

<aside class="w-64 bg-white border-r border-slate-200 min-h-screen flex flex-col shrink-0">

    {{-- Brand --}}
    <div class="px-5 py-4 border-b border-slate-200">
        <a href="/dashboard" class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">
                <i class="fa-solid fa-tooth text-lg"></i>
            </div>
            <div>
                <div class="font-bold leading-tight text-slate-800">DentalCare</div>
                <div class="text-xs text-slate-500">Management System</div>
            </div>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="px-3 py-4 space-y-0.5 flex-1 overflow-y-auto">

        <p class="px-3 pt-1 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Main</p>

        {!! $navItem('dashboard',    'Dashboard',        '<i class="fa-solid fa-gauge"></i>',         'dashboard') !!}
        {!! $navItem('patients',     'Patients',         '<i class="fa-solid fa-users"></i>',         'patients') !!}
        {!! $navItem('appointments', 'Appointments',     '<i class="fa-solid fa-calendar-days"></i>', 'appointments') !!}
        @if (in_array($role, ['admin', 'dentist']))
            {!! $navItem('records', 'Dental Records', '<i class="fa-solid fa-stethoscope"></i>', 'records') !!}
        @endif
        @if (in_array($role, ['admin', 'receptionist']))
            {!! $navItem('billing', 'Billing', '<i class="fa-solid fa-credit-card"></i>', 'billing') !!}
        @endif

        @if ($role === 'admin')
            <p class="px-3 pt-4 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Analytics</p>
            {!! $navItem('reports', 'Reports', '<i class="fa-solid fa-chart-bar"></i>', 'reports') !!}

            <p class="px-3 pt-4 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">System</p>
            {!! $navItem('users',    'Users & Roles', '<i class="fa-solid fa-user-gear"></i>', 'users') !!}
            {!! $navItem('settings', 'Settings',      '<i class="fa-solid fa-gear"></i>',      'settings') !!}
        @endif

    </nav>

    {{-- Divider + User card --}}
    <div class="border-t border-slate-200 p-4">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700 text-sm shrink-0">
                {{ auth()->user() ? strtoupper(substr(auth()->user()->name, 0, 2)) : '?' }}
            </div>
            <div class="leading-tight min-w-0">
                <div class="font-semibold text-sm truncate">{{ auth()->user()->name ?? 'Guest' }}</div>
                <div class="text-xs text-slate-500">{{ ucfirst($role) }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="ml-auto">
                @csrf
                <button type="submit" class="text-slate-400 hover:text-slate-600 transition" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
