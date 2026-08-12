@props(['name', 'title' => null, 'maxWidth' => '2xl', 'bodyClass' => 'overflow-y-auto px-6 py-5'])

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
    x-bind:class="open ? '' : 'pointer-events-none'"
    x-bind:aria-hidden="open ? 'false' : 'true'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dialog-title-{{ $name }}"
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
        class="relative flex max-h-[100dvh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:max-h-[90dvh] sm:rounded-2xl {{ $maxWidthClass }}"
        x-on:click.stop
    >
        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-6 sm:py-4">
            <h2 id="dialog-title-{{ $name }}" class="text-lg font-bold text-slate-800">{{ $title }}</h2>
            <button type="button" x-on:click="open = false" class="touch-target rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition" aria-label="Close dialog">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="{{ $bodyClass }}" data-dialog-body="{{ $name }}">
            {{ $slot }}
        </div>
    </div>
</div>
