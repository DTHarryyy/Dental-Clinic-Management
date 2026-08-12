(function () {
    const loading = `
        <div class="flex min-h-64 items-center justify-center p-8 text-slate-500">
            <div class="text-center"><i class="fa-solid fa-circle-notch fa-spin text-2xl text-emerald-500"></i><p class="mt-3 text-sm">Loading invoice details…</p></div>
        </div>`;

    async function loadInvoice(url) {
        const body = document.querySelector('[data-invoice-details-body]');
        if (!body) return;

        body.innerHTML = loading;
        window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'invoice-details' } }));

        try {
            const response = await fetch(url, {
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Unable to load invoice');
            body.innerHTML = await response.text();
        } catch (error) {
            body.innerHTML = `<div class="flex min-h-64 items-center justify-center p-8"><div class="text-center"><i class="fa-solid fa-triangle-exclamation text-2xl text-red-500"></i><p class="mt-3 text-sm font-semibold text-slate-700">Invoice details could not be loaded.</p><button type="button" data-retry-invoice="${url}" class="mt-4 rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Try again</button></div></div>`;
        }
    }

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-invoice-details-url]');
        if (opener) {
            loadInvoice(opener.dataset.invoiceDetailsUrl);
            return;
        }

        const retry = event.target.closest('[data-retry-invoice]');
        if (retry) {
            loadInvoice(retry.dataset.retryInvoice);
            return;
        }

    });
})();
