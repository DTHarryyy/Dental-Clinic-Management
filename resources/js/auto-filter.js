(function () {
    // Auto-submitting filter bars: text inputs submit after a short pause,
    // selects/date/month submit immediately. Removes the need for a "Search" button.
    // Any <form data-auto-filter="key" method="GET"> opts in.

    const DEBOUNCE_MS = 450;
    const FOCUS_KEY = 'autoFilterFocus';
    const timers = new WeakMap();

    function isTextInput(el) {
        return el.matches(
            'form[data-auto-filter] input[type="text"],' +
            'form[data-auto-filter] input[type="search"],' +
            'form[data-auto-filter] input:not([type])'
        );
    }

    function isInstantInput(el) {
        return el.matches(
            'form[data-auto-filter] select,' +
            'form[data-auto-filter] input[type="date"],' +
            'form[data-auto-filter] input[type="month"],' +
            'form[data-auto-filter] input[type="number"]'
        );
    }

    // Remember which field (and caret position) triggered the reload so we can
    // restore it afterwards — a full-page GET would otherwise drop focus mid-typing.
    function rememberFocus(input) {
        try {
            sessionStorage.setItem(FOCUS_KEY, JSON.stringify({
                form: input.form ? input.form.getAttribute('data-auto-filter') : '',
                name: input.name || '',
                start: input.selectionStart,
                end: input.selectionEnd,
            }));
        } catch (e) { /* private mode / disabled storage — ignore */ }
    }

    function submitForm(input) {
        const form = input.form;
        if (!form) return;
        rememberFocus(input);
        const url = new URL(form.action || window.location.href, window.location.origin);
        const params = new URLSearchParams(new FormData(form));
        url.search = params.toString();

        if (window.Turbo && document.querySelector('meta[name="turbo-enabled"]')?.content === 'true') {
            window.Turbo.visit(url.toString(), { action: 'replace' });
        } else if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    function restoreFocus() {
        let data;
        try {
            data = JSON.parse(sessionStorage.getItem(FOCUS_KEY) || 'null');
            sessionStorage.removeItem(FOCUS_KEY);
        } catch (e) { return; }
        if (!data || !data.name) return;

        const safeName = (window.CSS && CSS.escape) ? CSS.escape(data.name) : data.name;
        const scoped = data.form
            ? document.querySelector('form[data-auto-filter="' + data.form + '"] [name="' + safeName + '"]')
            : null;
        const input = scoped || document.querySelector('[name="' + safeName + '"]');
        if (!input) return;

        input.focus();
        if (typeof data.start === 'number' && typeof input.setSelectionRange === 'function') {
            const len = input.value.length;
            try { input.setSelectionRange(Math.min(data.start, len), Math.min(data.end, len)); } catch (e) { /* unsupported type */ }
        }
    }

    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!isTextInput(el)) return;
        clearTimeout(timers.get(el));
        timers.set(el, setTimeout(function () { submitForm(el); }, DEBOUNCE_MS));
    });

    document.addEventListener('change', function (e) {
        const el = e.target;
        if (!isInstantInput(el)) return;
        clearTimeout(timers.get(el));
        submitForm(el);
    });

    // Enter flushes the debounce and submits right away.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        const el = e.target;
        if (!isTextInput(el)) return;
        e.preventDefault();
        clearTimeout(timers.get(el));
        submitForm(el);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreFocus);
    } else {
        restoreFocus();
    }
    document.addEventListener('turbo:load', restoreFocus);
})();
