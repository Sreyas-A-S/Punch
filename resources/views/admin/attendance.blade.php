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

    table {
        width: 100%;
        border-collapse: collapse;
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

    .pagination-wrapper {
        margin-top: 1.5rem;
        display: flex;
        justify-content: center;
    }
</style>

<div class="filter-card">
    <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">Filter Attendance</h3>
    <form action="{{ route('admin.attendance') }}" method="GET" class="filter-form" id="attendance-filter-form">
        <div>
            <label for="name">Employee Name</label>
            <input type="text" id="name" name="name" value="{{ request('name') }}" placeholder="Search by name...">
        </div>
        
        <div>
            <label for="date">Date</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}">
        </div>

        <div>
            <label for="device_sn">Device</label>
            <select id="device_sn" name="device_sn">
                <option value="">All Devices</option>
                @foreach($devices as $device)
                    <option value="{{ $device->serial_number }}" {{ request('device_sn') == $device->serial_number ? 'selected' : '' }}>
                        {{ $device->display_name }} ({{ $device->serial_number }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn" style="flex: 1;">Filter</button>
            <a href="{{ route('admin.attendance') }}" class="btn" style="background-color: var(--text-muted); text-align: center; text-decoration: none;">Clear</a>
        </div>
    </form>
</div>

<div class="card" style="overflow-x: auto;">
    <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">Attendance Logs</h3>
    <div id="attendance-table-container">
        @include('admin.partials.attendance-table')
    </div>
</div>

<script>
    const tableContainer = document.getElementById('attendance-table-container');
    const filterForm = document.getElementById('attendance-filter-form');

    function fetchAttendance(url = null) {
        if (!url) {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData).toString();
            url = `{{ route('admin.attendance') }}?${params}`;
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            tableContainer.innerHTML = html;
            attachPaginationLinks();
        })
        .catch(error => console.error('Error fetching attendance:', error));
    }

    function attachPaginationLinks() {
        const links = tableContainer.querySelectorAll('.pagination-link:not(.disabled)');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetchAttendance(this.href);
            });
        });
    }

    // Handle filter form submission
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchAttendance();
    });

    // Auto-refresh every 15 seconds
    setInterval(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || 1;
        
        if (parseInt(currentPage) === 1) {
            fetchAttendance();
        }
    }, 15000);

    // Initial attachment for existing links
    attachPaginationLinks();
</script>

@endsection
