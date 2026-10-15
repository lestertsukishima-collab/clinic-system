export function setupAppointmentAvailability(browser, page) {
    const form = page.querySelector('[data-appointment-availability]');

    if (!form) {
        return;
    }

    const doctor = form.querySelector('[name="doctor_id"]');
    const date = form.querySelector('[name="appointment_date"]');
    const button = form.querySelector('button[type="submit"]');
    const notice = form.querySelector('[data-availability-message]');
    let revision = 0;

    const checkAvailability = async () => {
        const currentRevision = ++revision;
        const endpoint = doctor.selectedOptions[0]?.dataset.availabilityUrl;
        notice.hidden = true;
        button.disabled = false;
        date.setCustomValidity('');

        if (!endpoint || !date.value) {
            return false;
        }

        button.disabled = true;
        notice.textContent = 'Checking doctor availability…';
        notice.hidden = false;
        const url = new browser.URL(endpoint, browser.location.href);
        url.searchParams.set('appointment_date', date.value);
        if (form.dataset.appointmentId) {
            url.searchParams.set('appointment_id', form.dataset.appointmentId);
        }

        try {
            const response = await browser.fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error('Availability check failed');
            }
            const result = await response.json();
            if (currentRevision !== revision) {
                return false;
            }

            button.disabled = !result.available;
            notice.textContent = result.message ?? 'This doctor is available at the selected time.';
            date.setCustomValidity(result.available ? '' : notice.textContent);
            return result.available;
        } catch {
            if (currentRevision !== revision) {
                return false;
            }
            button.disabled = false;
            notice.textContent = 'Availability could not be checked. The clinic will validate this time when you submit.';
            return true;
        }
    };

    doctor.addEventListener('change', checkAvailability);
    date.addEventListener('input', checkAvailability);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (await checkAvailability() && form.reportValidity()) {
            form.submit();
        }
    });
    checkAvailability();
}
