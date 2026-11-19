@extends('admin.layouts.master')

@section('title', 'Inbox')
@section('page-title', 'Inbox')

@php
    $tz = config('news.admin_timezone', 'Africa/Addis_Ababa');
    $statusClass = ['new' => 'text-bg-primary', 'in_progress' => 'text-bg-warning', 'done' => 'text-bg-success', 'spam' => 'text-bg-secondary'];
@endphp

@section('content')
<div class="card inbox-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h5 mb-0"><i class="fas fa-inbox me-2" aria-hidden="true"></i>Website messages <span class="text-muted fw-normal small">({{ $inquiries->total() }})</span></h2>
        <span class="small text-muted">Emails go to <strong>{{ $inbox ?: 'not set' }}</strong> (CONTACT_INBOX)</span>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('admin.inbox.read-all') }}" class="ms-auto">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-check-double me-1" aria-hidden="true"></i>Mark all as read ({{ $unreadCount }})</button>
            </form>
        @endif
    </div>

    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('admin.inbox.index') }}" class="row g-2 align-items-end" role="search">
            <div class="col-12 col-md-4">
                <label for="q" class="form-label small mb-1">Search name, email, phone, reference or text</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" maxlength="100">
            </div>
            <div class="col-6 col-md-2">
                <label for="type" class="form-label small mb-1">Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="">All types</option>
                    @foreach (\App\Models\Inquiry::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small mb-1">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Inquiry::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <div class="form-check mt-md-4">
                    <input class="form-check-input" type="checkbox" name="unread" value="1" id="unread" @checked($unread)>
                    <label class="form-check-label" for="unread">Unread only ({{ $unreadCount }})</label>
                </div>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-fill"><i class="fas fa-search me-1" aria-hidden="true"></i>Filter</button>
                @if ($search !== '' || $type || $status || $unread)
                    <a href="{{ route('admin.inbox.index') }}" class="btn btn-light" title="Clear filters"><i class="fas fa-times" aria-hidden="true"></i><span class="visually-hidden">Clear filters</span></a>
                @endif
            </div>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.inbox.bulk-destroy') }}" id="bulk-delete-form">
    @csrf
    @method('DELETE')
    <div class="card-body border-bottom py-2 d-flex flex-wrap align-items-center gap-2">
        <button type="submit" class="btn btn-sm btn-outline-danger" id="bulk-delete-btn" disabled><i class="fas fa-trash me-1" aria-hidden="true"></i>Delete selected <span id="bulk-count" class="badge text-bg-light">0</span></button>
        <span class="small text-muted">Tick messages in the list, then delete them together.</span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <caption class="visually-hidden">Website messages, newest first</caption>
                <thead>
                    <tr>
                        <th scope="col" class="ps-4" style="width:2.5rem"><input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all messages on this page"></th>
                        <th scope="col">From</th>
                        <th scope="col">Type</th>
                        <th scope="col">Message</th>
                        <th scope="col">Status</th>
                        <th scope="col">Received</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="pe-4"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inquiries as $item)
                        <tr class="{{ $item->read_at ? '' : 'inbox-unread' }}">
                            <td data-label="Select" class="ps-4"><input type="checkbox" class="form-check-input bulk-box" name="ids[]" value="{{ $item->id }}" aria-label="Select message {{ $item->reference }}"></td>
                            <td data-label="From">
                                <a href="{{ route('admin.inbox.show', $item) }}" class="text-body text-decoration-none fw-semibold inbox-name" lang="{{ $item->language }}">
                                    @unless ($item->read_at)<span class="inbox-dot" aria-hidden="true"></span><span class="visually-hidden">Unread: </span>@endunless{{ $item->name }}
                                </a>
                                <div class="small text-muted text-break">{{ $item->email }}</div>
                                <div class="small text-muted">{{ $item->reference }}</div>
                            </td>
                            <td data-label="Type">{{ $item->type_label }}@if ($item->service)<div class="small text-muted">{{ $item->service }}</div>@endif @if ($item->plan)<div class="small text-muted">Plan: {{ $item->plan }}</div>@endif</td>
                            <td data-label="Message" class="inbox-snippet" lang="{{ $item->language }}">{{ \Illuminate\Support\Str::limit((string) $item->message, 90) ?: '—' }}</td>
                            <td data-label="Status"><span class="badge {{ $statusClass[$item->status] ?? 'text-bg-light' }}">{{ $item->status_label }}</span></td>
                            <td data-label="Received" class="text-nowrap">{{ $item->created_at->copy()->setTimezone($tz)->format('M j, H:i') }}</td>
                            <td data-label="Email" class="text-nowrap small">
                                @if ($item->legacy_table)
                                    <span class="text-muted">Older message</span>
                                @elseif ($item->team_notify_error)
                                    <span class="text-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Failed</span>
                                @elseif ($item->team_notified_at)
                                    <span class="text-success"><i class="fas fa-check" aria-hidden="true"></i> Sent</span>
                                @else
                                    <span class="text-muted"><i class="fas fa-clock" aria-hidden="true"></i> Queued</span>
                                @endif
                            </td>
                            <td data-label="Delete" class="pe-4 text-nowrap">
                                <button type="submit" form="delete-message-{{ $item->id }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this message? This can\'t be undone from the admin.');"><i class="fas fa-trash me-1" aria-hidden="true"></i>Delete<span class="visually-hidden"> message {{ $item->reference }}</span></button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3" aria-hidden="true"></i>
                                <p class="h5 text-muted">{{ ($search !== '' || $type || $status || $unread) ? 'No messages match these filters.' : 'No messages yet.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </form>

    {{-- One small form per row for the single Delete button (a form cannot sit inside the bulk form). --}}
    @foreach ($inquiries as $item)
        <form method="POST" action="{{ route('admin.inbox.destroy', $item) }}" id="delete-message-{{ $item->id }}" class="d-none">@csrf @method('DELETE')</form>
    @endforeach

    @if ($inquiries->hasPages())
        <div class="card-footer d-flex justify-content-center">
            {{ $inquiries->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('bulk-delete-form');
        if (!form) return;
        var boxes = Array.prototype.slice.call(form.querySelectorAll('.bulk-box'));
        var all = document.getElementById('select-all');
        var btn = document.getElementById('bulk-delete-btn');
        var count = document.getElementById('bulk-count');
        function refresh() {
            var n = boxes.filter(function (b) { return b.checked; }).length;
            count.textContent = n;
            btn.disabled = n === 0;
            if (all) { all.checked = n > 0 && n === boxes.length; all.indeterminate = n > 0 && n < boxes.length; }
        }
        boxes.forEach(function (b) { b.addEventListener('change', refresh); });
        if (all) all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); refresh(); });
        form.addEventListener('submit', function (e) {
            var n = boxes.filter(function (b) { return b.checked; }).length;
            if (n === 0 || !window.confirm('Delete ' + (n === 1 ? 'the selected message' : 'the ' + n + ' selected messages') + '? This can\'t be undone from the admin.')) e.preventDefault();
        });
        refresh();
    })();
</script>
@endpush

@push('styles')
<style>
    .inbox-card.card:hover { transform: none; box-shadow: none; }
    .inbox-unread td { background: #f8fbff; }
    .inbox-unread .inbox-name { font-weight: 700 !important; }
    .inbox-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2563eb; margin-right: 6px; vertical-align: middle; }
    .inbox-name[lang="am"], .inbox-snippet[lang="am"] { font-family: var(--font-ui); }
    @media (min-width: 768px) {
        .inbox-card th, .inbox-card td[data-label="Type"], .inbox-card td[data-label="Status"] { white-space: nowrap; word-break: normal; }
        .inbox-snippet { max-width: 360px; }
    }
</style>
@endpush
