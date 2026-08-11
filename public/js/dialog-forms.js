(function () {
    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    // "items.0.description" -> "items[0][description]" to match the bracket-notation
    // input names Laravel forms use for nested arrays.
    function dotToBracketName(key) {
        const parts = key.split('.');
        return parts.reduce((acc, part, i) => (i === 0 ? part : `${acc}[${part}]`), '');
    }

    function clearErrors(form) {
        form.querySelectorAll('.field-error').forEach((el) => el.remove());
        form.querySelectorAll('.field-invalid').forEach((el) => {
            el.classList.remove('field-invalid', 'border-red-400', 'ring-2', 'ring-red-100');
        });
        const summary = form.querySelector('[data-error-summary]');
        if (summary) {
            summary.classList.add('hidden');
            summary.innerHTML = '';
        }
    }

    function showSummary(form, messages) {
        let summary = form.querySelector('[data-error-summary]');
        if (!summary) {
            summary = document.createElement('div');
            summary.setAttribute('data-error-summary', '');
            form.prepend(summary);
        }
        summary.className = 'mb-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3 space-y-1';
        summary.innerHTML = messages.map((m) => `<div><i class="fa-solid fa-circle-exclamation mr-1.5"></i>${m}</div>`).join('');
    }

    // Returns true if the error was attached next to a field, false if no matching field was found.
    function applyFieldError(form, key, message) {
        const bracketName = dotToBracketName(key);
        let target = form.querySelector('[name="' + bracketName + '"]');
        let container = null;

        if (!target) {
            // Whole-array errors (e.g. checkbox groups like "conditions[]") - attach near the group.
            target = form.querySelector('[name="' + bracketName + '[]"]');
            if (target) {
                container = target.closest('[data-field-group]') || target.closest('div');
            }
        } else {
            target.classList.add('field-invalid', 'border-red-400', 'ring-2', 'ring-red-100');
            container = target.closest('[data-field-group]') || target.parentElement;
        }

        if (!container) return false;

        const p = document.createElement('p');
        p.className = 'field-error text-xs text-red-600 mt-1';
        p.textContent = message;
        container.appendChild(p);
        return true;
    }

    function setLoading(form, loading) {
        const btn = form.querySelector('[type="submit"]');
        if (!btn) return;
        if (loading) {
            form.setAttribute('aria-busy', 'true');
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + (btn.dataset.loadingText || 'Saving...');
        } else {
            form.removeAttribute('aria-busy');
            btn.disabled = false;
            btn.classList.remove('opacity-70', 'cursor-not-allowed');
            if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
        }
    }

    async function handleSubmit(e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-ajax-form]')) return;
        e.preventDefault();
        clearErrors(form);
        setLoading(form, true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            });

            if (response.status === 422) {
                const data = await response.json();
                const errors = data.errors || {};
                const unmatched = [];

                Object.keys(errors).forEach((key) => {
                    const placed = applyFieldError(form, key, errors[key][0]);
                    if (!placed) unmatched.push(errors[key][0]);
                });

                if (unmatched.length) showSummary(form, unmatched);

                const firstInvalid = form.querySelector('.field-invalid');
                if (firstInvalid) firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });

                setLoading(form, false);
                return;
            }

            if (!response.ok) {
                showSummary(form, ['Something went wrong. Please try again.']);
                setLoading(form, false);
                return;
            }

            // Success: the controller returns the intended redirect target as JSON instead of
            // an actual redirect, so the browser's own navigation (not fetch) is what "spends"
            // the one-shot session flash message - otherwise fetch would auto-follow a real
            // redirect itself and consume the flash before the user ever saw the destination page.
            const data = await response.json();
            if (window.Turbo && document.querySelector('meta[name="turbo-enabled"]')?.content === 'true') {
                window.Turbo.visit(data.redirect, { action: 'replace' });
            } else {
                window.location.href = data.redirect;
            }
        } catch (err) {
            showSummary(form, ['Network error. Please check your connection and try again.']);
            setLoading(form, false);
        }
    }

    document.addEventListener('submit', handleSubmit);

    // Openers that carry a payload (the dialog's prefill) declare it as JSON on the button
    // rather than inline JS, so Blade's normal escaping is all that stands between the two.
    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-open-dialog]');
        if (opener) {
            window.dispatchEvent(new CustomEvent('open-dialog', { detail: JSON.parse(opener.dataset.openDialog) }));
            return;
        }

        const canceller = e.target.closest('[data-open-cancel]');
        if (canceller) {
            window.dispatchEvent(new CustomEvent('open-appointment-cancel', { detail: JSON.parse(canceller.dataset.openCancel) }));
        }
    });

    // Shared dialogs are rendered once per page, so an opener that seeds them (e.g. completing
    // a specific appointment) has to hand over that row's data with the open event.
    function prefill(body, detail) {
        // Wipe the previous row's answers first - without this, notes typed for one
        // appointment would still be sitting there when the next one is opened.
        body.querySelectorAll('form[data-ajax-form]').forEach((form) => form.reset());

        Object.entries(detail.fields || {}).forEach(([name, value]) => {
            const input = body.querySelector('[name="' + name + '"]');
            if (input) input.value = value ?? '';
        });

        Object.entries(detail.text || {}).forEach(([key, value]) => {
            body.querySelectorAll('[data-fill-text="' + key + '"]').forEach((el) => {
                el.textContent = value ?? '';
            });
        });

        Object.entries(detail.actions || {}).forEach(([key, url]) => {
            const form = body.querySelector('[data-action-target="' + key + '"]');
            if (form) form.action = url;
        });

        // A select silently keeps its old value when asked for an option it doesn't have,
        // which happens when the booked service has since left the catalog.
        const procedure = body.querySelector('select[name="procedure"]');
        const hint = body.querySelector('[data-procedure-missing]');
        if (procedure && hint) {
            const booked = detail.fields?.procedure;
            hint.classList.toggle('hidden', !booked || procedure.value === booked);
        }
    }

    // Reset stale error state whenever a dialog is (re)opened.
    window.addEventListener('open-dialog', (e) => {
        const body = document.querySelector('[data-dialog-body="' + e.detail.id + '"]');
        if (!body) return;
        body.querySelectorAll('form[data-ajax-form]').forEach((form) => clearErrors(form));
        if (e.detail.fields || e.detail.text || e.detail.actions) prefill(body, e.detail);
    });
})();
