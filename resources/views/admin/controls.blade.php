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

    /* Base Styles */
    .btn-sync-logs { background-color: rgba(59, 130, 246, 0.1); color: #3B82F6; border-color: rgba(59, 130, 246, 0.2); }
    .btn-sync-users { background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-color: rgba(16, 185, 129, 0.2); }
    .btn-reboot { background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-color: rgba(239, 68, 68, 0.2); }
    .btn-sync-time { background-color: rgba(245, 158, 11, 0.1); color: #F59E0B; border-color: rgba(245, 158, 11, 0.2); }

    /* Hover Styles */
    .btn-sync-logs:hover { background-color: #3B82F6; border-color: #3B82F6; }
    .btn-sync-users:hover { background-color: #10B981; border-color: #10B981; }
    .btn-reboot:hover { background-color: #EF4444; border-color: #EF4444; }
    .btn-sync-time:hover { background-color: #F59E0B; border-color: #F59E0B; }
    .btn-info-cmd { background-color: rgba(99, 102, 241, 0.1); color: #6366F1; border-color: rgba(99, 102, 241, 0.2); }
    .btn-info-cmd:hover { background-color: #6366F1; border-color: #6366F1; }
    .btn-clear-logs { background-color: rgba(251, 191, 36, 0.1); color: #D97706; border-color: rgba(251, 191, 36, 0.2); }
    .btn-clear-logs:hover { background-color: #FBBF24; border-color: #FBBF24; }

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

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: var(--card-bg);
        margin: 10% auto;
        padding: 2rem;
        border-radius: 16px;
        width: 80%;
        max-width: 600px;
        position: relative;
        box-shadow: var(--shadow);
    }

    .close-modal {
        position: absolute;
        right: 1.5rem;
        top: 1rem;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-muted);
    }

    pre#response-viewer {
        background: var(--bg-color);
        padding: 1rem;
        border-radius: 8px;
        overflow-x: auto;
        overflow-y: auto;
        max-height: 300px;
        font-size: 0.85rem;
        margin-top: 1rem;
        border: 1px solid var(--border-color);
        white-space: pre-wrap;
    }
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
                    <button type="button" class="command-btn btn-sync-logs" onclick="setCommand('DATA QUERY ATTLOG', 'Sync Logs')" title="Force device to upload all stored punch records">
                        Sync Logs
                    </button>

                    <button type="button" class="command-btn btn-sync-users" onclick="setCommand('DATA QUERY USERINFO', 'Sync Users')" title="Update employee names from device to server">
                        Sync Users
                    </button>

                    <button type="button" class="command-btn btn-reboot" onclick="setCommand('REBOOT', 'Reboot Device')" title="Restart the biometric machine remotely">
                        Reboot
                    </button>

                    <button type="button" class="command-btn btn-sync-time" onclick="setCommand('CHECK', 'Sync Time')" title="Update device clock to match server time">
                        Sync Time
                    </button>

                    <button type="button" class="command-btn btn-info-cmd" onclick="setCommand('INFO', 'System Info')" title="Request device system specifications and status">
                        System Info
                    </button>

                    <button type="button" class="command-btn btn-clear-logs" onclick="setCommand('CLEAR LOG', 'Clear Logs')" title="Permanently delete all attendance logs stored on the device">
                        Clear Logs
                    </button>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label for="custom_command">Custom Command</label>
                <div style="display: flex; gap: 0.5rem; align-items: stretch;">
                    <input type="text" id="custom_command" name="command" placeholder="Enter command string..." style="margin-bottom: 0; flex: 1;">
                    <button type="submit" class="btn" style="white-space: nowrap; height: 100%;">Send</button>
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
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody id="commands-tbody">
                    <!-- DataTables will populate this -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div id="responseModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 style="margin-bottom: 0.5rem;">Command Output</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;" id="modal-command-info"></p>
        <pre id="response-viewer"></pre>
    </div>
</div>

<script>
let commandsTable;

$(document).ready(function() {
    // Initial data from server
    const initialData = @json($recentCommands);

    commandsTable = $('#commands-table').DataTable({
        data: initialData,
        order: [[3, 'desc']], // Sort by the 4th column (Time/Timestamp)
        pageLength: 10,
        columns: [
            { data: 'device_sn', render: (data) => `<span style="font-family: monospace;">${data}</span>` },
            { 
                data: 'command', 
                render: function(data, type, row) {
                    return `<div><strong>${row.friendly_name}</strong><br><code style="color: var(--text-muted); font-size: 0.75rem;">${data}</code></div>`;
                }
            },
            { data: 'status', render: (data) => `<span class="status-badge status-${data}">${data}</span>` },
            { 
                data: 'time',
                render: function(data, type, row) {
                    if (type === 'sort') return row.timestamp;
                    return `<span style="color: var(--text-muted);">${data}</span>`;
                }
            },
            {
                data: 'response',
                orderable: false,
                render: function(data, type, row) {
                    if (!data) return '<span style="color: var(--text-muted); font-style: italic;">No output</span>';
                    return `<button class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;" onclick="showResponse('${row.command}', \`${data}\`)">View</button>`;
                }
            }
        ],
        language: {
            searchPlaceholder: "Search commands...",
            search: ""
        }
    });

    // Close modal logic
    $('.close-modal').on('click', function() {
        $('#responseModal').hide();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is('#responseModal')) {
            $('#responseModal').hide();
        }
    });
});

function showResponse(command, response) {
    $('#modal-command-info').text('Output for: ' + command);
    $('#response-viewer').text(response);
    $('#responseModal').show();
}

function setCommand(cmd, label = null) {
    const device = document.getElementById('device_sn').value;
    if (!device) {
        alert('Please select a device first.');
        return;
    }
    
    const displayCmd = label ? `"${label}" (${cmd})` : `"${cmd}"`;
    if (confirm('Queue command ' + displayCmd + ' for device ' + device + '?')) {
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
