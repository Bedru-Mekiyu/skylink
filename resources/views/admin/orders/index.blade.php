@extends('admin.layouts.master')

@section('title', 'Orders & Online Payments')
@section('page-title', 'Orders & Online Payments')

@section('content')
<div class="container-fluid">
    {{-- Alerts --}}

    {{-- Metrics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <span class="text-muted small fw-semibold text-uppercase">Total Orders</span>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalOrdersCount }}</h3>
                    <div class="bg-primary-subtle text-primary rounded-circle p-2">
                        <i class="fas fa-shopping-bag fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <span class="text-muted small fw-semibold text-uppercase">Paid Revenue (Chapa)</span>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <h3 class="fw-bold mb-0 text-success">ETB {{ number_format($paidRevenue, 2) }}</h3>
                    <div class="bg-success-subtle text-success rounded-circle p-2">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                </div>
                <small class="text-muted mt-1">{{ $paidOrdersCount }} orders settled</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <span class="text-muted small fw-semibold text-uppercase">Pending Payments</span>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <h3 class="fw-bold mb-0 text-warning">{{ $pendingOrdersCount }}</h3>
                    <div class="bg-warning-subtle text-warning rounded-circle p-2">
                        <i class="fas fa-hourglass-half fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <span class="text-muted small fw-semibold text-uppercase">Failed / Cancelled</span>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <h3 class="fw-bold mb-0 text-danger">{{ $failedOrdersCount }}</h3>
                    <div class="bg-danger-subtle text-danger rounded-circle p-2">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar & Export --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search order #, customer, email..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <select name="payment_method" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Payment Methods</option>
                        <option value="chapa" @selected(request('payment_method') === 'chapa')>Chapa Gateway</option>
                        <option value="request_only" @selected(request('payment_method') === 'request_only')>Quote Request</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                    @if(request()->hasAny(['search', 'status', 'payment_method']))
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset filters">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                    <a href="{{ route('admin.orders.renewals') }}" class="btn btn-sm btn-outline-warning" title="View Renewals Due">
                        <i class="fas fa-calendar-check me-1"></i> Renewals
                    </a>
                    @if(auth()->user()->hasRole('finance'))
<a href="{{ route('admin.orders.export', request()->query()) }}" class="btn btn-sm btn-outline-success" title="Export CSV">
                        <i class="fas fa-file-csv"></i>
                    </a>
@endif
                </div>
            </form>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Order Number</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Billing Term</th>
                        <th class="text-end">Total (ETB)</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold font-monospace text-decoration-none">
                                    {{ $order->order_number }}
                                </a>
                                @if($order->payment_method === 'chapa')
                                    <span class="badge bg-success-subtle text-success small d-block mt-1" style="width: fit-content; font-size: 10px;">Chapa</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $order->created_at->format('M d, Y') }}<br>
                                <span style="font-size: 11px;">{{ $order->created_at->format('H:i') }}</span>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $order->customer_name }}</strong>
                                <small class="text-muted d-block">{{ $order->customer_email }}</small>
                                <small class="text-muted d-block">{{ $order->customer_phone }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}</span>
                            </td>
                            <td>
                                <span class="text-muted small">{{ ucfirst($order->billing_term ?: 'one-time') }}</span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                {{ $order->currency }} {{ number_format((float) $order->total, 2) }}
                            </td>
                            <td class="text-center">
                                @if($order->status === 'paid')
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Paid</span>
                                @elseif($order->status === 'pending')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>
                                @elseif($order->status === 'failed')
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Failed</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary" title="View Order Details">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3 d-block text-secondary opacity-50"></i>
                                No orders found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
