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
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                    No attendance records found matching your criteria.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrapper" style="display: flex; justify-content: space-between; align-items: center;">
    <span style="color: var(--text-muted); font-size: 0.875rem;">Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() ?? 0 }} results</span>
    <div style="display: flex; gap: 0.5rem;" id="pagination-links">
        @if ($logs->onFirstPage())
            <span class="btn pagination-link disabled" style="background-color: var(--border-color); color: var(--text-muted); cursor: not-allowed; padding: 0.5rem 1rem;">Previous</span>
        @else
            <a href="{{ $logs->previousPageUrl() }}" class="btn pagination-link" style="padding: 0.5rem 1rem; text-decoration: none;">Previous</a>
        @endif

        @if ($logs->hasMorePages())
            <a href="{{ $logs->nextPageUrl() }}" class="btn pagination-link" style="padding: 0.5rem 1rem; text-decoration: none;">Next</a>
        @else
            <span class="btn pagination-link disabled" style="background-color: var(--border-color); color: var(--text-muted); cursor: not-allowed; padding: 0.5rem 1rem;">Next</span>
        @endif
    </div>
</div>
