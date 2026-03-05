<header class="bg-white border-b">
    <div class="px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
        {{-- Search --}}
        <div class="flex-1 max-w-xl">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
                <input
                    type="text"
                    placeholder="Search patients, appointments..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                />
            </div>
        </div>

        {{-- Right actions --}}
        <div class="flex items-center gap-4">
            {{-- Bell --}}
            <div class="relative">
                <button class="h-10 w-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center">
                    🔔
                </button>
                <span class="absolute -top-1 -right-1 text-[10px] px-1.5 py-0.5 rounded-full bg-red-500 text-white font-bold">
                    3
                </span>
            </div>

            {{-- Profile --}}
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block leading-tight">
                    <div class="font-semibold">Michaela</div>
                </div>
                <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700">
                    M
                </div>
            </div>
        </div>
    </div>
</header>