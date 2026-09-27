// Shared fetch cache for HTML fragments loaded into dialogs (invoice details, patient
// detail). Same idea as settings-popover.js: prefetch on intent (hover/focus/pointerdown),
// dedupe in-flight requests, paint from cache instantly and revalidate in the background.
//
// Entries are dropped on every Turbo visit: a visit is either a navigation (different
// page, different rows) or the background refresh that follows a dialog save — either
// way the underlying data may have changed, so the next open fetches fresh.

export const SPINNER_DELAY_MS = 150; // matches Turbo.setProgressBarDelay(150) in app.js

export function createFragmentCache() {
    const cache = new Map();
    const inFlight = new Map();

    function fetchFragment(url) {
        if (inFlight.has(url)) return inFlight.get(url);

        const request = fetch(url, {
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) throw new Error(`Unable to load ${url}`);
                return response.text();
            })
            .then((html) => {
                cache.set(url, html);
                return html;
            })
            .finally(() => inFlight.delete(url));

        inFlight.set(url, request);
        return request;
    }

    function prefetch(url) {
        if (!url || cache.has(url) || inFlight.has(url)) return;
        fetchFragment(url).catch(() => {});
    }

    function clear() {
        cache.clear();
    }

    document.addEventListener('turbo:visit', clear);

    return {
        get: (url) => cache.get(url),
        fetch: fetchFragment,
        prefetch,
        invalidate: (url) => cache.delete(url),
        clear,
    };
}

// Warm `cache` for any element matching `selector` the moment the user shows intent.
// pointerenter/focus don't bubble, so this listens in the capture phase.
export function prefetchOnIntent(selector, urlOf, cache) {
    ['pointerenter', 'focus', 'pointerdown'].forEach((type) => {
        document.addEventListener(type, (event) => {
            const el = event.target.closest?.(selector);
            if (el) cache.prefetch(urlOf(el));
        }, { capture: true, passive: true });
    });
}
