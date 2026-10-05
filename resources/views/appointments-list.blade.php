<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('herd-favicon.png') }}?v=1">
    <title>Appointments List</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h2>Appointments List</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Service</th>
                <th>Appointment Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="appointmentsTableBody">
            <tr>
                <td colspan="6">Loading appointments...</td>
            </tr>
        </tbody>
    </table>

    <script>
        async function fetchAppointments() {
            try {
                const response = await fetch('/api/appointments');
                const result = await response.json();
                
                const tbody = document.getElementById('appointmentsTableBody');
                tbody.innerHTML = '';

                if (result.data && result.data.length > 0) {
                    result.data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.id}</td>
                            <td>${item.patient ? item.patient.name : 'Patient #' + item.patient_id}</td>
                            <td>${item.doctor ? item.doctor.name : 'Doctor #' + item.doctor_id}</td>
                            <td>${item.service ? item.service.name : 'Service #' + item.service_id}</td>
                            <td>${new Date(item.appointment_date).toLocaleString()}</td>
                            <td class="status-${item.status}">${item.status}</td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6">No appointments found.</td></tr>';
                }
            } catch (error) {
                console.error('Error fetching appointments:', error);
                document.getElementById('appointmentsTableBody').innerHTML = '<tr><td colspan="6" style="color:red;">Failed to load appointments.</td></tr>';
            }
        }

        // I-load ang mga appointment sa pagbukas ng page
        fetchAppointments();
    </script>
</body>
</html>
