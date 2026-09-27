import { createFragmentCache, prefetchOnIntent, SPINNER_DELAY_MS } from './fragment-cache';

// Invoice details dialog on /billing. Bundled (not a page <script>) so its document-level
// listeners are bound exactly once — a body <script> re-runs on every Turbo visit and
// stacked a duplicate set of listeners each time.
const loading = `
    <div class="flex min-h-64 items-center justify-center p-8 text-slate-500">
        <div class="text-center"><i class="fa-solid fa-circle-notch fa-spin text-2xl text-emerald-500"></i><p class="mt-3 text-sm">Loading invoice details…</p></div>
    </div>`;

const invoices = createFragmentCache();
let currentUrl = null;

function errorState(url) {
    return `<div class="flex min-h-64 items-center justify-center p-8"><div class="text-center"><i class="fa-solid fa-triangle-exclamation text-2xl text-red-500"></i><p class="mt-3 text-sm font-semibold text-slate-700">Invoice details could not be loaded.</p><button type="button" data-retry-invoice="${url}" class="mt-4 rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Try again</button></div></div>`;
}

async function loadInvoice(url, { open = true } = {}) {
    const body = document.querySelector('[data-invoice-details-body]');
    if (!body) return;

    currentUrl = url;
    const cached = invoices.get(url);

    if (open) window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'invoice-details' } }));

    if (cached !== undefined) {
        // Paint instantly from the hover prefetch, then revalidate quietly.
        body.innerHTML = cached;
        try {
            const fresh = await invoices.fetch(url);
            if (fresh !== cached && currentUrl === url) body.innerHTML = fresh;
        } catch {
            // Cached content stays on screen.
        }
        return;
    }

    // Keep the previous invoice from flashing; only show the spinner if the response
    // loses the race against a short delay.
    body.innerHTML = '';
    const spinner = setTimeout(() => {
        if (currentUrl === url) body.innerHTML = loading;
    }, SPINNER_DELAY_MS);

    try {
        const html = await invoices.fetch(url);
        clearTimeout(spinner);
        if (currentUrl === url) body.innerHTML = html;
    } catch {
        clearTimeout(spinner);
        if (currentUrl === url) body.innerHTML = errorState(url);
    }
}

prefetchOnIntent('[data-invoice-details-url]', (el) => el.dataset.invoiceDetailsUrl, invoices);

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-invoice-details-url]');
    if (opener) {
        loadInvoice(opener.dataset.invoiceDetailsUrl);
        return;
    }

    const retry = event.target.closest('[data-retry-invoice]');
    if (retry) loadInvoice(retry.dataset.retryInvoice);
});

// dialog-forms.js fires this after a successful save made from inside the details dialog
// (e.g. recording a payment): re-render the open invoice in place so the dialog stays up.
window.addEventListener('invoice-details-refresh', () => {
    if (!currentUrl) return;
    invoices.invalidate(currentUrl);
    loadInvoice(currentUrl, { open: false });
});

// The record-payment form is injected via fetch + innerHTML, so its own
// <script> tags would never execute — wire the reference-required toggle
// here instead, delegated on the document.
function syncRecordPaymentReference() {
    const form = document.getElementById('record-payment-form');
    if (!form) return;
    const select = form.querySelector('#invoice-payment-method');
    const reference = form.querySelector('#invoice-payment-reference');
    const label = form.querySelector('#invoice-payment-reference-label');
    if (!select || !reference || !label) return;

    const option = select.selectedOptions[0];
    const required = option?.dataset.requiresReference === '1';
    reference.required = required;
    label.innerHTML = required
        ? 'Reference <span class="font-normal text-red-500">(required)</span>'
        : 'Reference <span class="font-normal text-slate-400">(optional)</span>';
}

document.addEventListener('change', (event) => {
    if (event.target.closest('#invoice-payment-method')) syncRecordPaymentReference();
});
