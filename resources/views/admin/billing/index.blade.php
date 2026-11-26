@extends('admin.layouts.master')

@section('title', 'Recurring Plans')
@section('page-title', 'Recurring Plans')

@section('content')
<p class="text-muted small">Customers pay each period from an emailed link; nothing is charged automatically. Automatic emails need <code>RENEWAL_REMINDERS_ENABLED=true</code> and the scheduler cron.</p>

<form method="GET" class="mb-3">
    <label class="form-label small" for="status">Status</label>
    <select id="status" name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        <option value="">All</option>
        @foreach(['active', 'pending', 'cancelled'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>@endforeach
    </select>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Reference</th><th>Customer</th><th>Cycle</th><th class="text-end">Amount</th><th>Paid until</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($subscriptions as $sub)
                <tr>
                    <td class="font-monospace small"><a href="{{ route('admin.orders.show', $sub->first_order_id) }}">{{ $sub->reference }}</a></td>
                    <td>{{ $sub->customer_name }}<div class="small text-muted">{{ $sub->customer_email }}</div></td>
                    <td>{{ ucfirst($sub->billing_term) }}</td>
                    <td class="text-end">{{ $sub->currency }} {{ number_format((float) $sub->amount, 2) }}</td>
                    <td>{{ $sub->current_period_end?->format('M d, Y') ?? '—' }}</td>
                    <td><span class="badge {{ ['active' => 'bg-success', 'cancelled' => 'bg-secondary'][$sub->status] ?? 'bg-warning text-dark' }}">{{ ucfirst($sub->status) }}</span></td>
                    <td class="text-end text-nowrap">
                        @if($sub->status === 'active')
                            <form method="POST" action="{{ route('admin.billing.send-link', $sub) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary">Send payment link</button></form>
                        @endif
                        @if($sub->status !== 'cancelled')
                            <form method="POST" action="{{ route('admin.billing.cancel', $sub) }}" class="d-inline" data-sky-confirm="No more renewal links will be sent for this plan." data-sky-title="Cancel this recurring plan?" data-sky-ok="Cancel plan" data-sky-variant="danger" data-sky-cancel-label="Keep plan">@csrf<button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No recurring plans yet. One appears when a customer pays for a monthly, quarterly, six-monthly or yearly term.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $subscriptions->links() }}</div>
@endsection
