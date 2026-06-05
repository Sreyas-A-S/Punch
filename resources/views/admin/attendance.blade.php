@extends('layouts.admin')

@section('content')

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
                <th>PIN</th>
                <th>Employee Name</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Device</th>
                <th>Verify Mode</th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTables will populate this -->
        </tbody>
    </table>
</div>

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
        order: [[3, 'desc']], // Default sort by Date & Time (Index 3)
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

    // Auto-refresh every 5 seconds for the first page
    setInterval(() => {
        if (table.page() === 0) {
            table.ajax.reload(null, false); // false = stay on current page
        }
    }, 5000);
});
</script>

@endsection
