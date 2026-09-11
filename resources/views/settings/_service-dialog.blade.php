{{-- Reusable create/edit dialog for a single service. Opened via window events:
     - open-service-create
     - open-service-edit  (detail: { id, name, price, duration }) --}}
<div
    x-data="{
        open: false,
        mode: 'create',
        id: null,
        form: { name: '', price: '', duration_minutes: 30, public_description: '', public_image_path: '', public_sort_order: 0, show_public_price: true, is_active: true },
        get action() {
            return this.mode === 'edit'
                ? '{{ url('settings/services') }}/' + this.id
                : '{{ route('settings.services.store') }}';
        },
        openCreate() {
            this.mode = 'create';
            this.id = null;
            this.form = { name: '', price: '', duration_minutes: 30, public_description: '', public_image_path: '', public_sort_order: 0, show_public_price: true, is_active: true };
            this.show();
        },
        openEdit(svc) {
            this.mode = 'edit';
            this.id = svc.id;
            this.form = {
                name: svc.name ?? '',
                price: svc.price ?? '',
                duration_minutes: svc.duration_minutes ?? 30,
                public_description: svc.public_description ?? '',
                public_image_path: svc.public_image_path ?? '',
                public_sort_order: svc.public_sort_order ?? 0,
                show_public_price: !!svc.show_public_price,
                is_active: !!svc.is_active,
            };
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

        <form :action="action" method="POST" enctype="multipart/form-data" data-ajax-form data-loading-text="Saving..." class="px-6 py-5" data-dialog-body="service-form">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Duration (minutes)</label>
                        <input type="number" min="1" max="480" name="duration_minutes" x-model="form.duration_minutes" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Public Description</label>
                    <textarea name="public_description" x-model="form.public_description" rows="3" class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"></textarea>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Public Image</label>
                    <template x-if="form.public_image_path">
                        <div class="mb-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <img :src="'{{ asset('storage') }}/' + form.public_image_path" alt="Current service image" class="h-32 w-full object-cover">
                        </div>
                    </template>
                    <input type="file" name="public_image" accept="image/jpeg,image/png,image/webp"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700">
                    <label x-show="form.public_image_path" class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                        <input type="checkbox" name="remove_public_image" value="1" class="rounded border-slate-300 text-emerald-600"> Remove current image
                    </label>
                    <p class="mt-2 text-xs text-slate-500">JPEG, PNG, or WebP up to 2 MB. Images appear on the public services grid.</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Public Order</label>
                        <input type="number" min="0" max="999" name="public_sort_order" x-model="form.public_sort_order" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                    </div>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-600">
                        <input type="hidden" name="show_public_price" value="0">
                        <input type="checkbox" name="show_public_price" value="1" x-model="form.show_public_price" class="rounded border-slate-300 text-emerald-600"> Show price
                    </label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-600">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-slate-300 text-emerald-600"> Active
                    </label>
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
