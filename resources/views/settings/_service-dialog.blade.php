{{-- Reusable create/edit dialog for a single service. Opened via window events:
     - open-service-create
     - open-service-edit  (detail: { id, name, price, duration }) --}}
<div
    x-data="{
        open: false,
        mode: 'create',
        id: null,
        form: { name: '', price: '', duration: '' },
        get action() {
            return this.mode === 'edit'
                ? '{{ url('settings/services') }}/' + this.id
                : '{{ route('settings.services.store') }}';
        },
        openCreate() {
            this.mode = 'create';
            this.id = null;
            this.form = { name: '', price: '', duration: '' };
            this.show();
        },
        openEdit(svc) {
            this.mode = 'edit';
            this.id = svc.id;
            this.form = { name: svc.name ?? '', price: svc.price ?? '', duration: svc.duration ?? '' };
            this.show();
        },
        show() {
            this.open = true;
            this.$nextTick(() => {
                this.$root.querySelectorAll('.field-error').forEach((el) => el.remove());
                this.$root.querySelectorAll('.field-invalid').forEach((el) => el.classList.remove('field-invalid', 'border-red-400', 'ring-2', 'ring-red-100'));
                this.$refs.nameInput?.focus();
            });
        },
    }"
    x-on:open-service-create.window="openCreate()"
    x-on:open-service-edit.window="openEdit($event.detail)"
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
         class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg flex flex-col overflow-hidden" x-on:click.stop>

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800" x-text="mode === 'edit' ? 'Edit Service' : 'Add Service'"></h2>
            <button type="button" x-on:click="open = false" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form :action="action" method="POST" data-ajax-form data-loading-text="Saving..." class="px-6 py-5" data-dialog-body="service-form">
            @csrf
            <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Service Name <span class="text-red-500">*</span></label>
                    <input x-ref="nameInput" type="text" name="name" x-model="form.name" placeholder="e.g. Teeth Whitening"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Price (₱) <span class="text-red-500">*</span></label>
                        <input type="number" name="price" x-model="form.price" step="0.01" min="0" placeholder="0.00"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Duration</label>
                        <input type="text" name="duration" x-model="form.duration" placeholder="e.g. 30 min"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-slate-100">
                <button type="button" x-on:click="open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    <i class="fa-solid fa-check mr-1"></i><span x-text="mode === 'edit' ? 'Save Changes' : 'Add Service'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
