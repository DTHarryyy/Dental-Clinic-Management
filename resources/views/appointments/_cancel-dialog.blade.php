{{-- Confirmation for cancelling an appointment. Opened via:
     open-appointment-cancel  (detail: { action, name, when })

     Cancel used to fire the moment you clicked it, from a button sitting right next to
     Complete. This makes the destructive move deliberate and names what it will hit. --}}
<div
    x-data="{
        open: false,
        action: '',
        name: '',
        when: '',
        openCancel(a) {
            this.action = a.action;
            this.name = a.name ?? 'this patient';
            this.when = a.when ?? '';
            this.open = true;
        },
    }"
    x-on:open-appointment-cancel.window="openCancel($event.detail)"
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
                <i class="fa-solid fa-calendar-xmark text-xl"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-800">Cancel this appointment?</h2>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                You’re about to cancel <span class="font-semibold text-slate-700" x-text="name"></span>’s appointment
                <span class="font-semibold text-slate-700" x-text="when"></span>.
                The slot frees up and the booking stays on file. You can reopen it afterwards if this was a mistake.
            </p>

            <form :action="action" method="POST" data-ajax-form data-loading-text="Cancelling..." class="flex items-center gap-3 mt-6">
                @csrf
                <input type="hidden" name="status" value="cancelled" />
                <button type="button" x-on:click="open = false" class="flex-1 px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                    Keep it
                </button>
                <button type="submit" class="flex-1 px-5 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white font-semibold text-sm transition shadow-sm">
                    Cancel appointment
                </button>
            </form>
        </div>
    </div>
</div>
