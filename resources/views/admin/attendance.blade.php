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
    <form action="{{ route('admin.attendance') }}" method="GET" class="filter-form">
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
    <table>
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
            @forelse($logs as $index => $log)
                <tr>
                    <td style="color: var(--text-muted);">{{ $loop->iteration + ($logs->currentPage() - 1) * $logs->perPage() }}</td>
                    <td style="font-weight: 500;">{{ $log->timestamp }}</td>
                    <td style="color: var(--text-muted);">{{ $log->employee_pin }}</td>
                    <td style="font-weight: 600;">{{ $log->employee_name ?? 'N/A' }}</td>
                    <td>
                        <span class="status-badge" style="background-color: rgba(59, 130, 246, 0.1); color: #3B82F6;">
                            {{ $log->status }}
                        </span>
                    </td>
                    <td style="color: var(--text-muted); font-family: monospace;">{{ $log->device_sn }}</td>
                    <td>{{ $log->verify_mode }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                        No attendance records found matching your criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrapper" style="display: flex; justify-content: space-between; align-items: center;">
        <span style="color: var(--text-muted); font-size: 0.875rem;">Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() ?? 0 }} results</span>
        <div style="display: flex; gap: 0.5rem;">
            @if ($logs->onFirstPage())
                <span class="btn" style="background-color: var(--border-color); color: var(--text-muted); cursor: not-allowed; padding: 0.5rem 1rem;">Previous</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="btn" style="padding: 0.5rem 1rem; text-decoration: none;">Previous</a>
            @endif

            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="btn" style="padding: 0.5rem 1rem; text-decoration: none;">Next</a>
            @else
                <span class="btn" style="background-color: var(--border-color); color: var(--text-muted); cursor: not-allowed; padding: 0.5rem 1rem;">Next</span>
            @endif
        </div>
    </div>
</div>

@endsection
