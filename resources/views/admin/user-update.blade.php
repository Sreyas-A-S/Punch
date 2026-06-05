@extends('layouts.admin')

@section('content')

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">User Name Sync</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.875rem;">
            Use this tool to manually push a name update to a specific biometric device. 
            This is useful when a user's name is not showing correctly on the machine's display.
        </p>

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

            <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(67, 24, 255, 0.05); border-radius: 12px; border: 1px solid var(--primary-color);">
                <label style="color: var(--primary-color); font-weight: 700;">PUSH: Update Device</label>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-top: 0.5rem;">
                    <div>
                        <label for="user_pin">Employee PIN</label>
                        <input type="text" id="user_pin" placeholder="e.g. 101" style="margin-bottom: 0;">
                    </div>
                    <div>
                        <label for="user_name">Full Name</label>
                        <input type="text" id="user_name" placeholder="e.g. John Doe" style="margin-bottom: 0;">
                    </div>
                </div>
                <button type="button" class="btn" onclick="pushUserInfo()" style="width: 100%; margin-top: 1rem;">
                    Push Name to Device
                </button>
            </div>

            <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 12px; border: 1px solid #10B981;">
                <label style="color: #10B981; font-weight: 700;">PULL: Fetch from Device</label>
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; margin-top: 0.5rem;">
                    <input type="text" id="fetch_pin" placeholder="Enter PIN (optional)" style="margin-bottom: 0;">
                    <button type="button" class="btn" onclick="fetchUserInfo()" style="background-color: #10B981; white-space: nowrap;">
                        Fetch by PIN
                    </button>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <span style="color: var(--text-muted); font-size: 0.75rem;">— OR —</span>
                </div>
                <button type="button" class="btn" onclick="fetchAllUsers()" style="width: 100%; margin-top: 1rem; background-color: #059669;">
                    Fetch ALL Users from Device
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top: 2rem;">
        <h3 style="margin-bottom: 1rem; font-size: 1rem;">How it works</h3>
        <ul style="color: var(--text-muted); font-size: 0.875rem; padding-left: 1.25rem; line-height: 1.6;">
            <li><strong>Push Name:</strong> Queues a <code>SET USERINFO</code> command. Sets the name in the machine's memory.</li>
            <li><strong>Fetch by PIN:</strong> Queues a <code>DATA QUERY USERINFO PIN=x</code> command. Server asks for one user.</li>
            <li><strong>Fetch ALL:</strong> Queues a <code>DATA QUERY USERINFO</code> command. Server asks for the entire user list.</li>
            <li>Devices pull these commands on their next check-in (usually within 30s).</li>
        </ul>
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
