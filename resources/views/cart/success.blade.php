@extends('layouts.master')

@section('title', 'Request Received | Sky Link ICT Solutions')
@section('description', 'Your quotation and service request has been received.')

@section('content')
<div class="container py-5 my-md-4 sky-commerce">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
                <div class="mb-4">
                    <span class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow" style="width: 72px; height: 72px;">
                        <i class="fas fa-check fa-2x"></i>
                    </span>
                </div>

                <h2 class="fw-bold text-dark mb-2">{{ __('Request Received Successfully!') }}</h2>
                <p class="text-muted mb-4">
                    {{ __('Thank you,') }} <strong>{{ $submitted['name'] ?? 'valued client' }}</strong>. {{ __('We have received your request for') }} {{ $submitted['items_count'] ?? 1 }} {{ __('item(s).') }}
                </p>

                <div class="bg-light border rounded-3 p-3 mb-4">
                    <div class="small text-muted text-uppercase fw-semibold">{{ __('Your Reference Number is:') }}</div>
                    <div class="h3 fw-bold text-primary mb-0 user-select-all font-monospace mt-1">
                        {{ $submitted['reference'] ?? 'SK-REQUEST' }}
                    </div>
                </div>

                <p class="small text-muted mb-4">
                    {{ __('A confirmation email has been dispatched to') }} <strong>{{ $submitted['email'] ?? 'your email' }}</strong>. {{ __('Our solution architects will review your project requirements and prepare your customized quotation within 1 business day.') }}
                </p>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('home') }}" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">
                        <i class="fas fa-home me-2"></i>{{ __('Return to Home') }}
                    </a>
                    <a href="{{ route('services') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold">
                        <i class="fas fa-laptop-code me-2"></i>{{ __('Explore More Services') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
