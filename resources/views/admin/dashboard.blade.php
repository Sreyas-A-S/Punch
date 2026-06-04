@extends('layouts.admin')

@section('content')

<style>
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        padding: 1.5rem;
        border-radius: 16px;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .stat-card h3 {
        color: var(--text-muted);
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card p {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .grid-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
        align-items: start;
    }

    @media (max-width: 900px) {
        .grid-layout {
            grid-template-columns: 1fr;
        }
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.875rem;
    }

    th {
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        background-color: rgba(0,0,0,0.02);
    }

    [data-theme="dark"] th {
        background-color: rgba(255,255,255,0.02);
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-working {
        background-color: rgba(16, 185, 129, 0.1);
        color: #10B981;
    }

    .status-offline {
        background-color: rgba(239, 68, 68, 0.1);
        color: #EF4444;
    }

</style>

@if(session('success'))
    <div class="alert" style="background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-color: #10B981;">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert" style="background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-color: #EF4444;">
        <ul style="list-style: none; padding: 0; margin: 0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>Total Devices</h3>
        <p id="stat-total">{{ $totalDevices }}</p>
    </div>
    <div class="stat-card">
        <h3>Working Devices</h3>
        <p id="stat-working" style="color: #10B981;">{{ $workingDevices }}</p>
    </div>
    <div class="stat-card">
        <h3>Offline Devices</h3>
        <p id="stat-offline" style="color: #EF4444;">{{ $totalDevices - $workingDevices }}</p>
    </div>
</div>

<div class="grid-layout">
    <!-- Add Device Form -->
    <div class="card">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Add New SSL Device</h3>
        <form action="{{ route('admin.devices.store') }}" method="POST">
            @csrf
            <div>
                <label for="display_name">Display Name</label>
                <input type="text" id="display_name" name="display_name" required placeholder="e.g. Front Door Scanner">
            </div>
            
            <div>
                <label for="serial_number">Serial Number</label>
                <input type="text" id="serial_number" name="serial_number" required placeholder="e.g. SN123456789">
            </div>

            <button type="submit" class="btn" style="width: 100%;">Add Device</button>
        </form>
    </div>

    <!-- Devices List -->
    <div class="card">
        <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">SSL Devices Master List</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Sl No</th>
                        <th>Display Name</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Added On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td style="color: var(--text-muted);">{{ $loop->iteration }}</td>
                            <td style="font-weight: 500;">{{ $device->display_name }}</td>
                            <td style="color: var(--text-muted); font-family: monospace;">{{ $device->serial_number }}</td>
                            <td id="status-{{ $device->serial_number }}">
                                @if($device->status)
                                    <span class="status-badge status-working">Working</span>
                                @else
                                    <span class="status-badge status-offline">Offline</span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted);">{{ $device->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.devices.edit', $device->id) }}" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.75rem; text-decoration: none; background-color: var(--primary-color);">Edit</a>
                                    <form action="{{ route('admin.devices.destroy', $device->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this device?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.75rem; background-color: #EF4444;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No devices found. Add your first device to get started!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updateDeviceStatuses() {
        fetch('{{ route('admin.devices.status') }}')
            .then(response => response.json())
            .then(data => {
                // Update Top Statistics
                document.getElementById('stat-total').innerText = data.stats.total;
                document.getElementById('stat-working').innerText = data.stats.working;
                document.getElementById('stat-offline').innerText = data.stats.offline;

                // Update Individual Rows
                data.devices.forEach(device => {
                    const statusCell = document.getElementById('status-' + device.serial_number);
                    if (statusCell) {
                        if (device.status) {
                            statusCell.innerHTML = '<span class="status-badge status-working">Working</span>';
                        } else {
                            statusCell.innerHTML = '<span class="status-badge status-offline">Offline</span>';
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching device status:', error));
    }

    // Poll every 10 seconds
    setInterval(updateDeviceStatuses, 10000);
</script>

@endsection
