@php
    $toastMessage = null;
    $toastType = 'success';

    if (session('status')) {
        $toastMessage = session('status');
        $toastType = 'success';
    } elseif ($errors->any()) {
        $toastMessage = $errors->count() > 1
            ? 'Please fix ' . $errors->count() . ' errors in the form.'
            : $errors->first();
        $toastType = 'error';
    }
@endphp

@if ($toastMessage)
    <div id="app-toast" class="fixed inset-x-3 top-3 z-[60] sm:inset-x-auto sm:right-5 sm:top-5 sm:w-full sm:max-w-sm">
        <div class="toast-in flex items-start gap-3 rounded-2xl shadow-lg border p-4 {{ $toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800' }}" role="alert">
            <div class="mt-0.5 shrink-0">
                @if ($toastType === 'success')
                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                @else
                    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                @endif
            </div>
            <div class="flex-1 text-sm font-medium">{{ $toastMessage }}</div>
            <button onclick="document.getElementById('app-toast').remove()" class="shrink-0 text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>
    </div>

    <style>
        @keyframes toast-in-anim {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .toast-in { animation: toast-in-anim 0.25s ease-out; }
    </style>

    <script>
        setTimeout(() => {
            const toast = document.getElementById('app-toast');
            if (toast) {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-12px)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    </script>
@endif
