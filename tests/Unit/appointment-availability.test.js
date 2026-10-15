import assert from 'node:assert/strict';
import { test } from 'node:test';
import { setupAppointmentAvailability } from '../../resources/js/appointment-availability.js';

function createBookingForm(fetch) {
    const events = {};
    const doctor = {
        selectedOptions: [{ dataset: { availabilityUrl: '/doctor-availability/7' } }],
        addEventListener: (name, handler) => { events.doctor = handler; },
    };
    const date = {
        value: '',
        validationMessage: '',
        setCustomValidity(message) { this.validationMessage = message; },
        addEventListener: (name, handler) => { events.date = handler; },
    };
    const button = { disabled: false };
    const notice = { hidden: true, textContent: '' };
    const fields = {
        '[name="doctor_id"]': doctor,
        '[name="appointment_date"]': date,
        'button[type="submit"]': button,
        '[data-availability-message]': notice,
    };
    const form = {
        dataset: { appointmentId: '12' },
        submitted: false,
        querySelector: selector => fields[selector],
        addEventListener: (name, handler) => { events.submit = handler; },
        reportValidity: () => true,
        submit() { this.submitted = true; },
    };
    const browser = { URL, location: { href: 'https://clinic-system.test/appointments/create' }, fetch };
    setupAppointmentAvailability(browser, { querySelector: () => form });
    date.value = '2026-10-16T09:00';

    return { events, doctor, date, button, notice, form };
}

test('unavailable times display the message and block submission', async () => {
    const booking = createBookingForm(async url => {
        assert.equal(url.searchParams.get('appointment_date'), '2026-10-16T09:00');
        assert.equal(url.searchParams.get('appointment_id'), '12');
        return { ok: true, json: async () => ({ available: false, message: 'Doctor is unavailable.' }) };
    });

    await booking.events.date();
    assert.equal(booking.button.disabled, true);
    assert.equal(booking.notice.hidden, false);
    assert.equal(booking.notice.textContent, 'Doctor is unavailable.');
    assert.equal(booking.date.validationMessage, 'Doctor is unavailable.');
    await booking.events.submit({ preventDefault() {} });
    assert.equal(booking.form.submitted, false);
});

test('changing to an available time clears the error and permits submission', async () => {
    let available = false;
    const booking = createBookingForm(async () => ({ ok: true, json: async () => ({ available, message: available ? null : 'Unavailable' }) }));
    await booking.events.date();
    available = true;
    booking.date.value = '2026-10-16T10:00';

    await booking.events.date();
    await booking.events.submit({ preventDefault() {} });

    assert.equal(booking.button.disabled, false);
    assert.equal(booking.date.validationMessage, '');
    assert.equal(booking.form.submitted, true);
});

test('an older availability response cannot enable booking for a newer unavailable selection', async () => {
    let resolveOlder;
    let requestCount = 0;
    const booking = createBookingForm(() => {
        requestCount++;
        if (requestCount === 1) {
            return new Promise(resolve => { resolveOlder = resolve; });
        }
        return Promise.resolve({ ok: true, json: async () => ({ available: false, message: 'New time unavailable' }) });
    });
    const older = booking.events.date();
    booking.date.value = '2026-10-16T09:30';
    await booking.events.date();
    resolveOlder({ ok: true, json: async () => ({ available: true, message: null }) });
    await older;

    assert.equal(booking.button.disabled, true);
    assert.equal(booking.notice.textContent, 'New time unavailable');
});

test('a failed availability check allows the server to validate the booking', async () => {
    const booking = createBookingForm(async () => { throw new Error('Network unavailable'); });

    await booking.events.submit({ preventDefault() {} });

    assert.equal(booking.button.disabled, false);
    assert.equal(booking.form.submitted, true);
    assert.match(booking.notice.textContent, /clinic will validate/);
});

test('submission rechecks availability that changed after a successful check', async () => {
    let available = true;
    const booking = createBookingForm(async () => ({ ok: true, json: async () => ({ available, message: available ? null : 'Now unavailable' }) }));
    await booking.events.date();
    available = false;

    await booking.events.submit({ preventDefault() {} });

    assert.equal(booking.form.submitted, false);
    assert.equal(booking.button.disabled, true);
});
