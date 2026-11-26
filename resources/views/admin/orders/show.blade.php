@extends('admin.layouts.master')

@section('title', 'Order ' . $order->order_number)
@section('page-title', 'Order Details: ' . $order->order_number)

@section('content')
<div class="container-fluid">
    {{-- Header Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Order #{{ $order->order_number }}</h1>
                @if($order->status === 'paid')
                    <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Paid</span>
                @elseif($order->status === 'pending')
                    <span class="badge bg-warning text-dark fs-6"><i class="fas fa-clock me-1"></i>Pending</span>
                @elseif($order->status === 'failed')
                    <span class="badge bg-danger fs-6"><i class="fas fa-times-circle me-1"></i>Failed</span>
                @else
                    <span class="badge bg-secondary fs-6">{{ ucfirst($order->status) }}</span>
                @endif
            </div>
            <small class="text-muted">Placed on {{ $order->created_at->format('M d, Y \a\t H:i:s T') }}</small>
        </div>

        <div class="d-flex gap-2">
            @if($order->status === 'pending')
                @if(auth()->user()->hasRole('finance'))
                <form action="{{ route('admin.orders.reverify', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm" data-sky-confirm="Ask Chapa for the current status of this payment?" data-sky-title="Check payment with Chapa?" data-sky-ok="Check now" data-sky-variant="primary">
                        <i class="fas fa-sync-alt me-1"></i> Re-verify with Chapa
                    </button>
                </form>
                @endif
            @endif

            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
    </div>

    {{-- Alerts --}}

    <div class="row g-4">
        {{-- Left: Itemized Lines & Payments --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">📦 Ordered Items ({{ $order->items->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Item / Service</th>
                                <th>Option / Tier</th>
                                <th>Billing Cycle</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end pe-3">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="ps-3">
                                        <strong class="d-block text-dark">{{ $item->title }}</strong>
                                        <small class="text-muted text-uppercase" style="font-size: 11px;">{{ $item->item_type }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $item->option_name }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ ucfirst($item->billing_term) }}</span>
                                    </td>
                                    <td class="text-center fw-semibold">{{ $item->quantity }}</td>
                                    <td class="text-end text-muted">
                                        @if($item->unit_price !== null)
                                            {{ $item->currency }} {{ number_format((float) $item->unit_price, 2) }}
                                        @else
                                            <span class="small">Quote</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 fw-bold text-dark">
                                        @if($item->line_total !== null)
                                            {{ $item->currency }} {{ number_format((float) $item->line_total, 2) }}
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Quote</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Subtotal:</th>
                                <th class="text-end pe-3">{{ $order->currency }} {{ number_format((float) $order->subtotal, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Tax:</th>
                                <th class="text-end pe-3">{{ $order->currency }} {{ number_format((float) $order->tax, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end fs-5 text-primary">Grand Total:</th>
                                <th class="text-end pe-3 fs-5 text-primary fw-bold">{{ $order->currency }} {{ number_format((float) $order->total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Subscription Renewal Info --}}
            @if($order->renewal_note)
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-primary-subtle text-primary">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <i class="fas fa-calendar-check fa-2x"></i>
                        <div>
                            <strong class="d-block text-dark">Subscription Renewal Schedule</strong>
                            <span class="small">{{ $order->renewal_note }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Payment Transactions --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">💳 Gateway Transactions</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tx Ref</th>
                                <th>Gateway</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Gateway Ref</th>
                                <th class="text-end pe-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $payment)
                                <tr>
                                    <td class="ps-3 font-monospace small fw-bold">{{ $payment->tx_ref }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ ucfirst($payment->gateway) }}</span></td>
                                    <td class="fw-semibold">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>
                                        @if($payment->status === 'successful')
                                            <span class="badge bg-success">Successful</span>
                                        @elseif($payment->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace small text-muted">{{ $payment->gateway_reference ?: 'N/A' }}</td>
                                    <td class="text-end pe-3 small text-muted">{{ $payment->created_at->format('M d, H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No gateway transactions recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Customer Info & Metadata --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">👤 Customer Information</h5>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <span class="text-muted small d-block">Full Name</span>
                        <strong class="d-block text-dark fs-6">{{ $order->customer_name }}</strong>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Email Address</span>
                        <a href="mailto:{{ $order->customer_email }}" class="text-decoration-none fw-semibold">
                            {{ $order->customer_email }}
                        </a>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Phone Number</span>
                        <a href="tel:{{ $order->customer_phone }}" class="text-decoration-none fw-semibold">
                            {{ $order->customer_phone }}
                        </a>
                    </div>

                    @if($order->company)
                        <div class="mb-3">
                            <span class="text-muted small d-block">Company / Organization</span>
                            <span class="fw-semibold text-dark">{{ $order->company }}</span>
                        </div>
                    @endif

                    @if($order->notes)
                        <div class="pt-3 border-top">
                            <span class="text-muted small d-block">Customer Notes / Requirements</span>
                            <p class="small text-muted mb-0 bg-light p-2 rounded">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">⚙️ Payment & Settlement</h5>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Payment Method:</span>
                        <span class="fw-semibold text-dark">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Currency:</span>
                        <span class="fw-semibold text-dark">{{ $order->currency }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Order Status:</span>
                        <span class="fw-bold text-dark">{{ ucfirst($order->status) }}</span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Updated At:</span>
                        <span class="small text-muted">{{ $order->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
