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
    const requestedStartAt = event.detail.requested_start_at || '';
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
        const sessionEndInput = form.elements.session_end_at;
        const exactDuration = form.querySelector('[data-exact-duration]');
        const windowEnds = { morning: '12:00', afternoon: '17:00' };
        let initialLoad = true;
        // session_end_at is a timezone-naive datetime-local value, parsed server-side as Asia/Manila local time
        const toManilaLocalInput = (isoString) => {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false,
            }).formatToParts(new Date(isoString)).reduce((acc, p) => ({ ...acc, [p.type]: p.value }), {});
            return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
        };
        const applySessionEndDefaults = () => {
            if (form.elements.scheduling_mode.value !== 'first_come' || !slot.value) return;
            const windowEnd = windowEnds[slot.selectedOptions[0]?.dataset.window];
            if (!windowEnd) return;
            const startLocal = toManilaLocalInput(slot.value);
            const defaultEnd = `${startLocal.slice(0, 10)}T${windowEnd}`;
            sessionEndInput.min = startLocal; sessionEndInput.max = defaultEnd; sessionEndInput.step = 1800;
            sessionEndInput.value = defaultEnd;
        };
        const load = async () => {
            if (!dentist.value || !date.value) return;
            slot.disabled = true; status.textContent = 'Checking availability…';
            const url = new URL(form.elements.availability_url.value, window.location.origin);
            url.searchParams.set('dentist_id', dentist.value); url.searchParams.set('date', date.value); url.searchParams.set('duration_minutes', duration.value);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            const fcfs = form.elements.scheduling_mode.value === 'first_come';
            slot.replaceChildren(new Option(data.slots.length ? 'Select a start time' : 'No available times', ''), ...data.slots.map(s => {
                const option = new Option(`${fcfs ? s.label : s.range_label} (${s.window})`, s.start);
                option.dataset.window = s.window;
                return option;
            }));
            slot.disabled = data.slots.length === 0; status.textContent = data.slots.length ? `${data.slots.length} conflict-free choices` : 'Try another dentist or date.';
            const warning = form.querySelector('[data-priority-warning]');
            warning.classList.toggle('hidden', data.older_requests.length === 0);
            form.querySelector('[data-older-requests]').textContent = data.older_requests.map(r => r.full_name).join(', ');
            if (initialLoad && !fcfs && requestedStartAt && [...slot.options].some((o) => o.value === requestedStartAt)) {
                slot.value = requestedStartAt;
            }
            initialLoad = false;
            applySessionEndDefaults();
        };
        const toggleMode = () => {
            const fcfs = form.elements.scheduling_mode.value === 'first_come';
            sessionEnd.classList.toggle('hidden', !fcfs);
            exactDuration.classList.toggle('hidden', fcfs);
            sessionEndInput.required = fcfs;
            duration.disabled = fcfs;
            load();
        };
        dentist.onchange = load; date.onchange = load; duration.onchange = load; slot.onchange = applySessionEndDefaults;
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

const patientBookingStates = new WeakMap();

function localDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function addDays(date, days) {
    const next = new Date(date);
    next.setDate(next.getDate() + days);
    return next;
}

function money(value) {
    return `PHP ${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function initPatientBooking() {
    document.querySelectorAll('[data-patient-booking]').forEach((form) => {
        if (patientBookingStates.has(form)) return;

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const state = {
            weekStart: today,
            selectedDate: localDateString(today),
            selectedSlot: form.querySelector('[data-booking-start]')?.value || '',
            datesController: null,
            slotsController: null,
            lastSlotPayload: null,
        };
        patientBookingStates.set(form, state);

        const dateStrip = form.querySelector('[data-date-strip]');
        const timeSlots = form.querySelector('[data-time-slots]');
        const live = form.querySelector('[data-booking-live]');
        const weekLabel = form.querySelector('[data-week-label]');
        const startInput = form.querySelector('[data-booking-start]');
        const services = () => [...form.querySelectorAll('[data-booking-service]:checked')];
        const serviceIds = () => services().map((input) => input.value);
        const minDate = today;
        const maxDate = addDays(today, 90);

        const setLive = (message) => { if (live) live.textContent = message; };
        const updateReview = () => {
            const chosen = services();
            const names = chosen.map((input) => input.dataset.serviceName);
            const duration = chosen.reduce((sum, input) => sum + Number(input.dataset.serviceDuration || 0), 0);
            const total = chosen.reduce((sum, input) => sum + Number(input.dataset.servicePrice || 0), 0);
            form.querySelector('[data-review-services]').textContent = names.length ? names.join(', ') : 'None selected';
            form.querySelector('[data-review-duration]').textContent = `${duration} min`;
            form.querySelector('[data-review-total]').textContent = money(total);
        };
        const updateWeekLabel = () => {
            const end = addDays(state.weekStart, 6);
            weekLabel.textContent = `${state.weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
            form.querySelector('[data-week-prev]').disabled = state.weekStart <= minDate;
            form.querySelector('[data-week-next]').disabled = addDays(state.weekStart, 7) > maxDate;
        };
        const emptyDates = (message) => {
            dateStrip.replaceChildren();
            timeSlots.replaceChildren();
            startInput.value = '';
            setLive(message);
        };
        const showDateLoadError = (message) => {
            dateStrip.replaceChildren();
            timeSlots.replaceChildren();
            startInput.value = '';

            const box = document.createElement('div');
            box.className = 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:col-span-4 lg:col-span-7';

            const copy = document.createElement('p');
            copy.textContent = message;

            const retry = document.createElement('button');
            retry.type = 'button';
            retry.dataset.bookingDatesRetry = '';
            retry.className = 'mt-3 inline-flex min-h-11 items-center gap-2 rounded-xl border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200';
            retry.innerHTML = '<i class="fa-solid fa-rotate-right"></i><span>Retry availability</span>';

            box.append(copy, retry);
            dateStrip.appendChild(box);
            setLive(message);
        };
        const jsonResponse = async (response, fallbackMessage) => {
            const responseUrl = new URL(response.url || window.location.href, window.location.origin);
            if (response.redirected && responseUrl.pathname === '/login') {
                window.location.assign(response.url);
                return null;
            }

            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            const payload = isJson ? await response.json().catch(() => null) : null;

            if (!response.ok) {
                const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload?.message || fallbackMessage);
            }

            if (!isJson) {
                throw new Error('Availability could not be loaded. Please refresh the page and sign in again.');
            }

            return payload;
        };
        const renderDates = (days) => {
            dateStrip.replaceChildren(...days.map((day) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.date = day.date;
                button.disabled = !day.open || !day.available;
                button.className = [
                    'min-h-24 rounded-xl border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-emerald-200',
                    day.date === state.selectedDate ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-700',
                    (!day.open || !day.available) ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'hover:border-emerald-300 hover:bg-emerald-50',
                ].join(' ');
                const label = document.createElement('span');
                label.className = 'block text-xs font-semibold uppercase tracking-wide';
                label.textContent = day.weekday;
                const number = document.createElement('span');
                number.className = 'mt-1 block text-2xl font-bold';
                number.textContent = day.day;
                const month = document.createElement('span');
                month.className = 'block text-xs font-semibold';
                month.textContent = day.month;
                const status = document.createElement('span');
                status.className = 'mt-2 block text-xs';
                status.textContent = day.available ? 'Available' : (day.reason || 'Unavailable');
                button.append(label, number, month, status);
                return button;
            }));

            const selected = days.find((day) => day.date === state.selectedDate && day.open && day.available)
                || days.find((day) => day.open && day.available);
            if (selected) {
                state.selectedDate = selected.date;
                loadSlots();
            } else {
                timeSlots.replaceChildren();
                startInput.value = '';
                setLive('No available dates in this seven-day range.');
            }
        };
        const renderSlots = (payload) => {
            state.lastSlotPayload = payload;
            timeSlots.replaceChildren();
            startInput.value = '';
            const slots = (payload.slots || []).filter((slot) => slot.available);
            if (!slots.length) {
                const box = document.createElement('div');
                box.className = 'rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500 sm:col-span-3 lg:col-span-4';
                box.textContent = 'No exact times are available for this date.';
                timeSlots.appendChild(box);
                setLive('No available time slots.');
                return;
            }
            timeSlots.replaceChildren(...slots.map((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.slot = slot.start;
                button.className = [
                    'min-h-11 rounded-xl border px-3 text-sm font-semibold transition',
                    slot.start === state.selectedSlot ? 'border-emerald-300 bg-emerald-500 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-emerald-50 hover:text-emerald-700',
                ].join(' ');
                button.textContent = slot.range_label;
                return button;
            }));
            if (slots.some((slot) => slot.start === state.selectedSlot)) {
                startInput.value = state.selectedSlot;
            }
            setLive(`${slots.length} available time slots. Estimated total ${money(payload.estimated_total)}.`);
            form.querySelector('[data-review-duration]').textContent = `${payload.duration || 0} min`;
            form.querySelector('[data-review-total]').textContent = money(payload.estimated_total);
        };
        const loadDates = async () => {
            updateReview();
            updateWeekLabel();
            state.datesController?.abort();
            state.slotsController?.abort();
            if (!serviceIds().length) {
                emptyDates('Choose at least one service to load availability.');
                return;
            }
            state.datesController = new AbortController();
            setLive('Loading available dates...');
            const url = new URL(form.dataset.datesUrl, window.location.origin);
            url.searchParams.set('start_date', localDateString(state.weekStart));
            serviceIds().forEach((id) => url.searchParams.append('service_ids[]', id));
            try {
                const response = await fetch(url, { signal: state.datesController.signal, headers: { Accept: 'application/json' } });
                const payload = await jsonResponse(response, 'Availability could not be loaded. Please try again.');
                if (payload) renderDates(payload.days || []);
            } catch (error) {
                if (error.name === 'AbortError') return;
                showDateLoadError(error.message || 'Availability could not be loaded. Please try again.');
            }
        };
        async function loadSlots() {
            state.slotsController?.abort();
            if (!serviceIds().length || !state.selectedDate) return;
            state.slotsController = new AbortController();
            setLive('Loading exact times...');
            timeSlots.replaceChildren();
            const url = new URL(form.dataset.slotsUrl, window.location.origin);
            url.searchParams.set('date', state.selectedDate);
            serviceIds().forEach((id) => url.searchParams.append('service_ids[]', id));
            try {
                const response = await fetch(url, { signal: state.slotsController.signal, headers: { Accept: 'application/json' } });
                const payload = await jsonResponse(response, 'Availability failed. Retry without changing your services.');
                if (payload) renderSlots(payload);
            } catch (error) {
                if (error.name === 'AbortError') return;
                timeSlots.innerHTML = '<button type="button" data-booking-retry class="min-h-11 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-600">Retry availability</button>';
                setLive(error.message || 'Availability failed. Retry without changing your services.');
            }
        }

        form.addEventListener('change', (event) => {
            if (event.target.matches('[data-booking-service]')) {
                state.selectedSlot = '';
                loadDates();
            } else if (event.target.matches('[data-calendar-jump]') && event.target.value) {
                const jump = new Date(`${event.target.value}T00:00:00`);
                state.weekStart = jump < minDate ? minDate : (jump > maxDate ? maxDate : jump);
                state.selectedDate = localDateString(state.weekStart);
                state.selectedSlot = '';
                loadDates();
            }
        });
        form.addEventListener('click', (event) => {
            const dateButton = event.target.closest('[data-date]');
            if (dateButton && !dateButton.disabled) {
                state.selectedDate = dateButton.dataset.date;
                state.selectedSlot = '';
                renderDates([...dateStrip.querySelectorAll('[data-date]')].map((button) => ({
                    date: button.dataset.date,
                    weekday: button.children[0].textContent,
                    day: button.children[1].textContent,
                    month: button.children[2].textContent,
                    open: !button.disabled,
                    available: !button.disabled,
                    reason: button.children[3].textContent,
                })));
                return;
            }
            const slotButton = event.target.closest('[data-slot]');
            if (slotButton) {
                state.selectedSlot = slotButton.dataset.slot;
                startInput.value = state.selectedSlot;
                renderSlots(state.lastSlotPayload || { slots: [] });
                setLive(`Selected ${slotButton.textContent}.`);
                return;
            }
            if (event.target.closest('[data-week-prev]')) {
                state.weekStart = addDays(state.weekStart, -7);
                if (state.weekStart < minDate) state.weekStart = minDate;
                state.selectedDate = localDateString(state.weekStart);
                state.selectedSlot = '';
                loadDates();
                return;
            }
            if (event.target.closest('[data-week-next]')) {
                state.weekStart = addDays(state.weekStart, 7);
                if (state.weekStart > maxDate) state.weekStart = maxDate;
                state.selectedDate = localDateString(state.weekStart);
                state.selectedSlot = '';
                loadDates();
                return;
            }
            if (event.target.closest('[data-booking-dates-retry]')) {
                loadDates();
                return;
            }
            if (event.target.closest('[data-booking-retry]')) loadSlots();
        });
        updateReview();
        loadDates();
    });
}

document.addEventListener('DOMContentLoaded', () => { initPublicBooking(); initCancelReschedule(); initPatientBooking(); });
document.addEventListener('turbo:load', () => { initPublicBooking(); initCancelReschedule(); initPatientBooking(); });
document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('[data-patient-booking]').forEach((form) => {
        const state = patientBookingStates.get(form);
        state?.datesController?.abort();
        state?.slotsController?.abort();
        patientBookingStates.delete(form);
    });
});

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
