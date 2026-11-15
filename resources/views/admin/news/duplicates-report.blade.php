@extends('admin.layouts.master')

@section('title', 'Duplicate check report')
@section('page-title', 'Duplicate check report')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.news.index') }}">News</a></li>
        <li class="breadcrumb-item active" aria-current="page">Duplicate check</li>
    </ol>
</nav>

<div class="card news-report-card">
    <div class="card-header">
        <h2 class="h5 mb-0"><i class="fas fa-clone me-2" aria-hidden="true"></i>Latest duplicate check (dry run)</h2>
    </div>
    <div class="card-body">
        <p class="small text-muted">
            This report is made automatically once a day and <strong>changes nothing</strong>.
            "HIDE" lines are articles that <em>would</em> be hidden, with their address forwarded to the article marked "KEEP".
            Hiding is a separate, manual step done by the site administrator on the server.
        </p>
        @if ($report)
            <pre class="news-report" tabindex="0" aria-label="Report text">{{ $report }}</pre>
        @else
            <p class="mb-0">No report yet. It appears after the first scheduled run (daily at 02:00 UTC).</p>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .news-report-card.card:hover { transform: none; box-shadow: none; }
    .news-report { white-space: pre-wrap; word-break: normal; overflow-wrap: anywhere; background: #f8f9fa; border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; font-size: 0.85rem; font-family: 'Inter', 'Noto Sans Ethiopic', 'Nyala', ui-monospace, monospace; max-height: 70vh; overflow: auto; }
</style>
@endpush
