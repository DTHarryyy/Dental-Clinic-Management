@php $role = auth()->user()->role ?? 'receptionist'; @endphp
<aside class="hidden lg:flex h-full min-h-0 w-64 shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white print:hidden" data-app-sidebar>

    {{-- Brand --}}
    <div class="px-5 py-4 border-b border-slate-200">
        <a href="/dashboard" class="flex items-center gap-3">
            <img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-10 w-10 rounded-2xl object-contain shadow-sm" />
            <div>
                <div class="font-bold leading-tight text-slate-800">DentalCare</div>
                <div class="text-xs text-slate-500">Management System</div>
            </div>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="min-h-0 flex-1 space-y-0.5 overflow-y-auto overscroll-contain px-3 py-4">

        <p class="px-3 pt-1 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Navigation</p>
        <x-navigation />

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
            <div class="ml-auto">
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'logout-confirm' } }))" class="touch-target rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" title="Logout" aria-label="Log out">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </div>
        </div>
    </div>
</aside>
