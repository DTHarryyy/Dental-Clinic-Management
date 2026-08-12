import './bootstrap';
import './dashboard-charts';
import * as Turbo from '@hotwired/turbo';

import Alpine from 'alpinejs';

window.Turbo = Turbo;
Turbo.setProgressBarDelay(150);

if (document.querySelector('meta[name="turbo-enabled"]')?.content !== 'true') {
    Turbo.session.drive = false;
}

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('turbo:load', () => {
    document.querySelectorAll('form[data-ajax-form]').forEach((form) => {
        form.dataset.turbo = 'false';
    });
});

window.openPatientDetail = (url) => {
    const frame = document.getElementById('patient-detail-frame');
    if (frame) frame.setAttribute('src', url);
    window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'patient-view' } }));
};

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-patient-detail]');
    if (trigger) window.openPatientDetail(trigger.dataset.patientDetail);
});

document.addEventListener('turbo:visit', () => document.documentElement.classList.add('turbo-loading'));
document.addEventListener('turbo:load', () => document.documentElement.classList.remove('turbo-loading'));
document.addEventListener('turbo:fetch-request-error', () => document.documentElement.classList.remove('turbo-loading'));

const patientLookupState = new WeakMap();
document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-patient-search]');
    if (!input) return;
    const root = input.closest('[data-patient-lookup]');
    const hidden = root.querySelector('[data-patient-id]');
    const results = root.querySelector('[data-patient-results]');
    hidden.value = '';
    clearTimeout(patientLookupState.get(root)?.timer);
    patientLookupState.get(root)?.controller?.abort();

    const timer = setTimeout(async () => {
        const controller = new AbortController();
        patientLookupState.set(root, { controller });
        const url = new URL(root.dataset.lookupUrl, window.location.origin);
        url.searchParams.set('q', input.value.trim());
        try {
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            const payload = await response.json();
            results.replaceChildren(...payload.data.map((patient) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-emerald-50';
                button.dataset.patientOption = patient.id;
                button.dataset.patientName = patient.name;
                button.textContent = patient.email ? `${patient.name} · ${patient.email}` : patient.name;
                return button;
            }));
            results.classList.toggle('hidden', payload.data.length === 0);
        } catch (error) {
            if (error.name !== 'AbortError') results.classList.add('hidden');
        }
    }, 250);
    patientLookupState.set(root, { timer });
});

document.addEventListener('click', (event) => {
    const option = event.target.closest('[data-patient-option]');
    if (!option) return;
    const root = option.closest('[data-patient-lookup]');
    root.querySelector('[data-patient-id]').value = option.dataset.patientOption;
    root.querySelector('[data-patient-search]').value = option.dataset.patientName;
    root.querySelector('[data-patient-results]').classList.add('hidden');
});

// Invoice details are loaded dynamically, so this handler belongs on the document
// rather than in page initialization code. It also survives Turbo navigation.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-use-full-balance]');
    if (!button) return;

    event.preventDefault();
    const form = button.closest('form');
    const amount = form?.querySelector('[name="amount"]');
    const balance = Number.parseFloat(button.dataset.useFullBalance);
    if (!amount || !Number.isFinite(balance)) return;

    amount.value = balance.toFixed(2);
    amount.dispatchEvent(new Event('input', { bubbles: true }));
    amount.dispatchEvent(new Event('change', { bubbles: true }));
    amount.focus({ preventScroll: true });
    amount.select();

    const feedback = form.querySelector('[data-full-balance-feedback]');
    if (feedback) {
        feedback.textContent = `Full balance of ₱${balance.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} applied.`;
        feedback.classList.remove('hidden');
    }

    button.dataset.applied = 'true';
    button.setAttribute('aria-pressed', 'true');
    button.querySelector('[data-full-balance-label]').textContent = 'Full amount selected';
});

document.addEventListener('input', (event) => {
    const amount = event.target.closest('form[data-ajax-form] [name="amount"]');
    if (!amount || event.isTrusted === false) return;

    const form = amount.closest('form');
    const button = form?.querySelector('[data-use-full-balance]');
    const feedback = form?.querySelector('[data-full-balance-feedback]');
    if (button) {
        button.dataset.applied = 'false';
        button.setAttribute('aria-pressed', 'false');
        button.querySelector('[data-full-balance-label]').textContent = button.dataset.defaultLabel;
    }
    feedback?.classList.add('hidden');
});

document.addEventListener('click', (event) => {
    const preview = event.target.closest('[data-receipt-preview-url]');
    if (preview) {
        const frame = document.querySelector('[data-receipt-preview-frame]');
        const loading = document.querySelector('[data-receipt-preview-loading]');
        const printButton = document.querySelector('[data-print-receipt-preview]');
        if (!frame || !printButton) return;

        if (loading) {
            loading.textContent = 'Loading receipt…';
            loading.classList.remove('hidden', 'text-red-600');
            loading.classList.add('text-slate-500');
        }
        printButton.disabled = true;
        printButton.querySelector('[data-preview-print-label]').textContent = 'Loading…';
        const fail = () => {
            if (loading) {
                loading.textContent = 'The receipt could not be loaded. Close the preview and try again.';
                loading.classList.remove('hidden', 'text-slate-500');
                loading.classList.add('text-red-600');
            }
            printButton.disabled = true;
            printButton.querySelector('[data-preview-print-label]').textContent = 'Unavailable';
        };
        frame.onload = () => {
            try {
                if (!frame.contentDocument?.body?.classList.contains('receipt-embedded')) {
                    fail();
                    return;
                }
                loading?.classList.add('hidden');
                printButton.disabled = false;
                printButton.querySelector('[data-preview-print-label]').textContent = 'Print';
            } catch {
                fail();
            }
        };
        frame.onerror = fail;
        frame.src = preview.dataset.receiptPreviewUrl;
        window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'receipt-preview' } }));
        return;
    }

    const printPreview = event.target.closest('[data-print-receipt-preview]');
    if (!printPreview) return;

    const printWindow = document.querySelector('[data-receipt-preview-frame]')?.contentWindow;
    printWindow?.focus();
    printWindow?.print();
}, true);

document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('[data-toast], [data-form-errors]').forEach((element) => element.remove());
    document.querySelectorAll('form[data-ajax-form]').forEach((form) => {
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[disabled][data-loading-disabled]').forEach((element) => {
            element.disabled = false;
            element.removeAttribute('data-loading-disabled');
        });
    });
});

window.addEventListener('open-dialog', (event) => {
    if (event.detail.id !== 'appointment-confirm') return;
    setTimeout(() => {
        const form = document.querySelector('[data-appointment-confirm]');
        if (!form) return;
        const dentist = form.elements.dentist_id;
        const date = form.elements.schedule_date;
        const slot = form.elements.scheduled_start_at;
        const status = form.querySelector('[data-slot-status]');
        const duration = form.elements.duration_minutes;
        const modes = form.querySelectorAll('[name="scheduling_mode"]');
        const sessionEnd = form.querySelector('[data-session-end]');
        const exactDuration = form.querySelector('[data-exact-duration]');
        const load = async () => {
            if (!dentist.value || !date.value) return;
            slot.disabled = true; status.textContent = 'Checking availability…';
            const url = new URL(form.elements.availability_url.value, window.location.origin);
            url.searchParams.set('dentist_id', dentist.value); url.searchParams.set('date', date.value); url.searchParams.set('duration_minutes', duration.value);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            slot.replaceChildren(new Option(data.slots.length ? 'Select a start time' : 'No available times', ''), ...data.slots.map(s => new Option(`${s.range_label} (${s.window})`, s.start)));
            slot.disabled = data.slots.length === 0; status.textContent = data.slots.length ? `${data.slots.length} conflict-free choices` : 'Try another dentist or date.';
            const warning = form.querySelector('[data-priority-warning]');
            warning.classList.toggle('hidden', data.older_requests.length === 0);
            form.querySelector('[data-older-requests]').textContent = data.older_requests.map(r => r.full_name).join(', ');
        };
        const toggleMode = () => {
            const fcfs = form.elements.scheduling_mode.value === 'first_come';
            sessionEnd.classList.toggle('hidden', !fcfs);
            exactDuration.classList.toggle('hidden', fcfs);
            form.elements.session_end_at.required = fcfs;
            duration.disabled = fcfs;
            load();
        };
        dentist.onchange = load; date.onchange = load; duration.onchange = load;
        modes.forEach((radio) => radio.onchange = toggleMode);
        toggleMode();
    }, 0);
});

function initPublicBooking() {
    document.querySelectorAll('[data-public-booking]').forEach((form) => {
    if (form.dataset.scheduleReady) return;
    form.dataset.scheduleReady = '1';
    const date = form.elements.preferred_date;
    const slot = form.elements.requested_start_at;
    const status = form.querySelector('[data-public-slot-status]');
    const load = async () => {
        const services = [...form.querySelectorAll('[name="service_ids[]"]:checked')].map((input) => input.value);
        if (!date.value || services.length === 0) {
            slot.disabled = true;
            slot.replaceChildren(new Option('Choose date and services first', ''));
            return;
        }
        slot.disabled = true; status.textContent = 'Checking availability…';
        const url = new URL(form.dataset.availabilityUrl, window.location.origin);
        url.searchParams.set('date', date.value);
        services.forEach((id) => url.searchParams.append('service_ids[]', id));
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        const options = data.slots.map((item) => {
            const option = new Option(item.range_label, item.start, false, item.start === "");
            option.disabled = !item.available;
            return option;
        });
        slot.replaceChildren(new Option('Select an exact time', ''), ...options);
        slot.disabled = false;
        status.textContent = `${data.duration} minute estimated appointment`;
    };
    date.addEventListener('change', load);
    form.querySelectorAll('[name="service_ids[]"]').forEach((input) => input.addEventListener('change', load));
    slot.addEventListener('change', () => {
        if (!slot.value) return;
        form.elements.preferred_time_window.value = new Date(slot.value).toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour12: false, hour: '2-digit' }) < '12' ? 'morning' : 'afternoon';
    });
    load();
    });
}

function initCancelReschedule() {
    document.querySelectorAll('[data-cancel-reschedule]').forEach((form) => {
        if (form.dataset.scheduleReady) return;
        form.dataset.scheduleReady = '1';
        const date = form.elements.reschedule_date;
        const duration = form.elements.reschedule_duration_minutes;
        const slot = form.elements.reschedule_start_at;
        const status = form.querySelector('[data-reschedule-status]');
        const load = async () => {
            if (!date.value || !form.elements.availability_url.value) return;
            slot.disabled = true; status.textContent = 'Checking availability…';
            const url = new URL(form.elements.availability_url.value, window.location.origin);
            url.searchParams.set('date', date.value); url.searchParams.set('duration_minutes', duration.value);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            slot.replaceChildren(new Option('Select an exact time', ''), ...data.slots.map((item) => {
                const option = new Option(item.range_label, item.start); option.disabled = !item.available; return option;
            }));
            slot.disabled = false; status.textContent = `${duration.value} minute range`;
        };
        date.addEventListener('change', load); duration.addEventListener('change', load);
        form.elements.reschedule_requested.addEventListener('change', () => setTimeout(load, 0));
    });
}

document.addEventListener('DOMContentLoaded', () => { initPublicBooking(); initCancelReschedule(); });
document.addEventListener('turbo:load', () => { initPublicBooking(); initCancelReschedule(); });

const globalSearchStates = new WeakMap();

function initGlobalSearch() {
    document.querySelectorAll('[data-global-search]').forEach((root) => {
        if (globalSearchStates.has(root)) return;

        const state = { query: '', timer: null, controller: null, active: -1, results: [] };
        const inputs = [...root.querySelectorAll('[data-global-search-input]')];
        const desktopPanel = root.querySelector('[data-global-search-panel]');
        const mobilePanel = root.querySelector('[data-global-search-mobile]');
        const status = root.querySelector('[data-global-search-status]');
        globalSearchStates.set(root, state);

        const setExpanded = (expanded) => inputs.forEach((input) => input.setAttribute('aria-expanded', String(expanded)));
        const setStatus = (message) => { status.textContent = message; };
        const visibleContainers = () => [...root.querySelectorAll('[data-global-search-results]')];
        const syncInputs = (source) => inputs.forEach((input) => { if (input !== source) input.value = source.value; });

        function message(text, retry = false) {
            visibleContainers().forEach((container) => {
                container.replaceChildren();
                const box = document.createElement('div');
                box.className = 'p-6 text-center text-sm text-slate-500';
                const copy = document.createElement('p'); copy.textContent = text; box.appendChild(copy);
                if (retry) {
                    const button = document.createElement('button');
                    button.type = 'button'; button.dataset.globalSearchRetry = '';
                    button.className = 'mt-3 min-h-11 rounded-xl bg-slate-800 px-4 font-semibold text-white';
                    button.textContent = 'Try again'; box.appendChild(button);
                }
                container.appendChild(box);
            });
            state.results = []; state.active = -1; setStatus(text);
        }

        function render(payload) {
            state.results = [];
            visibleContainers().forEach((container) => {
                container.replaceChildren();
                payload.groups.forEach((group) => {
                    if (!group.results.length) return;
                    const section = document.createElement('section'); section.className = 'py-1';
                    const heading = document.createElement('h3');
                    heading.className = 'px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400';
                    heading.textContent = group.label; section.appendChild(heading);
                    group.results.forEach((result) => {
                        const link = document.createElement('a');
                        link.href = result.url; link.id = `${container.id}-${result.id}`; link.role = 'option';
                        link.dataset.globalSearchResult = String(state.results.length);
                        link.className = 'global-search-result';
                        const icon = document.createElement('span'); icon.className = 'global-search-result-icon';
                        const glyph = document.createElement('i');
                        glyph.className = `fa-solid ${{ patients: 'fa-user', appointments: 'fa-calendar-days', records: 'fa-tooth', billing: 'fa-file-invoice-dollar' }[group.type] || 'fa-magnifying-glass'}`;
                        icon.appendChild(glyph);
                        const copy = document.createElement('span'); copy.className = 'min-w-0 flex-1';
                        const title = document.createElement('span'); title.className = 'break-content block text-sm font-semibold text-slate-800'; title.textContent = result.title;
                        const subtitle = document.createElement('span'); subtitle.className = 'break-content mt-0.5 block text-xs text-slate-500'; subtitle.textContent = result.subtitle;
                        copy.append(title, subtitle);
                        const meta = document.createElement('span'); meta.className = 'ml-2 shrink-0 text-[10px] font-semibold text-slate-400'; meta.textContent = result.meta;
                        link.append(icon, copy, meta); section.appendChild(link);
                        if (container === visibleContainers()[0]) state.results.push(result);
                    });
                    container.appendChild(section);
                });
            });
            state.active = -1;
            setStatus(payload.total ? `${payload.total} search results available.` : 'No matching records found.');
            if (!payload.total) message('No matching records found.');
        }

        function updateActive(next) {
            const links = [...root.querySelectorAll('[data-global-search-result]')];
            if (!links.length) return;
            state.active = Math.max(0, Math.min(next, state.results.length - 1));
            links.forEach((link) => {
                const active = Number(link.dataset.globalSearchResult) === state.active;
                link.classList.toggle('is-active', active); link.setAttribute('aria-selected', String(active));
            });
            inputs.forEach((input) => input.setAttribute('aria-activedescendant', `${input.getAttribute('aria-controls')}-${state.results[state.active].id}`));
            links.find((link) => Number(link.dataset.globalSearchResult) === state.active && link.offsetParent)?.scrollIntoView({ block: 'nearest' });
        }

        async function search() {
            const query = state.query.trim().replace(/\s+/g, ' ');
            state.controller?.abort();
            if (query.length < 2) { message(query ? 'Enter at least two characters.' : 'Search patients, appointments, records, and invoices.'); return; }
            state.controller = new AbortController(); message(`Searching for “${query}”…`);
            try {
                const url = new URL(root.dataset.searchUrl, window.location.origin); url.searchParams.set('q', query);
                const response = await fetch(url, { signal: state.controller.signal, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('Search failed');
                render(await response.json());
            } catch (error) {
                if (error.name !== 'AbortError') message('Search is unavailable. Check your connection and try again.', true);
            }
        }

        function openDesktop() { desktopPanel.classList.remove('hidden'); setExpanded(true); if (!state.query.trim()) message('Search patients, appointments, records, and invoices.'); }
        function closeDesktop() { desktopPanel.classList.add('hidden'); setExpanded(false); state.active = -1; }
        function openMobile() {
            mobilePanel.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); setExpanded(true);
            const input = root.querySelector('[data-search-mode="mobile"]'); input.value = state.query; setTimeout(() => input.focus(), 0);
            if (!state.query.trim()) message('Search patients, appointments, records, and invoices.');
        }
        function closeMobile() { mobilePanel.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); setExpanded(false); state.active = -1; }

        inputs.forEach((input) => {
            input.addEventListener('focus', () => { if (input.dataset.searchMode === 'desktop') openDesktop(); });
            input.addEventListener('input', () => {
                state.query = input.value; syncInputs(input); state.active = -1;
                clearTimeout(state.timer); state.timer = setTimeout(search, 250);
            });
            input.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') { event.preventDefault(); updateActive(state.active + 1); }
                else if (event.key === 'ArrowUp') { event.preventDefault(); updateActive(state.active <= 0 ? state.results.length - 1 : state.active - 1); }
                else if (event.key === 'Home' && state.results.length) { event.preventDefault(); updateActive(0); }
                else if (event.key === 'End' && state.results.length) { event.preventDefault(); updateActive(state.results.length - 1); }
                else if (event.key === 'Enter' && state.active >= 0) { event.preventDefault(); window.location.href = state.results[state.active].url; }
                else if (event.key === 'Escape') { event.preventDefault(); input.dataset.searchMode === 'mobile' ? closeMobile() : closeDesktop(); }
            });
        });
        root.querySelector('[data-global-search-open]').addEventListener('click', openMobile);
        root.querySelector('[data-global-search-close]').addEventListener('click', closeMobile);
        root.addEventListener('click', (event) => { if (event.target.closest('[data-global-search-retry]')) search(); });
        state.openDesktop = openDesktop; state.closeDesktop = closeDesktop; state.openMobile = openMobile; state.closeMobile = closeMobile;
    });
}

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-global-search]').forEach((root) => {
        if (root.contains(event.target)) return;
        const state = globalSearchStates.get(root); state?.closeDesktop();
    });
});
document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'k') return;
    const root = document.querySelector('[data-global-search]'); if (!root) return;
    event.preventDefault();
    const state = globalSearchStates.get(root);
    if (window.matchMedia('(max-width: 639px)').matches) state?.openMobile();
    else { state?.openDesktop(); root.querySelector('[data-search-mode="desktop"]')?.focus(); }
});
document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('[data-global-search]').forEach((root) => {
        const state = globalSearchStates.get(root); state?.controller?.abort(); state?.closeMobile(); state?.closeDesktop();
    });
});
document.addEventListener('DOMContentLoaded', initGlobalSearch);
document.addEventListener('turbo:load', initGlobalSearch);
