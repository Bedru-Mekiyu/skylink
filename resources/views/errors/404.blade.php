@extends('layouts.master')

@php($seoStatus = 404)

@section('content')
<section class="py-5 text-center" style="min-height: 55vh; display: flex; align-items: center;">
    <div class="container py-5">
        <p class="text-uppercase fw-semibold mb-2" style="color: #1a6fb0; letter-spacing: .08em;">404</p>
        <h1 class="mb-3">We could not find that page</h1>
        <p class="mb-4 text-muted">The address may have changed or never existed. You can go back to the home page or contact us.</p>
        <a class="btn btn-primary me-2" href="{{ route('home') }}">Home</a>
        <a class="btn btn-outline-primary" href="{{ route('contact') }}">Contact us</a>
    </div>
</section>
@endsection
