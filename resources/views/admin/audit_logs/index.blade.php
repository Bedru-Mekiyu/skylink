@extends('admin.layouts.master')

@section('page-title', 'Admin Audit Logs')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1">Audit Trail & Activity Logs</h4>
        <p class="text-muted small mb-0">Immutable records of catalog modifications, price updates, staff role changes, and administrative actions.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-3 align-items-center mb-4">
            <div class="col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by action, user, IP, or target..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-secondary w-100">Filter Logs</button>
            </div>
            @if(request('search'))
                <div class="col-md-2">
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-link text-muted">Clear</a>
                </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details / Changes</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap text-muted">{{ $log->created_at?->format('M d, Y H:i:s') ?? 'N/A' }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $log->user?->name ?? 'System' }}</div>
                                <span class="text-muted small">{{ $log->user?->email }}</span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td>
                                @if($log->target_type)
                                    <span class="text-dark fw-semibold">{{ class_basename($log->target_type) }} #{{ $log->target_id }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($log->changes))
                                    <pre class="bg-light p-2 rounded mb-0 text-wrap" style="max-height: 80px; overflow-y: auto; font-size: 11px;">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small text-nowrap">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No audit log entries recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
