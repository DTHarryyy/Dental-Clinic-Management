(function () {
    const SETTINGS_PATH_RE = /^\/settings(\/|$)/;
    const DEFAULT_TAB_URL = '/settings/clinic';
    const SPINNER_DELAY_MS = 150; // matches Turbo.setProgressBarDelay(150) in app.js

    const loading = `
        <div class="flex min-h-64 items-center justify-center p-8 text-slate-400">
            <div class="text-center"><i class="fa-solid fa-circle-notch fa-spin text-2xl text-emerald-500"></i><p class="mt-3 text-sm">Loading settings…</p></div>
        </div>`;

    // pathname -> rendered tab HTML. A tab switch that hits this cache paints
    // synchronously with no spinner and no request — that's what makes it feel instant.
    // Cleared entirely on any settings save (see settings-popover-refresh below), so a
    // save is never followed by a switch-away-and-back showing the pre-save HTML.
    const tabCache = new Map();
    // pathname -> in-flight fetch promise, so hovering across the rail (which prefetches)
    // can't fire the same request twice, and a prefetch in flight when the user actually
    // clicks is reused instead of duplicated.
    const inFlight = new Map();
    // The pathname of whichever tab is currently on screen. A background revalidation
    // fetch started for tab A can resolve after the user has already switched to tab B —
    // the single popover body element never gets torn down between switches, so a plain
    // "is the body element still the same" check doesn't catch that. Guarding on this too
    // means a slow, now-irrelevant revalidation only updates tabCache, never the DOM.
    let currentTabKey = null;

    function popoverBody() {
        return document.querySelector('[data-settings-popover-body]');
    }

    function isSettingsPath(pathname) {
        return SETTINGS_PATH_RE.test(pathname);
    }

    function pathnameOf(url) {
        return new URL(url, window.location.origin).pathname;
    }

    // The rail renders once and stays put while only the right pane swaps, so its
    // server-rendered active state (which reflects whatever page is underneath) has to
    // be re-applied here on every tab change.
    function syncActiveTab(url) {
        const target = pathnameOf(url);
        document.querySelectorAll('.settings-nav-item').forEach((link) => {
            const linkPath = pathnameOf(link.getAttribute('href'));
            link.classList.toggle('is-active', linkPath === target);
        });
    }

    function fetchTab(url) {
        const key = pathnameOf(url);
        if (inFlight.has(key)) return inFlight.get(key);

        const request = fetch(url, {
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) throw new Error('Unable to load settings');
                return response.text();
            })
            .then((html) => {
                tabCache.set(key, html);
                return html;
            })
            .finally(() => inFlight.delete(key));

        inFlight.set(key, request);
        return request;
    }

    // Warms the cache for a tab without touching the visible pane. Used on hover/focus
    // (the user's likely-next click) and to pre-warm the rest of the rail once the
    // popover opens. Errors are swallowed — a failed prefetch just means the eventual
    // real click falls back to a normal cold load.
    function prefetchTab(url) {
        const key = pathnameOf(url);
        if (tabCache.has(key) || inFlight.has(key)) return;
        fetchTab(url).catch(() => {});
    }

    async function loadTab(url, { push = true } = {}) {
        const body = popoverBody();
        if (!body) return;

        const key = pathnameOf(url);
        const cached = tabCache.get(key);
        currentTabKey = key;

        if (cached !== undefined) {
            // Cache hit: paint immediately, no spinner, then revalidate in the background
            // and only touch the DOM again if the server actually returned something new
            // (e.g. another tab or another user changed this data since it was cached).
            body.innerHTML = cached;
            body.scrollTop = 0;
            syncActiveTab(url);
            if (push) pushUrl(url);

            try {
                const fresh = await fetchTab(url);
                if (fresh !== cached && popoverBody() === body && currentTabKey === key) {
                    body.innerHTML = fresh;
                }
            } catch {
                // Stale-but-cached content is still correct enough to leave on screen.
            }
            return;
        }

        // Cold load: only show the spinner if the response doesn't win the race against
        // a short delay, so a fast network never flashes it. Also guarded on currentTabKey
        // so a switch-away before this resolves can't flash the spinner over whatever tab
        // the user has since moved on to.
        const spinnerTimer = setTimeout(() => {
            if (currentTabKey === key) body.innerHTML = loading;
        }, SPINNER_DELAY_MS);

        try {
            const html = await fetchTab(url);
            clearTimeout(spinnerTimer);
            if (currentTabKey !== key) return;
            body.innerHTML = html;
            body.scrollTop = 0;
            syncActiveTab(url);
            if (push) pushUrl(url);
        } catch (error) {
            clearTimeout(spinnerTimer);
            if (currentTabKey !== key) return;
            body.innerHTML = `<div class="flex min-h-64 items-center justify-center p-8"><div class="text-center"><i class="fa-solid fa-triangle-exclamation text-2xl text-red-500"></i><p class="mt-3 text-sm font-semibold text-slate-300">Settings could not be loaded.</p><button type="button" data-retry-settings-tab="${url}" class="mt-4 rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Try again</button></div></div>`;
        }
    }

    function pushUrl(url) {
        const target = new URL(url, window.location.origin);
        if (window.location.pathname !== target.pathname) {
            history.pushState({ settingsPopover: true }, '', target.pathname + target.search);
        }
    }

    function openPopover(url, opts) {
        window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'settings-popover' } }));
        loadTab(url, opts);
        warmRestOfRail(url);
    }

    // Once a tab is showing, prefetch every other tab on the rail at low priority so the
    // whole rail feels instant after the first click, not just the second visit to a tab.
    function warmRestOfRail(currentUrl) {
        const currentPath = pathnameOf(currentUrl);
        // Rail is only in the DOM once the dialog has opened; queue past the paint.
        setTimeout(() => {
            document.querySelectorAll('.settings-nav-item').forEach((link) => {
                const href = link.getAttribute('href');
                if (href && pathnameOf(href) !== currentPath) prefetchTab(href);
            });
        }, 0);
    }

    document.addEventListener('click', (event) => {
        // The main sidebar / mobile drawer "Settings" entry — opens the popover instead
        // of navigating to the full page.
        const trigger = event.target.closest('[data-settings-trigger]');
        if (trigger) {
            event.preventDefault();
            const onSettingsPath = isSettingsPath(window.location.pathname);
            openPopover(onSettingsPath ? window.location.pathname : DEFAULT_TAB_URL, { push: !onSettingsPath });
            return;
        }

        // Rail tab links. The rail lives beside the content pane (not inside it), so this
        // matches on the dialog as a whole. A tab click only ever swaps the right pane —
        // it must never become a page load.
        const tabLink = event.target.closest('.settings-nav-item');
        if (tabLink && tabLink.closest('[data-settings-dialog]')) {
            event.preventDefault();
            loadTab(tabLink.getAttribute('href'));
            return;
        }

        const retry = event.target.closest('[data-retry-settings-tab]');
        if (retry) {
            loadTab(retry.dataset.retrySettingsTab, { push: false });
        }
    });

    // Prefetch on intent: pointerenter/focus fire well before click, and pointerdown fires
    // ~100ms ahead of click on top of that — by the time the click handler above runs,
    // fetchTab() usually already resolved into tabCache. pointerenter and focus don't
    // bubble, so delegating from `document` only works by listening on the *capture*
    // phase (which every event trickles through on the way down, bubbling or not).
    ['pointerenter', 'focus', 'pointerdown'].forEach((type) => {
        document.addEventListener(type, (event) => {
            const tabLink = event.target.closest?.('.settings-nav-item');
            if (tabLink && tabLink.closest('[data-settings-dialog]')) {
                prefetchTab(tabLink.getAttribute('href'));
            }
        }, { capture: true, passive: true });
    });

    // Back/forward: step through previously visited tabs, or close the popover once
    // the user has navigated past where it was opened — the underlying page was never
    // replaced, so there is nothing to restore.
    window.addEventListener('popstate', () => {
        if (isSettingsPath(window.location.pathname)) {
            openPopover(window.location.pathname, { push: false });
        } else {
            window.dispatchEvent(new CustomEvent('close-dialog', { detail: { id: 'settings-popover' } }));
        }
    });

    // Dispatched by dialog-forms.js after a successful ajax-form submit whose closest
    // dialog body is the settings popover — refreshes the current tab in place instead
    // of navigating away, so a save stays inside the popover.
    //
    // The whole tab cache is cleared, not just the saved tab: several tabs read
    // overlapping state (Business Hours writes booking fields Clinic also reads;
    // Services/FAQs/Payment Channels lists can be edited from more than one place), and a
    // save is rare enough that losing every tab's warm cache costs nothing users would
    // notice, while getting this wrong would mean a save is followed by stale HTML —
    // exactly the bug this cache exists to avoid introducing.
    window.addEventListener('settings-popover-refresh', (event) => {
        tabCache.clear();
        loadTab(event.detail.url, { push: false }).then(() => warmRestOfRail(event.detail.url));
    });

    // A direct/bookmarked visit to a /settings/* URL renders settings/_host.blade.php —
    // the plain app shell plus this marker. Settings has no standalone page, so open the
    // dialog straight onto the requested tab. Guarded because both DOMContentLoaded and
    // turbo:load fire on a first load.
    function autoOpenFromHost() {
        const marker = document.querySelector('[data-settings-autoopen]:not([data-opened])');
        if (!marker) return;
        marker.setAttribute('data-opened', '');
        openPopover(marker.dataset.settingsAutoopen, { push: false });
    }

    document.addEventListener('DOMContentLoaded', autoOpenFromHost);
    document.addEventListener('turbo:load', autoOpenFromHost);
})();
