// Client-side twin of components/toast.blade.php, for feedback that shouldn't wait for a
// page render (a dialog save shows its toast the moment the POST returns). Same markup and
// the same #app-toast id, so a newer toast replaces an older one from either source.
const STYLES = {
    success: { box: 'bg-emerald-50 border-emerald-200 text-emerald-800', icon: 'fa-circle-check text-emerald-500' },
    error: { box: 'bg-red-50 border-red-200 text-red-800', icon: 'fa-circle-exclamation text-red-500' },
};

let dismissTimer = null;

function dismiss(el) {
    el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-12px)';
    setTimeout(() => el.remove(), 300);
}

export function toast(message, type = 'success') {
    if (!message) return;
    const style = STYLES[type] ?? STYLES.success;

    document.getElementById('app-toast')?.remove();
    clearTimeout(dismissTimer);

    const wrapper = document.createElement('div');
    wrapper.id = 'app-toast';
    // Permanent so the background morph refresh that follows a save (whose server HTML
    // has no toast) doesn't remove it mid-display.
    wrapper.setAttribute('data-turbo-permanent', '');
    wrapper.className = 'fixed inset-x-3 top-3 z-[60] sm:inset-x-auto sm:right-5 sm:top-5 sm:w-full sm:max-w-sm';
    wrapper.innerHTML = `
        <div class="toast-in flex items-start gap-3 rounded-2xl shadow-lg border p-4 ${style.box}" role="alert">
            <div class="mt-0.5 shrink-0"><i class="fa-solid ${style.icon}"></i></div>
            <div class="flex-1 text-sm font-medium" data-toast-message></div>
            <button type="button" class="shrink-0 text-slate-400 hover:text-slate-600 transition" aria-label="Dismiss">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>`;
    wrapper.querySelector('[data-toast-message]').textContent = message;
    wrapper.querySelector('button').addEventListener('click', () => wrapper.remove());

    document.body.appendChild(wrapper);
    dismissTimer = setTimeout(() => dismiss(wrapper), 4000);
}

window.toast = toast;
