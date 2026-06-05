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

<!-- Bottom Section: History -->
<div class="card" style="margin-top: 1.5rem;">
    <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Recent User Sync History</h3>
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
        order: [[3, 'desc']], 
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
            searchPlaceholder: "Search sync history...",
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
                updateCommandsTable();
                $('#user_pin, #user_name, #fetch_pin').val('');
            } else {
                alert('Error queuing command.');
            }
        });
    }
}

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
