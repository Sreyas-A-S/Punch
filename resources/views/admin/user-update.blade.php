@extends('layouts.admin')

@section('content')

<style>
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

    .action-group {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px dashed var(--border-color);
    }
</style>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">User Synchronization</h3>
        
        <form id="user-sync-form">
            @csrf
            <div>
                <label for="device_sn">Target Device</label>
                <select id="device_sn" name="device_sn" required>
                    <option value="">Select a device...</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->serial_number }}">{{ $device->display_name }} ({{ $device->serial_number }})</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-top: 1.5rem;">
                <label>Quick Sync Actions</label>
                <div class="user-sync-grid">
                    <button type="button" class="command-btn btn-fetch-all" onclick="fetchAllUsers()" title="Fetch all users from device and update server database">
                        Fetch All Users
                    </button>
                    <button type="button" class="command-btn btn-push-all" onclick="pushAllUsers()" title="Push all employee names from server to device">
                        Push All Names
                    </button>
                </div>
            </div>

            <div class="action-group">
                <label>Update Specific User</label>
                <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.5rem; align-items: stretch;">
                    <input type="text" id="user_pin" placeholder="PIN" style="margin-bottom: 0;">
                    <input type="text" id="user_name" placeholder="New Name" style="margin-bottom: 0;">
                    <button type="button" class="btn" onclick="pushUserInfo()" style="white-space: nowrap; height: 100%;">Update</button>
                </div>
                <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                    Sets a specific name for the given PIN on the machine.
                </small>
            </div>

            <div class="action-group">
                <label>Fetch Specific User</label>
                <div style="display: flex; gap: 0.5rem; align-items: stretch;">
                    <input type="text" id="fetch_pin" placeholder="Enter PIN to fetch details..." style="margin-bottom: 0; flex: 1;">
                    <button type="button" class="btn" onclick="fetchUserInfo()" style="white-space: nowrap; height: 100%; background-color: #10B981;">Fetch</button>
                </div>
            </div>
        </form>
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

function pushAllUsers() {
    alert('This feature will be implemented soon to push all database employees to the machine.');
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
