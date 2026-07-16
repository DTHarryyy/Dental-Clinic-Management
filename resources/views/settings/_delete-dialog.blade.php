{{-- Styled confirmation dialog for deleting a service. Opened via:
     open-service-delete  (detail: { id, name }) --}}
<div
    x-data="{
        open: false,
        id: null,
        name: '',
        get action() { return '{{ url('settings/services') }}/' + this.id; },
        openDelete(svc) {
            this.id = svc.id;
            this.name = svc.name ?? '';
            this.open = true;
        },
    }"
    x-on:open-service-delete.window="openDelete($event.detail)"
    x-on:keydown.escape.window="if (open) open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-40 flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-slate-900/50" x-on:click="open = false"></div>

    {{-- Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" x-on:click.stop>

        <div class="p-6 text-center">
            <div class="h-14 w-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 mx-auto mb-4">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-800">Delete this service?</h2>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                You’re about to remove <span class="font-semibold text-slate-700" x-text="'“' + name + '”'"></span>.
                Existing appointments and records keep their label, but staff can no longer pick it for new bookings.
            </p>

            <form :action="action" method="POST" data-ajax-form data-loading-text="Deleting..." class="flex items-center gap-3 mt-6">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
                <button type="button" x-on:click="open = false" class="flex-1 px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-5 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white font-semibold text-sm transition shadow-sm">
                    <i class="fa-solid fa-trash mr-1"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>
