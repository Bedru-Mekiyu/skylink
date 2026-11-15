@extends('layouts.master')

@section('title', 'Newsletter | Sky Link')
@section('description', 'Manage your Sky Link newsletter subscription.')

@push('styles')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<section class="py-5" style="background: #f0f8ff; min-height: 50vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 text-center">
                        @if ($done)
                            <h1 class="h3 mb-3">You are unsubscribed</h1>
                            <p class="mb-1">You will not receive our newsletter any more.</p>
                            <p lang="am" class="mb-0">ከዜና መጽሔታችን ዝርዝር ተወግደዋል።</p>
                        @else
                            <h1 class="h3 mb-3">Unsubscribe from the newsletter?</h1>
                            <p lang="am">ከዜና መጽሔታችን ዝርዝር መውጣት ይፈልጋሉ?</p>
                            <form method="POST" action="{{ $confirmUrl }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Yes, unsubscribe me</button>
                            </form>
                        @endif
                        <p class="mt-4 mb-0"><a href="{{ route('home') }}">Back to the homepage</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
