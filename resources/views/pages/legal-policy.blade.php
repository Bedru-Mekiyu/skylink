@extends('layouts.master')

@section('title', $policy->title . ' - Sky Link Technologies')

@section('content')
<section class="py-5 bg-light border-bottom">
    <div class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $policy->title }}</li>
            </ol>
        </nav>
        <h1 class="fw-bold text-dark display-6 mb-2">{{ $policy->title }}</h1>
        <p class="text-muted small mb-0">Last updated: {{ $policy->updated_at->format('F d, Y') }}</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                    <div class="policy-body text-dark" style="line-height: 1.8; font-size: 15px;">
                        {!! nl2br(e($policy->localized_content)) !!}
                    </div>

                    <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-muted small">Sky Link Technologies PLC &bull; Addis Ababa, Ethiopia</span>
                        <a href="{{ route('contact') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fas fa-envelope me-1"></i> Contact Legal / Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
