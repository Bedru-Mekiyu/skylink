@extends('admin.layouts.master')

@section('page-title', 'Staff Accounts & Roles')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Staff Management</h4>
            <p class="text-muted small mb-0">Manage internal team accounts and role-based permissions (RBAC).</p>
        </div>
        <a href="{{ route('admin.staff.create') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-user-plus me-1"></i> Add Staff Account
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ route('admin.staff.index') }}" class="row g-3 align-items-center mb-3">
            <div class="col-md-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <select name="role" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}" {{ request('role') == $r->name ? 'selected' : '' }}>{{ $r->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Assigned Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                        @php
                            $role = $staff->roles->first();
                            $badgeClass = match($role?->name) {
                                'super_admin' => 'bg-danger-subtle text-danger border-danger-subtle',
                                'content_editor' => 'bg-info-subtle text-info border-info-subtle',
                                'sales' => 'bg-success-subtle text-success border-success-subtle',
                                'finance' => 'bg-warning-subtle text-warning border-warning-subtle',
                                default => 'bg-secondary-subtle text-secondary'
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $staff->name }}</div>
                                @if($staff->id === Auth::id())
                                    <span class="badge bg-light text-muted border" style="font-size: 11px;">You</span>
                                @endif
                            </td>
                            <td>{{ $staff->email }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }} border rounded-pill px-2 py-1">
                                    {{ $role?->display_name ?? ($staff->is_admin ? 'Super Administrator' : 'Staff') }}
                                </span>
                            </td>
                            <td>
                                @if($staff->is_active ?? true)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        <i class="fas fa-check-circle me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                        <i class="fas fa-ban me-1"></i> Deactivated
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $staff->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit Staff">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                @if($staff->id !== Auth::id())
                                    <form method="POST" action="{{ route('admin.staff.toggle-active', $staff) }}" class="d-inline" data-sky-confirm="{{ ($staff->is_active ?? true) ? 'They will no longer be able to sign in.' : 'They will be able to sign in again.' }}" data-sky-title="{{ ($staff->is_active ?? true) ? 'Deactivate this account?' : 'Activate this account?' }}" data-sky-ok="{{ ($staff->is_active ?? true) ? 'Deactivate' : 'Activate' }}" data-sky-variant="{{ ($staff->is_active ?? true) ? 'danger' : 'primary' }}">
                                        @csrf
                                        @if($staff->is_active ?? true)
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate Staff Account">
                                                <i class="fas fa-user-slash me-1"></i> Deactivate
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate Staff Account">
                                                <i class="fas fa-user-check me-1"></i> Activate
                                            </button>
                                        @endif
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No staff accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($staffMembers->hasPages())
            <div class="mt-4">
                {{ $staffMembers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
