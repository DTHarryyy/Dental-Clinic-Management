<div data-global-search data-search-url="{{ route('search.index') }}" class="min-w-0 flex-1 sm:max-w-md">
    <div class="relative hidden sm:block">
        <label for="global-search-desktop" class="sr-only">Search clinic records</label>
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input id="global-search-desktop" type="search" autocomplete="off" placeholder="Search patients, appointments…" data-global-search-input data-search-mode="desktop" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="global-search-results-desktop" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-14 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <kbd class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400">⌘K</kbd>
        <div data-global-search-panel data-search-mode="desktop" class="absolute left-0 right-0 top-full z-30 mt-2 hidden max-h-[min(32rem,70vh)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
            <div id="global-search-results-desktop" data-global-search-results role="listbox"></div>
        </div>
    </div>

    <button type="button" data-global-search-open class="touch-target rounded-xl border border-slate-200 text-slate-600 sm:hidden" aria-label="Search clinic records"><i class="fa-solid fa-magnifying-glass"></i></button>

    <div data-global-search-mobile class="fixed inset-0 z-[70] hidden bg-slate-50 sm:hidden" role="dialog" aria-modal="true" aria-label="Search clinic records">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-white px-3 py-2.5">
            <button type="button" data-global-search-close class="touch-target shrink-0 rounded-xl text-slate-600" aria-label="Close search"><i class="fa-solid fa-arrow-left"></i></button>
            <div class="relative min-w-0 flex-1">
                <label for="global-search-mobile-input" class="sr-only">Search clinic records</label>
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input id="global-search-mobile-input" type="search" autocomplete="off" placeholder="Search clinic records…" data-global-search-input data-search-mode="mobile" role="combobox" aria-autocomplete="list" aria-expanded="true" aria-controls="global-search-results-mobile" class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-base focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
            </div>
        </div>
        <div class="h-[calc(100dvh-4rem)] overflow-y-auto px-3 pb-24 pt-3">
            <div id="global-search-results-mobile" data-global-search-results role="listbox" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"></div>
        </div>
    </div>
    <p data-global-search-status class="sr-only" aria-live="polite"></p>
</div>
