@include('settings._partials._flash')

<div class="settings-tab-body">
    <div class="space-y-5">
        <div class="responsive-card responsive-card-padding">
            <h2 class="font-bold text-slate-800">Add FAQ</h2>
            <form action="{{ route('settings.faqs.store') }}" method="POST" data-ajax-form data-loading-text="Adding..." class="mt-4 grid gap-4">
                @csrf
                <input name="question" required placeholder="Question" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <textarea name="answer" rows="3" required placeholder="Answer" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></textarea>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="number" name="display_order" value="0" min="0" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-emerald-600"> Active
                    </label>
                    <button class="primary-action"><i class="fa-solid fa-plus"></i> Add FAQ</button>
                </div>
            </form>
        </div>
        <div class="space-y-3">
            @forelse($faqs as $faq)
                <div class="responsive-card responsive-card-padding">
                    <form action="{{ route('settings.faqs.update', $faq) }}" method="POST" data-ajax-form data-loading-text="Saving..." class="space-y-3">
                        @csrf
                        @method('PUT')
                        <input name="question" value="{{ $faq->question }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold">
                        <textarea name="answer" rows="3" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">{{ $faq->answer }}</textarea>
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="number" name="display_order" value="{{ $faq->display_order }}" min="0" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($faq->is_active) class="rounded border-slate-300 text-emerald-600"> Active
                            </label>
                            <button class="primary-action"><i class="fa-solid fa-check"></i> Save</button>
                        </div>
                    </form>
                    <form action="{{ route('settings.faqs.destroy', $faq) }}" method="POST" data-ajax-form data-loading-text="Removing..." class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex min-h-11 items-center rounded-xl bg-red-50 px-4 text-sm font-semibold text-red-600">Remove</button>
                    </form>
                </div>
            @empty
                <div class="responsive-card responsive-card-padding text-center text-sm text-slate-500">No FAQs yet.</div>
            @endforelse
        </div>
    </div>
</div>
