@extends('layouts.master')

@section('title', 'Subscribe Now | Sky Link')
@section('description', 'Subscribe to one of our premium service packages.')

@php
    // The plan comes from the address (/subscribe/{plan}); show and send only a safe value.
    $planSlug = preg_match('/^[A-Za-z0-9_-]{1,50}$/', (string) ($plan ?? '')) ? $plan : 'premium';
@endphp

@section('content')

<section class="py-5" style="background: #f0f8ff;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card shadow-lg rounded-4 border-0" id="subscribe-form">
          <div class="card-body">
            <h2 class="mb-4 text-primary text-center">Subscribe to <span class="fw-bold">{{ ucfirst($planSlug) }} Plan</span></h2>
            @include('partials.forms.feedback', ['anchor' => 'subscribe-form'])
            <form method="POST" action="{{ route('inquiry.store') }}">
              @csrf
              <x-form-guard />
              <input type="hidden" name="type" value="subscription">
              <input type="hidden" name="form_anchor" value="subscribe-form">
              <input type="hidden" name="plan" value="{{ $planSlug }}">
              <div class="mb-3">
                <label class="form-label" for="sub-name">Full Name *</label>
                <input type="text" class="form-control" id="sub-name" name="name" value="{{ old('name') }}" placeholder="Your full name" maxlength="120" autocomplete="name" required>
              </div>
              <div class="mb-3">
                <label class="form-label" for="sub-email">Email address *</label>
                <input type="email" class="form-control" id="sub-email" name="email" value="{{ old('email') }}" placeholder="you@example.com" maxlength="190" autocomplete="email" required>
              </div>
              <div class="mb-3">
                <label class="form-label" for="sub-phone">Phone Number *</label>
                <input type="tel" class="form-control" id="sub-phone" name="phone" value="{{ old('phone') }}" placeholder="+251 9xx xxx xxx" maxlength="30" autocomplete="tel" required>
              </div>
              <div class="mb-3">
                <label class="form-label" for="sub-notes">Additional Notes</label>
                <textarea class="form-control" id="sub-notes" name="message" rows="3" maxlength="5000" placeholder="Any specific requests?">{{ old('message') }}</textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100">Complete Subscription</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
