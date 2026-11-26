@extends('admin.layouts.master')

@section('title', 'Subscription Renewals Due')
@section('page-title', 'Subscription Renewals Due')

@section('content')
<div class="container-fluid">
    {{-- Alerts --}}

    {{-- Metrics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <span class="text-muted small fw-semibold text-uppercase">Renewals Due (Next 14 Days)</span>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <h3 class="fw-bold mb-0 text-warning">{{ $dueWithinCount }}</h3>
                    <div class="bg-warning-subtle text-warning rounded-circle p-2">
                        <i class="fas fa-calendar-check fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <span class="text-muted small fw-semibold text-uppercase">Overdue Renewals</span>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <h3 class="fw-bold mb-0 text-danger">{{ $dueOverdueCount }}</h3>
                    <div class="bg-danger-subtle text-danger rounded-circle p-2">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                </div>
                <small class="text-muted mt-1">Requires customer outreach</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-6 text-end d-flex align-items-center justify-content-end gap-2">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.orders.renewals') }}" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 fw-semibold text-muted small">Show renewals due within:</label>
                </div>
                <div class="col-auto">
                    <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="7" @selected($days === 7)>7 Days</option>
                        <option value="14" @selected($days === 14)>14 Days (Standard)</option>
                        <option value="30" @selected($days === 30)>30 Days</option>
                        <option value="60" @selected($days === 60)>60 Days</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Renewals Table --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Order #</th>
                            <th>Customer</th>
                            <th>Items / Plan</th>
                            <th>Billing Cycle</th>
                            <th>Amount</th>
                            <th>Initial Paid Date</th>
                            <th>Renewal Due Date</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($renewals as $order)
                            @php
                                $today = now()->startOfDay();
                                $dueDate = $order->renewal_due_date ? \Illuminate\Support\Carbon::parse($order->renewal_due_date)->startOfDay() : null;
                                $diff = $dueDate ? $today->diffInDays($dueDate, false) : null;
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-decoration-none">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $order->customer_name }}</div>
                                    <small class="text-muted d-block">{{ $order->customer_email }}</small>
                                    <small class="text-muted">{{ $order->customer_phone }}</small>
                                </td>
                                <td>
                                    @foreach($order->items as $item)
                                        <div class="small fw-semibold text-dark">{{ $item->title }}</div>
                                        <div class="small text-muted">{{ $item->option_name }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill text-capitalize">
                                        {{ $order->billing_term }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">
                                    {{ $order->currency }} {{ number_format($order->total, 2) }}
                                </td>
                                <td class="small text-muted">
                                    {{ $order->paid_at ? $order->paid_at->format('M d, Y') : ($order->created_at->format('M d, Y')) }}
                                </td>
                                <td>
                                    @if($dueDate)
                                        <span class="fw-semibold {{ $diff < 0 ? 'text-danger' : ($diff <= 7 ? 'text-warning' : 'text-dark') }}">
                                            {{ $dueDate->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($diff === null)
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">Pending</span>
                                    @elseif($diff < 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                                            Overdue by {{ abs($diff) }}d
                                        </span>
                                    @elseif($diff === 0)
                                        <span class="badge bg-danger text-white rounded-pill">
                                            Due Today
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">
                                            Due in {{ $diff }}d
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary" title="View Order">
                                        <i class="fas fa-eye"></i> Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                                    No subscription renewals due within the selected period ({{ $days }} days).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($renewals->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $renewals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
