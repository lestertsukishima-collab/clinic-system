<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('herd-favicon.png') }}?v=1">
    <title>Book Appointment</title>
</head>
<body>

    <h2>Book an Appointment</h2>

    <form id="appointmentForm">
        <!-- Patient (Hardcoded ID muna para sa testing kung wala pang auth) -->
        <label for="patient_id">Patient ID:</label>
        <input type="number" id="patient_id" placeholder="e.g. 1" required>
        <br><br>

        <!-- Select Doctor -->
        <label for="doctor">Select Doctor:</label>
        <select id="doctor" required>
            <option value="">Loading doctors...</option>
        </select>
        <br><br>

        <!-- Select Service -->
        <label for="service">Select Service:</label>
        <select id="service" required>
            <option value="">Loading services...</option>
        </select>
        <br><br>

        <!-- Date & Time -->
        <label for="appointment_date">Appointment Date & Time:</label>
        <input type="datetime-local" id="appointment_date" required>
        <br><br>

        <!-- Notes -->
        <label for="notes">Notes/Remarks:</label><br>
        <textarea id="notes" rows="3" cols="30" placeholder="Optional notes..."></textarea>
        <br><br>

        <button type="submit">Submit Appointment</button>
    </form>

    <div id="responseMessage" style="margin-top: 15px;"></div>

    <script>
        // 1. Kuhanin ang listahan ng Doctors at Services pag-load ng page
        async function loadDropdowns() {
            try {
                // Fetch Doctors
                const docRes = await fetch('/api/doctors');
                const doctors = await docRes.json();
                const doctorSelect = document.getElementById('doctor');
                doctorSelect.innerHTML = '<option value="">-- Choose a Doctor --</option>';
                doctors.forEach(doc => {
                    doctorSelect.innerHTML += `<option value="${doc.id}">${doc.name || 'Doctor #' + doc.id}</option>`;
                });

                // Fetch Services
                const servRes = await fetch('/api/v1/services');
                const servicesData = await servRes.json();
                const services = servicesData.data || servicesData; // In kaso naka-wrap sa 'data'
                const serviceSelect = document.getElementById('service');
                serviceSelect.innerHTML = '<option value="">-- Choose a Service --</option>';
                services.forEach(serv => {
                    serviceSelect.innerHTML += `<option value="${serv.id}">${serv.name || 'Service #' + serv.id}</option>`;
                });

            } catch (error) {
                console.error('Error loading options:', error);
            }
        }

        // 2. Submit form sa /api/appointments
        document.getElementById('appointmentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                patient_id: document.getElementById('patient_id').value,
                doctor_id: document.getElementById('doctor').value,
                service_id: document.getElementById('service').value,
                appointment_date: document.getElementById('appointment_date').value,
                notes: document.getElementById('notes').value
            };

            try {
                const response = await fetch('/api/appointments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok) {
                    document.getElementById('responseMessage').style.color = 'green';
                    document.getElementById('responseMessage').innerText = 'Appointment successfully created!';
                    document.getElementById('appointmentForm').reset();
                } else {
                    document.getElementById('responseMessage').style.color = 'red';
                    document.getElementById('responseMessage').innerText = result.message || 'Failed to create appointment.';
                }
            } catch (error) {
                console.error('Error submitting form:', error);
            }
        });

        loadDropdowns();
    </script>
</body>
</html>
