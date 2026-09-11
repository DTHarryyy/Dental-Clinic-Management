@php
    $tabs = [
        ['route' => 'settings.clinic',   'icon' => 'fa-hospital', 'label' => 'Clinic Info', 'desc' => 'Name, contact & address'],
        ['route' => 'settings.public',   'icon' => 'fa-globe',    'label' => 'Public Website', 'desc' => 'Landing page content'],
        ['route' => 'settings.hours',    'icon' => 'fa-clock',    'label' => 'Business Hours', 'desc' => 'Weekly schedule'],
        ['route' => 'settings.closures', 'icon' => 'fa-calendar-xmark', 'label' => 'Closure Dates', 'desc' => 'Holiday blocks'],
        ['route' => 'settings.services', 'icon' => 'fa-tooth',    'label' => 'Services',    'desc' => 'Catalog & pricing'],
        ['route' => 'settings.team',     'icon' => 'fa-user-doctor', 'label' => 'Team Profiles', 'desc' => 'Published dentists'],
        ['route' => 'settings.faqs',     'icon' => 'fa-circle-question', 'label' => 'FAQs', 'desc' => 'Public questions'],
    ];
@endphp

<nav class="bg-white rounded-2xl border border-slate-200 shadow-sm p-2.5 space-y-1 sticky top-20">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['route']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="flex items-start gap-3 px-3 py-2.5 rounded-xl border transition {{ $active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'text-slate-600 border-transparent hover:bg-slate-100 hover:text-slate-900' }}">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0 transition {{ $active ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 text-slate-500' }}">
                <i class="fa-solid {{ $tab['icon'] }} text-sm"></i>
            </span>
            <span class="min-w-0">
                <span class="block font-semibold text-sm leading-tight">{{ $tab['label'] }}</span>
                <span class="block text-xs mt-0.5 {{ $active ? 'text-emerald-600/80' : 'text-slate-400' }}">{{ $tab['desc'] }}</span>
            </span>
        </a>
    @endforeach
</nav>
