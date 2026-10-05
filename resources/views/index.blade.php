<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Clinic Appointments
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                        {{ session('status') }}
                    </div>
                @endif

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">Patient</th>
                            <th class="p-2">Date</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr class="border-b">
                                <td class="p-2">{{ $appointment->patient->name ?? 'N/A' }}</td>
                                <td class="p-2">{{ $appointment->appointment_date }}</td>
                                <td class="p-2">
                                    <span class="px-2 py-1 text-xs rounded {{ $appointment->status === 'confirmed' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' }}">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                </td>
                                <td class="p-2">
                                    @if($appointment->status !== 'confirmed')
                                        <form action="{{ route('appointments.confirm', $appointment->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                                Confirm & Send Email
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-500 text-sm">Confirmed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500">No appointments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</x-app-layout>
<!-- Sa loob ng index.blade.php -->
<div class="card">
    <h3>Your Clinic Appointments</h3>
    <table>
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Service</th>
                <th>Date & Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="appointmentsTableBody">
            <tr>
                <td colspan="4">Loading appointments...</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function () {
    const tbody = document.getElementById('appointmentsTableBody');
    if (!tbody) return;

    try {
        const response = await fetch('/api/appointments');
        const result = await response.json();
        const appointments = result.data || result;

        tbody.innerHTML = '';

        if (appointments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No appointments found.</td></tr>';
            return;
        }

        appointments.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.doctor ? item.doctor.name : 'Doctor #' + item.doctor_id}</td>
                <td>${item.service ? item.service.name : 'Service #' + item.service_id}</td>
                <td>${new Date(item.appointment_date).toLocaleString()}</td>
                <td><span class="badge">${item.status}</span></td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error('Error fetching appointments:', error);
        tbody.innerHTML = '<tr><td colspan="4" style="color: red; text-align: center;">Failed to load appointments.</td></tr>';
    }
});
</script>