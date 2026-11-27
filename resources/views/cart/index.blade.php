@extends('layouts.master')

@section('title', 'Your Request Cart | Sky Link ICT Solutions')
@section('description', 'Review your selected services and products and submit a quotation or subscription request.')

@section('content')
<div class="container py-5 cart-container sky-commerce">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h1 class="h2 fw-bold text-primary mb-0">
            <i class="fas fa-shopping-cart me-2"></i>{{ __('Request Cart') }}
        </h1>
    </div>



    @if(empty($cart))
        {{-- Empty Cart State --}}
        <div class="card border-0 shadow-sm rounded-4 text-center p-5 my-4">
            <div class="my-4">
                <i class="fas fa-shopping-basket fa-4x text-muted opacity-50 mb-3"></i>
                <h3 class="fw-bold">{{ __('Your cart is empty') }}</h3>
                <p class="text-muted max-w-md mx-auto">
                    {{ __('Browse our services and products to request a quote or start a subscription.') }}
                </p>
                <div class="d-flex justify-content-center gap-3 mt-4 flex-wrap">
                    <a href="{{ route('services') }}" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                        <i class="fas fa-laptop-code me-2"></i>{{ __('Explore Services') }}
                    </a>
                    <a href="{{ route('products') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill fw-semibold">
                        <i class="fas fa-boxes me-2"></i>{{ __('Browse Products') }}
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="row g-4">
            {{-- Cart Items Column --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    {{-- Desktop / Tablet Table View (>= 768px) --}}
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3 ps-4">{{ __('Item & Tier') }}</th>
                                    <th scope="col" class="py-3 text-center" style="width: 140px;">{{ __('Quantity') }}</th>
                                    <th scope="col" class="py-3 text-end" style="width: 150px;">{{ __('Price') }}</th>
                                    <th scope="col" class="py-3 text-end pe-4" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $key => $line)
                                    <tr id="cart-row-{{ $key }}" class="cart-item-row" data-line-key="{{ $key }}">
                                        <td class="py-3 ps-4">
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $imgSrc = Str::startsWith($line['image'] ?? '', 'http') || Str::startsWith($line['image'] ?? '', 'storage/')
                                                        ? asset($line['image'])
                                                        : asset('assets/images/services/' . ($line['image'] ?? 'placeholder.png'));
                                                @endphp
                                                <img src="{{ $imgSrc }}" alt="{{ $line['title'] }}" class="rounded-3 me-3" width="54" height="54" loading="lazy" style="width: 54px; height: 54px; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';">
                                                <div>
                                                    <h6 class="fw-bold mb-0 text-dark">{{ $line['title'] }}</h6>
                                                    <div class="small text-muted">
                                                        <span class="badge bg-light text-primary border me-1">{{ $line['option_name'] }}</span>
                                                        @if(!empty($line['billing_term']))
                                                            <span class="badge bg-light text-secondary border">{{ ucfirst($line['billing_term']) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-center">
                                            <form method="POST" action="{{ route('cart.update') }}" class="d-inline-flex align-items-center border rounded-pill p-1 cart-qty-form" data-line-key="{{ $key }}">
                                                @csrf
                                                <input type="hidden" name="line_key" value="{{ $key }}">
                                                <button type="submit" name="quantity" value="{{ max(0, $line['quantity'] - 1) }}" class="btn btn-sm btn-link text-decoration-none text-muted px-2 py-0" aria-label="Decrease quantity">
                                                    <i class="fas fa-minus small"></i>
                                                </button>
                                                <span class="px-2 fw-semibold line-qty-val" data-line-key="{{ $key }}">{{ $line['quantity'] }}</span>
                                                <button type="submit" name="quantity" value="{{ min(100, $line['quantity'] + 1) }}" class="btn btn-sm btn-link text-decoration-none text-muted px-2 py-0" aria-label="Increase quantity">
                                                    <i class="fas fa-plus small"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="py-3 text-end">
                                            @if($line['is_price_visible'] && $line['unit_price_amount'] !== null)
                                                <div class="fw-bold text-dark line-total-val" data-line-key="{{ $key }}">
                                                    {{ $line['currency'] }} {{ number_format($line['unit_price_amount'] * $line['quantity'], 2) }}
                                                </div>
                                                @if($line['quantity'] > 1)
                                                    <div class="small text-muted">
                                                        {{ $line['currency'] }} {{ number_format($line['unit_price_amount'], 2) }} {{ __('each') }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1 line-total-val" data-line-key="{{ $key }}">{{ $line['unit_price_display'] ?: __('Quote Request') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-end pe-4">
                                            <form method="POST" action="{{ route('cart.remove') }}" class="cart-remove-form" data-line-key="{{ $key }}">
                                                @csrf
                                                <input type="hidden" name="line_key" value="{{ $key }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="{{ __('Remove item') }}" aria-label="{{ __('Remove item') }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Cart Item Cards (< 768px) --}}
                    <div class="d-md-none p-3">
                        @foreach($cart as $key => $line)
                            @php
                                $imgSrc = Str::startsWith($line['image'] ?? '', 'http') || Str::startsWith($line['image'] ?? '', 'storage/')
                                    ? asset($line['image'])
                                    : asset('assets/images/services/' . ($line['image'] ?? 'placeholder.png'));
                            @endphp
                            <div id="cart-card-{{ $key }}" class="card border rounded-3 p-3 mb-3 bg-white shadow-sm cart-item-card" data-line-key="{{ $key }}">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $imgSrc }}" alt="{{ $line['title'] }}" class="rounded-3 me-3" width="50" height="50" loading="lazy" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark">{{ $line['title'] }}</h6>
                                            <div class="small">
                                                <span class="badge bg-light text-primary border me-1">{{ $line['option_name'] }}</span>
                                                @if(!empty($line['billing_term']))
                                                    <span class="badge bg-light text-secondary border">{{ ucfirst($line['billing_term']) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('cart.remove') }}" class="cart-remove-form" data-line-key="{{ $key }}">
                                        @csrf
                                        <input type="hidden" name="line_key" value="{{ $key }}">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-2" title="{{ __('Remove item') }}" aria-label="{{ __('Remove item') }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <form method="POST" action="{{ route('cart.update') }}" class="d-inline-flex align-items-center border rounded-pill px-2 py-1 cart-qty-form" data-line-key="{{ $key }}">
                                        @csrf
                                        <input type="hidden" name="line_key" value="{{ $key }}">
                                        <button type="submit" name="quantity" value="{{ max(0, $line['quantity'] - 1) }}" class="btn btn-sm btn-link text-decoration-none text-muted px-2 py-0" aria-label="Decrease quantity">
                                            <i class="fas fa-minus small"></i>
                                        </button>
                                        <span class="px-2 fw-semibold small line-qty-val" data-line-key="{{ $key }}">{{ $line['quantity'] }}</span>
                                        <button type="submit" name="quantity" value="{{ min(100, $line['quantity'] + 1) }}" class="btn btn-sm btn-link text-decoration-none text-muted px-2 py-0" aria-label="Increase quantity">
                                            <i class="fas fa-plus small"></i>
                                        </button>
                                    </form>
                                    <div class="text-end">
                                        @if($line['is_price_visible'] && $line['unit_price_amount'] !== null)
                                            <div class="fw-bold text-dark line-total-val" data-line-key="{{ $key }}">
                                                {{ $line['currency'] }} {{ number_format($line['unit_price_amount'] * $line['quantity'], 2) }}
                                            </div>
                                            @if($line['quantity'] > 1)
                                                <div class="small text-muted" style="font-size: 11px;">
                                                    {{ $line['currency'] }} {{ number_format($line['unit_price_amount'], 2) }} {{ __('each') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 line-total-val" data-line-key="{{ $key }}">{{ $line['unit_price_display'] ?: __('Quote Request') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Cart Summary Notice --}}
                @if($totals['has_total'])
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary-subtle text-primary mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0 fw-semibold">{{ __('Total Estimated Amount:') }}</span>
                            <span class="h3 mb-0 fw-bold" id="cartTotalDisplay">{{ $totals['currency'] }} {{ number_format($totals['subtotal'], 2) }}</span>
                        </div>
                        @if(!empty($totals['tax_note']))
                            <div class="small text-muted mt-2 text-end">{{ $totals['tax_note'] }}</div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-info border-0 rounded-4 shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('Pricing details and final terms will be confirmed in your customized official quotation.') }}
                    </div>
                @endif
            </div>

            {{-- Checkout Request Form Column --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-primary mb-3">
                            <i class="fas fa-paper-plane me-2"></i>{{ __('Submit Request') }}
                        </h5>
                        <p class="text-muted small mb-4">
                            {{ __('Provide your contact details. Our team will prepare your quotation and reach out within 1 business day.') }}
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3" role="alert">
                                {{ __('Please fix the highlighted fields below.') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('cart.checkout') }}" id="checkoutForm" novalidate>
                            @csrf
                            <x-form-guard />

                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="req-name">{{ __('Full Name *') }}</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="req-name" name="name" value="{{ old('name') }}" placeholder="{{ __('Your full name') }}" maxlength="120" autocomplete="name" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="req-email">{{ __('Email *') }}</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="req-email" name="email" value="{{ old('email') }}" placeholder="you@example.com" maxlength="190" autocomplete="email" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="req-phone">{{ __('Phone Number *') }}</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="req-phone" name="phone" value="{{ old('phone') }}" placeholder="+251 9xx xxx xxx" maxlength="30" autocomplete="tel" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text small">{{ __('e.g. +251 9xx xxx xxx or 09xx xxx xxx') }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="req-company">{{ __('Company / Organization') }}</label>
                                <input type="text" class="form-control @error('company') is-invalid @enderror" id="req-company" name="company" value="{{ old('company') }}" placeholder="{{ __('Organization or Business name') }}" maxlength="190" autocomplete="organization">
                                @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="req-notes">{{ __('Requirements / Notes (Optional)') }}</label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="req-notes" name="message" rows="3" maxlength="5000" placeholder="{{ __('Specific branch counts, timelines, or technical requirements...') }}">{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @php
                                $chapaService = app(\App\Services\ChapaService::class);
                                $chapaConfigured = $chapaService->isEnabled();
                                $payableIds = \App\Models\ItemOption::whereIn('id', array_filter(array_column($cart, 'option_id')))->where('is_payable_online', true)->pluck('id')->all();
                                $canPayOnline = $chapaConfigured && $totals['has_total'] && $totals['subtotal'] > 0 && ($totals['currency'] ?? 'ETB') === 'ETB'
                                    && collect($cart)->every(fn ($l) => in_array($l['option_id'], $payableIds));
                            @endphp

                            @if($chapaConfigured)
                                <div class="mb-3">
                                    <p class="form-label" id="payment-choice-label">{{ __('Checkout Action') }}</p>
                                    <div class="form-check sky-choice mb-2 {{ $canPayOnline ? '' : 'opacity-75' }}">
                                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_choice" id="payChapa" value="chapa" {{ $canPayOnline ? 'checked' : 'disabled' }}>
                                        <label class="form-check-label fw-semibold text-dark" for="payChapa">
                                            <i class="fas fa-credit-card text-success me-1"></i> {{ __('Pay Online via Chapa') }}
                                            <small class="d-block text-muted fw-normal" style="font-size: 11px;">
                                                {{ __('Telebirr, CBEBirr, AwashBirr, Visa & Mastercard') }}
                                            </small>
                                        </label>
                                        @if(!$canPayOnline)
                                            <span class="badge bg-secondary-subtle text-secondary small d-block mt-1">
                                                {{ __('Cart contains items requiring quotation') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="form-check sky-choice">
                                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_choice" id="payQuote" value="quote" {{ !$canPayOnline ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold text-dark" for="payQuote">
                                            <i class="fas fa-file-invoice text-primary me-1"></i> {{ __('Request Formal Quotation') }}
                                            <small class="d-block text-muted fw-normal" style="font-size: 11px;">
                                                {{ __('Receive an official quotation from our sales team') }}
                                            </small>
                                        </label>
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="payment_choice" value="quote">
                            @endif

                            @php
                                $publishedPolicies = class_exists(\App\Models\LegalPolicy::class) ? \App\Models\LegalPolicy::published()->get() : collect();
                            @endphp

                            <div class="form-check mb-4">
                                <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" id="req-consent" name="consent" value="1" @checked(old('consent')) required>
                                <label class="form-check-label small text-muted" for="req-consent">
                                    {{ __('I agree to be contacted by Sky Link ICT Solutions regarding this inquiry') }}@if($publishedPolicies->isNotEmpty()), {{ __('and agree to our') }}
                                        @foreach($publishedPolicies as $idx => $pol)
                                            <a href="{{ route('legal.show', $pol->slug) }}" target="_blank" class="text-primary text-decoration-underline">{{ $pol->title }}</a>{{ $idx < $publishedPolicies->count() - 1 ? ',' : '' }}
                                        @endforeach
                                    @endif.
                                </label>
                                @error('consent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" id="submitOrderBtn" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                                <i class="fas {{ $canPayOnline ? 'fa-lock' : 'fa-paper-plane' }} me-2" id="submitOrderBtnIcon"></i><span id="submitOrderBtnText">{{ $canPayOnline ? __('Proceed to Chapa Payment') : __('Send Request') }}</span>
                            </button>
                        </form>

                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const payChapa = document.getElementById('payChapa');
                            const payQuote = document.getElementById('payQuote');
                            const btnText = document.getElementById('submitOrderBtnText');
                            const btnIcon = document.getElementById('submitOrderBtnIcon');
                            const btn = document.getElementById('submitOrderBtn');

                            function updateCheckoutAction() {
                                if (payChapa && payChapa.checked) {
                                    if (btnText) btnText.textContent = "{{ __('Proceed to Chapa Payment') }}";
                                    if (btnIcon) btnIcon.className = "fas fa-lock me-2";
                                } else {
                                    if (btnText) btnText.textContent = "{{ __('Send Request') }}";
                                    if (btnIcon) btnIcon.className = "fas fa-paper-plane me-2";
                                }
                            }

                            if (payChapa) payChapa.addEventListener('change', updateCheckoutAction);
                            if (payQuote) payQuote.addEventListener('change', updateCheckoutAction);
                            updateCheckoutAction();

                            // Lock the button on submit so a double-click cannot create two orders.
                            const form = document.getElementById('checkoutForm');
                            if (form && btn) {
                                form.addEventListener('submit', function(e) {
                                    if (form.dataset.submitting === '1') { e.preventDefault(); return; }
                                    if (!form.checkValidity()) {
                                        e.preventDefault();
                                        form.reportValidity();
                                        return;
                                    }
                                    form.dataset.submitting = '1';
                                    btn.disabled = true;
                                    if (btnIcon) btnIcon.className = 'spinner-border spinner-border-sm me-2';
                                    if (btnText) btnText.textContent = "{{ __('Processing...') }}";
                                });
                            }
                            // Back-button restore (bfcache) must not leave the button locked.
                            window.addEventListener('pageshow', function(e) {
                                if (e.persisted && form && btn) {
                                    form.dataset.submitting = '0';
                                    btn.disabled = false;
                                    updateCheckoutAction();
                                }
                            });
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
