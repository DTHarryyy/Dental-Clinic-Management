import './bootstrap';
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
