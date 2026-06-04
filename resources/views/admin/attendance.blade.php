@extends('layouts.admin')

@section('content')

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<style>
    .filter-card {
        background-color: var(--card-bg);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        margin-bottom: 2rem;
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .filter-form label {
        margin-bottom: 0.25rem;
    }

    .filter-form input, .filter-form select {
        margin-bottom: 0;
    }

    /* DataTable Custom Styling to match theme */
    .dataTables_wrapper {
        padding: 1rem 0;
    }

    table.dataTable {
        border-collapse: collapse !important;
        margin-top: 1rem !important;
        border-bottom: none !important;
    }

    table.dataTable thead th {
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        background-color: rgba(0,0,0,0.02);
        border-bottom: 1px solid var(--border-color) !important;
        padding: 1rem !important;
        font-size: 0.75rem;
    }

    [data-theme="dark"] table.dataTable thead th {
        background-color: rgba(255,255,255,0.02);
    }

    table.dataTable tbody td {
        padding: 1rem !important;
        border-bottom: 1px solid var(--border-color) !important;
        font-size: 0.875rem;
        color: var(--text-color);
        background-color: transparent !important;
    }

    .dataTables_filter input, .dataTables_length select {
        border: 1px solid var(--border-color) !important;
        background-color: var(--bg-color) !important;
        color: var(--text-color) !important;
        border-radius: 8px !important;
        padding: 0.4rem 0.8rem !important;
    }

    .dataTables_info {
        color: var(--text-muted) !important;
        font-size: 0.875rem !important;
        padding-top: 1.5rem !important;
    }

    .dataTables_paginate {
        padding-top: 1.5rem !important;
    }

    .dataTables_paginate .paginate_button {
        border-radius: 8px !important;
        border: 1px solid var(--border-color) !important;
        background: var(--card-bg) !important;
        color: var(--text-color) !important;
    }

    .dataTables_paginate .paginate_button.current {
        background: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color) !important;
    }
</style>

<div class="filter-card">
    <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">Filter Attendance</h3>
    <form class="filter-form" id="attendance-filter-form">
        <div>
            <label for="name">Employee Name</label>
            <input type="text" id="name" name="name" placeholder="Search by name...">
        </div>
        
        <div>
            <label for="date">Date</label>
            <input type="date" id="date" name="date">
        </div>

        <div>
            <label for="device_sn">Device</label>
            <select id="device_sn" name="device_sn">
                <option value="">All Devices</option>
                @foreach($devices as $device)
                    <option value="{{ $device->serial_number }}">
                        {{ $device->display_name }} ({{ $device->serial_number }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn" style="flex: 1;">Apply Filters</button>
            <button type="reset" id="reset-filters" class="btn" style="background-color: var(--text-muted); flex: 1;">Clear</button>
        </div>
    </form>
</div>

<div class="card" style="overflow-x: auto;">
    <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">Attendance Logs</h3>
    <table id="attendance-datatable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Sl No</th>
                <th>Date & Time</th>
                <th>PIN</th>
                <th>Employee Name</th>
                <th>Status</th>
                <th>Device SN</th>
                <th>Verify Mode</th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTables will populate this -->
        </tbody>
    </table>
</div>

<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    const table = $('#attendance-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.attendance') }}",
            data: function (d) {
                d.name = $('#name').val();
                d.date = $('#date').val();
                d.device_sn = $('#device_sn').val();
            }
        },
        columns: [
            { orderable: false },
            { orderable: true },
            { orderable: true },
            { orderable: true },
            { orderable: true },
            { orderable: true },
            { orderable: true }
        ],
        order: [[1, 'desc']], // Default sort by Date & Time
        pageLength: 20,
        language: {
            searchPlaceholder: "Search logs...",
            search: ""
        }
    });

    // Handle filter form submission
    $('#attendance-filter-form').on('submit', function(e) {
        e.preventDefault();
        table.draw();
    });

    // Handle Reset
    $('#reset-filters').on('click', function() {
        $('#attendance-filter-form')[0].reset();
        table.draw();
    });

    // Auto-refresh every 30 seconds for the first page
    setInterval(() => {
        if (table.page() === 0) {
            table.ajax.reload(null, false); // false = stay on current page
        }
    }, 30000);
});
</script>

@endsection
