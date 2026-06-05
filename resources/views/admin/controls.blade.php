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

    .quick-commands-grid {
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
        background-color: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-color);
    }

    .command-btn:hover {
        transform: translateY(-1px);
        color: white;
    }

    .btn-sync-logs:hover { background-color: #3B82F6; border-color: #3B82F6; }
    .btn-sync-users:hover { background-color: #10B981; border-color: #10B981; }
    .btn-reboot:hover { background-color: #EF4444; border-color: #EF4444; }
    .btn-sync-time:hover { background-color: #F59E0B; border-color: #F59E0B; }

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
                
                <div class="quick-commands-grid">
                    <button type="button" class="command-btn btn-sync-logs" onclick="setCommand('DATA QUERY ATTLOG')" title="Force device to upload all stored punch records">
                        Sync Logs
                    </button>

                    <button type="button" class="command-btn btn-sync-users" onclick="setCommand('DATA QUERY USERINFO')" title="Update employee names from device to server">
                        Sync Users
                    </button>

                    <button type="button" class="command-btn btn-reboot" onclick="setCommand('REBOOT')" title="Restart the biometric machine remotely">
                        Reboot
                    </button>

                    <button type="button" class="command-btn btn-sync-time" onclick="setCommand('CHECK')" title="Update device clock to match server time">
                        Sync Time
                    </button>
                </div>
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
                            <td data-order="{{ $cmd->created_at->toDateTimeString() }}" style="color: var(--text-muted);">
                                {{ $cmd->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let commandsTable;

$(document).ready(function() {
    commandsTable = $('#commands-table').DataTable({
        order: [[3, 'desc']], // Sort by the 4th column (Time/Timestamp)
        pageLength: 10,
        columns: [
            { data: 'device_sn', render: (data) => `<span style="font-family: monospace;">${data}</span>` },
            { data: 'command', render: (data) => `<code>${data}</code>` },
            { data: 'status', render: (data) => `<span class="status-badge status-${data}">${data}</span>` },
            { 
                data: 'time',
                render: function(data, type, row) {
                    if (type === 'sort') return row.timestamp;
                    return `<span style="color: var(--text-muted);">${data}</span>`;
                }
            }
        ],
        language: {
            searchPlaceholder: "Search commands...",
            search: ""
        }
    });
});

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
        if (!commandsTable) return;
        
        commandsTable.clear();
        commandsTable.rows.add(data);
        commandsTable.draw(false);
    });
}

// Poll every 5 seconds for status updates
setInterval(updateCommandsTable, 5000);
</script>

@endsection
