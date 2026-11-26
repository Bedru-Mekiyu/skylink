@extends('layouts.master')

@php
    $paid = $order->isPaid();
    $failed = ! $paid && in_array($outcome, ['failed', 'mismatch'], true);
    $heading = match (true) {
        $paid => __('Payment received'),
        $failed => __('Payment was not completed'),
        $outcome === 'pending' => __('Payment pending'),
        default => $order->billing_subscription_id ? __('Renew your plan') : __('Review and pay'),
    };
    $termLabels = ['monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'semi-annually' => __('Every six months'), 'annually' => __('Yearly'), 'one-time' => __('One-time'), 'project-based' => __('Project-based')];
@endphp

@section('title', $heading.' | Sky Link')
@push('styles')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="container py-5 sky-commerce">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="h3 fw-bold mb-1">{{ $heading }}</h1>
            <p class="text-muted mb-4">{{ __('Order') }} <strong>{{ $order->order_number }}</strong>@if($order->period_start) · {{ __('period starting') }} {{ $order->period_start->format('M d, Y') }}@endif</p>

            @if($failed)<div class="alert alert-warning rounded-3" role="alert">{{ __('No payment was taken for this order. You can try again or contact us.') }}</div>@endif
            @if($outcome === 'pending')<div class="alert alert-info rounded-3" role="status">{{ __('We have not received the confirmation yet. Reload this page in a minute.') }}</div>@endif
            @if($canPay && $testMode)<div class="alert alert-secondary rounded-3 small" role="status">{{ __('Test mode: no real money is taken.') }}</div>@endif

            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <ul class="list-unstyled mb-0">
                    @foreach($order->items as $item)
                        <li class="d-flex justify-content-between gap-3 py-2 border-bottom">
                            <span>
                                <span class="fw-semibold d-block">{{ $item->title }}</span>
                                <span class="small text-muted">{{ $item->option_name }} · {{ $termLabels[$item->billing_term] ?? $item->billing_term }} · × {{ $item->quantity }}</span>
                            </span>
                            <span class="text-nowrap">{{ $item->currency }} {{ number_format((float) $item->line_total, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="d-flex justify-content-between align-items-center pt-3">
                    <span class="h5 mb-0">{{ $paid ? __('Total paid') : __('Total') }}</span>
                    <span class="h4 fw-bold mb-0">{{ $order->currency }} {{ number_format((float) $order->total, 2) }}</span>
                </div>
                @if($note = \App\Support\PriceVisibility::taxNote())<div class="small text-muted text-end">{{ $note }}</div>@endif
                @if($order->items->contains(fn ($i) => isset(\App\Models\BillingSubscription::TERM_MONTHS[$i->billing_term])))
                    <p class="small text-muted mt-3 mb-0">{{ __('This payment covers one billing period. Nothing is charged automatically: we email you a payment link before each renewal.') }}</p>
                @endif
            </div>

            @if($canPay)
                <form method="POST" action="{{ $payUrl }}" onsubmit="this.querySelector('button').disabled = true">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-lock me-2" aria-hidden="true"></i>{{ __('Pay with Chapa') }}</button>
                </form>
                <p class="small text-muted text-center mt-2">{{ __('You pay on Chapa’s secure page. We never see your card or account details.') }}</p>
            @elseif($paid)
                <div class="alert alert-success rounded-3" role="status">{{ __('Thank you. A confirmation has been sent to your email.') }}</div>
                <a href="{{ route('home') }}" class="btn btn-primary">{{ __('Back to the home page') }}</a>
            @else
                <p class="text-muted">{{ __('This order cannot be paid online right now.') }}</p>
            @endif

            <p class="small text-center mt-4">{{ __('Questions?') }} <a href="{{ route('contact', ['type' => 'support', 'service' => $order->order_number]) }}#contact-form">{{ __('Contact support') }}</a></p>
        </div>
    </div>
</div>
@endsection
