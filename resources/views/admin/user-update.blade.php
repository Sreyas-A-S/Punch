@extends('layouts.admin')

@section('content')

<style>
    .sync-layout-grid {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 1.5rem;
        align-items: stretch;
    }

    @media (max-width: 1000px) {
        .sync-layout-grid {
            grid-template-columns: 1fr;
        }
    }

    .user-sync-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .command-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.85rem 0.5rem;
        border: 1px solid transparent;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .command-btn:hover {
        transform: translateY(-1px);
        color: white !important;
    }

    /* Action Styles */
    .btn-fetch-all { background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-color: rgba(16, 185, 129, 0.2); }
    .btn-fetch-all:hover { background-color: #10B981; border-color: #10B981; }
    
    .btn-push-all { background-color: rgba(59, 130, 246, 0.1); color: #3B82F6; border-color: rgba(59, 130, 246, 0.2); }
    .btn-push-all:hover { background-color: #3B82F6; border-color: #3B82F6; }
</style>

<div class="sync-layout-grid">
    <!-- Left Column: Device & Quick Sync -->
    <div class="card">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Target & Quick Actions</h3>
        
        <form id="user-sync-form">
            @csrf
            <div>
                <label for="device_sn">Select Biometric Device</label>
                <select id="device_sn" name="device_sn" required>
                    <option value="">Select a device...</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->serial_number }}">{{ $device->display_name }} ({{ $device->serial_number }})</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-top: 2rem;">
                <label>Bulk Sync Commands</label>
                <div class="user-sync-grid">
                    <button type="button" class="command-btn btn-fetch-all" onclick="fetchAllUsers()" title="Fetch all users from device and update server database" style="grid-column: span 2;">
                        Fetch All Users from Machine
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Right Column: Specific User Sync -->
    <div class="card">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Individual Synchronization</h3>
        
        <div>
            <label>Update Name on Device</label>
            <div style="display: grid; grid-template-columns: 1fr 2.5fr auto; gap: 0.5rem; align-items: stretch;">
                <input type="text" id="user_pin" placeholder="PIN" style="margin-bottom: 0;">
                <input type="text" id="user_name" placeholder="Full Name" style="margin-bottom: 0;">
                <button type="button" class="btn" onclick="pushUserInfo()" style="white-space: nowrap; height: 100%;">Update</button>
            </div>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                Pushes the given name for this PIN to the machine's local database.
            </small>
        </div>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px dashed var(--border-color);">
            <label>Fetch User Details</label>
            <div style="display: flex; gap: 0.5rem; align-items: stretch;">
                <input type="text" id="fetch_pin" placeholder="Enter Employee PIN to fetch..." style="margin-bottom: 0; flex: 1;">
                <button type="button" class="btn" onclick="fetchUserInfo()" style="white-space: nowrap; height: 100%; background-color: #10B981;">Fetch</button>
            </div>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                Retrieves the current information stored on the device for this PIN.
            </small>
        </div>
    </div>
</div>

<script>
function pushUserInfo() {
    const device = $('#device_sn').val();
    const pin = $('#user_pin').val();
    const name = $('#user_name').val();
    
    if (!device) return alert('Please select a device.');
    if (!pin || !name) return alert('Enter PIN and Name.');

    const cmd = `SET USERINFO PIN=${pin} Name=${name}`;
    sendUserCommand(device, cmd, `Push name "${name}" to PIN ${pin}`);
}

function fetchUserInfo() {
    const device = $('#device_sn').val();
    const pin = $('#fetch_pin').val();
    
    if (!device) return alert('Please select a device.');
    if (!pin) return alert('Please enter a PIN to fetch.');

    const cmd = `DATA QUERY USERINFO PIN=${pin}`;
    sendUserCommand(device, cmd, `Fetch details for PIN ${pin}`);
}

function fetchAllUsers() {
    const device = $('#device_sn').val();
    if (!device) return alert('Please select a device.');

    const cmd = `DATA QUERY USERINFO`;
    sendUserCommand(device, cmd, `Fetch ALL users from machine`);
}

function sendUserCommand(device, cmd, label) {
    if (confirm(`${label} on device ${device}?`)) {
        $.post("{{ route('admin.commands.send') }}", {
            _token: "{{ csrf_token() }}",
            device_sn: device,
            command: cmd
        }, function(response) {
            if(response.success) {
                alert('Command successfully queued!');
                $('#user_pin, #user_name, #fetch_pin').val('');
            } else {
                alert('Error queuing command.');
            }
        });
    }
}
</script>

@endsection
