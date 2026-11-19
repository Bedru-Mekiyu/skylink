@extends('admin.layouts.master')

@section('title', 'Message '.$inquiry->reference)
@section('page-title', 'Inbox')

@php
    $tz = config('news.admin_timezone', 'Africa/Addis_Ababa');
    $fmt = fn ($d) => $d ? $d->copy()->setTimezone($tz)->format('M j, Y H:i') : null;
    $replySubject = rawurlencode('Re: '.$inquiry->type_label.' ('.$inquiry->reference.')');
    $details = array_filter([
        'Email' => $inquiry->email,
        'Phone' => $inquiry->phone,
        'Company' => $inquiry->company,
        'Service' => $inquiry->service,
        'Plan' => $inquiry->plan,
        'Sent from page' => $inquiry->source_page,
        'Received' => $fmt($inquiry->created_at).' (Addis Ababa)',
    ], fn ($v) => filled($v));
@endphp

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.inbox.index') }}">Inbox</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $inquiry->reference }}</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card inbox-card">
            <div class="card-header">
                <div class="small text-muted">{{ $inquiry->type_label }} · {{ $inquiry->reference }}</div>
                <h2 class="h5 mb-0 text-break" lang="{{ $inquiry->language }}">{{ $inquiry->name }}</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-3 small">
                    @foreach ($details as $label => $value)
                        <dt class="col-sm-3 text-muted fw-normal">{{ $label }}</dt>
                        <dd class="col-sm-9 text-break">
                            @if ($label === 'Email')<a href="mailto:{{ $value }}">{{ $value }}</a>
                            @elseif ($label === 'Phone')<a href="tel:{{ preg_replace('/[^\d+]/', '', $value) }}">{{ $value }}</a>
                            @else{{ $value }}@endif
                        </dd>
                    @endforeach
                </dl>
                <h3 class="h6 text-muted">Message</h3>
                <div class="inbox-message" lang="{{ $inquiry->language }}">{{ $inquiry->message ?: '—' }}</div>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="mailto:{{ $inquiry->email }}?subject={{ $replySubject }}"><i class="fas fa-reply me-1" aria-hidden="true"></i>Reply by email</a>
                <form method="POST" action="{{ route('admin.inbox.destroy', $inquiry) }}" onsubmit="return confirm('Delete this message? This can\'t be undone from the admin.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1" aria-hidden="true"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card inbox-card mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.inbox.update', $inquiry) }}" class="d-flex gap-2 align-items-end">
                    @csrf @method('PATCH')
                    <div class="flex-fill">
                        <label for="status" class="form-label small mb-1">Status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach (\App\Models\Inquiry::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected($inquiry->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Save</button>
                </form>
            </div>
        </div>

        <div class="card inbox-card">
            <div class="card-body small">
                <h3 class="h6">Email delivery</h3>
                @if ($inquiry->legacy_table)
                    <p class="text-muted mb-0">Older message, copied from <code>{{ $inquiry->legacy_table }}</code>. No email was sent by the inbox.</p>
                @else
                    <p class="mb-1"><strong>Team inbox</strong> ({{ $teamInbox ?: 'not set' }}):
                        @if ($inquiry->team_notify_error)
                            <span class="text-danger">failed</span><br><span class="text-muted text-break">{{ $inquiry->team_notify_error }}</span>
                        @elseif ($inquiry->team_notified_at)
                            <span class="text-success">sent {{ $fmt($inquiry->team_notified_at) }}</span>
                        @else
                            <span class="text-muted">queued</span>
                        @endif
                    </p>
                    <p class="mb-2"><strong>Confirmation to visitor:</strong>
                        @if ($inquiry->visitor_confirm_error)
                            <span class="text-danger">failed</span><br><span class="text-muted text-break">{{ $inquiry->visitor_confirm_error }}</span>
                        @elseif ($inquiry->visitor_confirmed_at)
                            <span class="text-success">sent {{ $fmt($inquiry->visitor_confirmed_at) }}</span>
                        @elseif (config('contact.confirm_visitor'))
                            <span class="text-muted">queued</span>
                        @else
                            <span class="text-muted">switched off</span>
                        @endif
                    </p>
                    <form method="POST" action="{{ route('admin.inbox.resend', $inquiry) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-redo me-1" aria-hidden="true"></i>Send to team inbox again</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .inbox-card.card:hover { transform: none; box-shadow: none; }
    .inbox-message { white-space: pre-wrap; overflow-wrap: anywhere; background: #f9fafb; border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; line-height: 1.7; }
    [lang="am"] { font-family: var(--font-ui); }
</style>
@endpush
