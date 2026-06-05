@extends('layouts.admin')

@section('content')

<style>
    .controls-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 1.5rem;
        align-items: start;
    }

    @media (max-width: 900px) {
        .controls-grid {
            grid-template-columns: 1fr;
        }
    }

    .command-btn {
        display: block;
        width: 100%;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 500;
    }

    .command-btn:hover {
        border-color: var(--primary-color);
        background-color: rgba(59, 130, 246, 0.05);
    }

    .command-btn span {
        display: block;
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 400;
        margin-top: 0.25rem;
    }

    .status-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-pending { background: #FEF3C7; color: #D97706; }
    .status-sent { background: #DBEAFE; color: #2563EB; }
    .status-completed { background: #D1FAE5; color: #059669; }
    .status-error { background: #FEE2E2; color: #DC2626; }
</style>

<div class="controls-grid">
    <!-- Command Trigger Panel -->
    <div class="card">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Device Command Center</h3>
        
        <form action="{{ route('admin.commands.send') }}" method="POST" id="command-form">
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
                <label>Quick Commands</label>
                
                <button type="button" class="command-btn" onclick="setCommand('DATA QUERY ATTLOG')">
                    Sync All Attendance
                    <span>Force device to upload all stored punch records.</span>
                </button>

                <button type="button" class="command-btn" onclick="setCommand('DATA QUERY USERINFO')">
                    Sync All User Names
                    <span>Update employee names from device to server.</span>
                </button>

                <button type="button" class="command-btn" onclick="setCommand('REBOOT')">
                    Reboot Device
                    <span>Restart the biometric machine remotely.</span>
                </button>

                <button type="button" class="command-btn" onclick="setCommand('CHECK')">
                    Sync Date & Time
                    <span>Update device clock to match server time.</span>
                </button>
            </div>

            <div style="margin-top: 1.5rem;">
                <label for="custom_command">Custom Command</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="custom_command" name="command" placeholder="Enter command string...">
                    <button type="submit" class="btn" style="white-space: nowrap;">Send</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Recent Command History -->
    <div class="card">
        <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">Recent Commands</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; font-size: 0.875rem;" id="commands-table">
                <thead>
                    <tr>
                        <th>Device SN</th>
                        <th>Command</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody id="commands-tbody">
                    @foreach($recentCommands as $cmd)
                        <tr>
                            <td style="font-family: monospace;">{{ $cmd->device_sn }}</td>
                            <td><code>{{ $cmd->command }}</code></td>
                            <td>
                                <span class="status-badge status-{{ $cmd->status }}">
                                    {{ $cmd->status }}
                                </span>
                            </td>
                            <td style="color: var(--text-muted);">{{ $cmd->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function setCommand(cmd) {
    const device = document.getElementById('device_sn').value;
    if (!device) {
        alert('Please select a device first.');
        return;
    }
    
    if (confirm('Queue command "' + cmd + '" for device ' + device + '?')) {
        $.post("{{ route('admin.commands.send') }}", {
            _token: "{{ csrf_token() }}",
            device_sn: device,
            command: cmd
        }, function(response) {
            if(response.success) {
                updateCommandsTable();
                $('#custom_command').val('');
            } else {
                alert('Error queuing command.');
            }
        });
    }
}

// Custom command manual submission
$('#command-form').on('submit', function(e) {
    e.preventDefault();
    const cmd = $('#custom_command').val();
    if(cmd) setCommand(cmd);
});

function updateCommandsTable() {
    $.get("{{ route('admin.commands.recent') }}", function(data) {
        let html = '';
        data.forEach(function(cmd) {
            html += `<tr>
                <td style="font-family: monospace;">${cmd.device_sn}</td>
                <td><code>${cmd.command}</code></td>
                <td>
                    <span class="status-badge status-${cmd.status}">
                        ${cmd.status}
                    </span>
                </td>
                <td style="color: var(--text-muted);">${cmd.time}</td>
            </tr>`;
        });
        $('#commands-tbody').html(html);
    });
}

// Poll every 5 seconds for status updates
setInterval(updateCommandsTable, 5000);
</script>

@endsection
