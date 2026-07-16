@props(['name', 'title' => null, 'maxWidth' => '2xl'])

@php
$maxWidthClass = [
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl',
    '4xl' => 'max-w-4xl',
    '5xl' => 'max-w-5xl',
][$maxWidth] ?? 'max-w-2xl';
@endphp

<div
    x-data="{ open: false }"
    x-on:open-dialog.window="if ($event.detail.id === '{{ $name }}') { open = true; $nextTick(() => $el.querySelector('input, select, textarea')?.focus()); }"
    x-on:keydown.escape.window="if (open) open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-40 flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-slate-900/50"
        x-on:click="open = false"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-white rounded-2xl shadow-xl w-full {{ $maxWidthClass }} max-h-[90vh] flex flex-col"
        x-on:click.stop
    >
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
            <h2 class="text-lg font-bold text-slate-800">{{ $title }}</h2>
            <button type="button" x-on:click="open = false" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="overflow-y-auto px-6 py-5" data-dialog-body="{{ $name }}">
            {{ $slot }}
        </div>
    </div>
</div>
