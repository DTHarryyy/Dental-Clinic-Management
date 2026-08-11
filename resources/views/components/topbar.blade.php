<header class="bg-white border-b border-slate-200 sticky top-0 z-10 print:hidden">
    <div class="px-6 lg:px-8 py-3.5 flex items-center justify-between gap-4">

        {{-- Search --}}
        <div class="flex-1 max-w-sm">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input
                    type="text"
                    placeholder="Search..."
                    class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition"
                />
            </div>
        </div>

        {{-- Right actions --}}
        <div class="flex items-center gap-3">

            {{-- Public booking link --}}
            <a
                href="/book-appointment"
                target="_blank"
                class="hidden sm:inline-flex items-center gap-2 text-xs font-semibold px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-100 transition"
            >
                <i class="fa-solid fa-clipboard-list"></i> <span>Patient Booking</span>
            </a>

            {{-- Notifications --}}
            <div class="relative" x-data="{ open: false }">
                <button
                    onclick="this.nextElementSibling.classList.toggle('hidden')"
                    class="h-9 w-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center transition relative"
                >
                    <i class="fa-solid fa-bell"></i>
                    <span class="absolute -top-1 -right-1 text-[9px] h-4 w-4 flex items-center justify-center rounded-full bg-red-500 text-white font-bold">3</span>
                </button>

                {{-- Dropdown --}}
                <div class="hidden absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-slate-200 shadow-lg z-20 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="font-semibold text-sm">Notifications</span>
                        <span class="text-xs text-emerald-600 font-medium cursor-pointer">Mark all read</span>
                    </div>
                    @php
                        $notifs = [
                            ['icon' => '<i class="fa-solid fa-calendar-days text-blue-500"></i>',          'text' => 'New appointment booked by Juan Dela Cruz', 'time' => '5 min ago'],
                            ['icon' => '<i class="fa-solid fa-credit-card text-violet-500"></i>',          'text' => 'Invoice #0042 is overdue', 'time' => '1 hr ago'],
                            ['icon' => '<i class="fa-solid fa-triangle-exclamation text-amber-500"></i>',  'text' => 'Low stock: Composite Resin Kit', 'time' => '3 hrs ago'],
                        ];
                    @endphp
                    <div class="divide-y divide-slate-100">
                        @foreach ($notifs as $n)
                            <div class="px-4 py-3 hover:bg-slate-50 flex items-start gap-3 cursor-pointer">
                                <span class="text-lg mt-0.5">{!! $n['icon'] !!}</span>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-700 leading-snug">{{ $n['text'] }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $n['time'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Profile --}}
            <div class="relative">
                <button
                    onclick="this.nextElementSibling.classList.toggle('hidden')"
                    class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition"
                >
                    <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700 text-sm">
                        {{ auth()->user() ? strtoupper(substr(auth()->user()->name, 0, 2)) : '?' }}
                    </div>
                    <div class="text-right hidden sm:block leading-tight">
                        <div class="font-semibold text-sm">{{ auth()->user()->name ?? 'Guest' }}</div>
                        <div class="text-[10px] text-slate-500">{{ ucfirst(auth()->user()->role ?? '') }}</div>
                    </div>
                    <svg class="h-3.5 w-3.5 text-slate-400 hidden sm:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                {{-- Dropdown --}}
                <div class="hidden absolute right-0 mt-2 w-44 bg-white rounded-2xl border border-slate-200 shadow-lg z-20 py-1 overflow-hidden">
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'profile-edit' } }))" class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 text-slate-700"><i class="fa-solid fa-user w-4 text-center"></i> My Profile</button>
                    <div class="border-t border-slate-100 pt-1">
                        <form action="{{ route('logout') }}" method="POST" data-turbo="false">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-red-50 text-red-600"><i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>
