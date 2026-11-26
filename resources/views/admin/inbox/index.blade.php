@extends('admin.layouts.master')

@section('title', 'Inbox')
@section('page-title', 'Inbox')

@php
    $tz = config('news.admin_timezone', 'Africa/Addis_Ababa');
    $filtered = $search !== '' || $type || $status || $unread;
    $emailLabel = fn ($i) => $i->legacy_table ? null : ($i->team_notify_error ? 'Email to the team failed' : null);
@endphp

@section('content')
<div class="card inbox-card">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold">{{ $inquiries->total() }} {{ \Illuminate\Support\Str::plural('message', $inquiries->total()) }}@if ($unreadCount > 0) <span class="text-muted fw-normal">· {{ $unreadCount }} unread</span>@endif</span>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('admin.inbox.read-all') }}" class="ms-auto">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-check-double me-1" aria-hidden="true"></i>Mark all as read ({{ $unreadCount }})</button>
            </form>
        @endif
    </div>

    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('admin.inbox.index') }}" class="inbox-filters" role="search">
            <label for="q" class="visually-hidden">Search name, email, phone, reference or text</label>
            <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" maxlength="100" placeholder="Search messages">
            <label for="type" class="visually-hidden">Type</label>
            <select id="type" name="type" class="form-select">
                <option value="">All types</option>
                @foreach (\App\Models\Inquiry::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label for="status" class="visually-hidden">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach (\App\Models\Inquiry::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-check inbox-unread-toggle">
                <input class="form-check-input" type="checkbox" name="unread" value="1" id="unread" @checked($unread)>
                <label class="form-check-label" for="unread">Unread only ({{ $unreadCount }})</label>
            </div>
            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1" aria-hidden="true"></i>Filter</button>
            @if ($filtered)
                <a href="{{ route('admin.inbox.index') }}" class="btn btn-light" title="Clear filters"><i class="fas fa-times" aria-hidden="true"></i><span class="visually-hidden">Clear filters</span></a>
            @endif
        </form>
    </div>

    <form method="POST" action="{{ route('admin.inbox.bulk-destroy') }}" id="bulk-delete-form">
    @csrf
    @method('DELETE')
    <div class="inbox-head">
        <span class="sky-tap inbox-check"><input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all messages on this page"></span>
        <span class="inbox-head__from">From</span>
        <span class="inbox-head__type">Type</span>
        <span class="inbox-head__msg">Message</span>
        <span class="inbox-head__date">Received</span>
        <a href="{{ route('admin.inbox.export') }}" class="btn btn-sm btn-outline-secondary me-2" style="min-height:44px;display:inline-flex;align-items:center"><i class="fas fa-file-csv me-1" aria-hidden="true"></i>Export CSV (all)</a>
        <button type="submit" class="btn btn-sm btn-outline-danger inbox-bulk" id="bulk-delete-btn" disabled><i class="fas fa-trash me-1" aria-hidden="true"></i>Delete selected <span id="bulk-count" class="badge text-bg-danger">0</span></button>
    </div>
    <ul class="inbox-list list-unstyled mb-0">
        @forelse ($inquiries as $item)
            <li class="inbox-row {{ $item->read_at ? '' : 'inbox-unread' }}">
                <span class="sky-tap inbox-check"><input type="checkbox" class="form-check-input bulk-box" name="ids[]" value="{{ $item->id }}" aria-label="Select message {{ $item->reference }}"></span>
                <div class="inbox-from">
                    <a href="{{ route('admin.inbox.show', $item) }}" class="text-body text-decoration-none inbox-name" lang="{{ $item->language }}" title="{{ $item->reference }}">@unless ($item->read_at)<span class="inbox-dot" aria-hidden="true"></span><span class="visually-hidden">Unread: </span>@endunless{{ $item->name }}</a>
                    <div class="small text-muted text-break">{{ $item->email }}</div>
                </div>
                <div class="inbox-type small">
                    <span class="inbox-chip">{{ $item->type_label }}</span>
                    @if ($item->status !== 'new')<span class="badge text-bg-light border">{{ $item->status_label }}</span>@endif
                </div>
                <div class="inbox-snippet" lang="{{ $item->language }}">{{ \Illuminate\Support\Str::limit((string) $item->message, 90) ?: '—' }}@if ($emailLabel($item))<div class="small text-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ $emailLabel($item) }}</div>@endif</div>
                <div class="inbox-date small text-muted">{{ $item->created_at->copy()->setTimezone($tz)->format('M j, H:i') }}</div>
                <button type="submit" form="delete-message-{{ $item->id }}" class="btn btn-sm btn-outline-danger inbox-del" data-sky-confirm="Delete this message? This can't be undone from the admin." data-sky-title="Delete message?" data-sky-ok="Delete" data-sky-variant="danger" onclick="return confirm('Delete this message? This can\'t be undone from the admin.');"><i class="fas fa-trash" aria-hidden="true"></i><span class="inbox-del__txt ms-1">Delete</span><span class="visually-hidden"> message {{ $item->reference }}</span></button>
            </li>
        @empty
            <li class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3" aria-hidden="true"></i>
                <p class="h5 text-muted">{{ $filtered ? 'No messages match these filters.' : 'No messages yet.' }}</p>
            </li>
        @endforelse
    </ul>
    </form>

    {{-- One small form per row for the single Delete button (a form cannot sit inside the bulk form). --}}
    @foreach ($inquiries as $item)
        <form method="POST" action="{{ route('admin.inbox.destroy', $item) }}" id="delete-message-{{ $item->id }}" class="d-none">@csrf @method('DELETE')</form>
    @endforeach

    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small text-muted">New messages are emailed to <strong>{{ $inbox ?: 'not set' }}</strong></span>
        @if ($inquiries->hasPages()){{ $inquiries->onEachSide(1)->links('pagination::bootstrap-5') }}@endif
    </div>
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
        // the whole 44 px cell is the tap target for its checkbox
        form.querySelectorAll('td.sky-tap, th.sky-tap').forEach(function (cell) {
            cell.addEventListener('click', function (e) {
                var box = cell.querySelector('input[type=checkbox]');
                if (box && e.target !== box) { box.click(); }
            });
        });
        if (all) all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); refresh(); });
        var confirmed = false;
        form.addEventListener('submit', function (e) {
            if (confirmed) return;
            var n = boxes.filter(function (b) { return b.checked; }).length;
            e.preventDefault();
            if (n === 0) return;
            var title = n === 1 ? 'Delete 1 message?' : 'Delete ' + n + ' messages?';
            var msg = (n === 1 ? 'The selected message' : 'The ' + n + ' selected messages') + ' will be removed from the inbox. This can\'t be undone from the admin.';
            if (window.SkyConfirm) {
                window.SkyConfirm.ask({ title: title, message: msg, confirmLabel: 'Delete', variant: 'danger' }).then(function (ok) { if (ok) { confirmed = true; form.submit(); } });
            } else if (window.confirm(title + '\n\n' + msg)) { confirmed = true; form.submit(); }
        });
        refresh();
    })();
</script>
@endpush

@push('styles')
<style>
    .inbox-card.card:hover { transform: none; box-shadow: none; }
    .inbox-filters { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
    .inbox-filters #q { flex: 1 1 220px; min-width: 0; }
    .inbox-filters .form-select { flex: 0 1 11rem; width: auto; }
    .inbox-filters .btn { white-space: nowrap; }
    .inbox-head, .inbox-row { display: grid; grid-template-columns: 44px minmax(170px, 1.1fr) 150px minmax(0, 2fr) 110px 150px; column-gap: 0.75rem; align-items: center; padding: 0.4rem 1rem 0.4rem 0.25rem; }
    .inbox-head { border-bottom: 1px solid var(--border-color); font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; padding-top: 0.2rem; padding-bottom: 0.2rem; }
    .inbox-head .inbox-bulk { grid-column: 6; justify-self: end; white-space: nowrap; text-transform: none; letter-spacing: 0; }
    .inbox-row { border-bottom: 1px solid var(--border-color); min-height: 64px; }
    .inbox-row:last-child { border-bottom: 0; }
    .inbox-row:hover { background: rgba(26, 111, 176, 0.04); }
    .inbox-unread { background: #f8fbff; }
    .inbox-unread .inbox-name { font-weight: 700; }
    .inbox-name { font-weight: 600; }
    .inbox-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: #2b4c9d; margin-right: 7px; vertical-align: middle; }
    .inbox-chip { display: inline-block; padding: 0.1rem 0.55rem; border-radius: 999px; background: #eef3ff; color: #2b4c9d; font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
    .inbox-snippet { min-width: 0; overflow: hidden; text-overflow: ellipsis; color: var(--text-muted); }
    .inbox-date { text-align: right; white-space: nowrap; }
    .inbox-del { justify-self: end; }
    .inbox-check { display: inline-flex; align-items: center; justify-content: center; }
    .inbox-name[lang="am"], .inbox-snippet[lang="am"] { font-family: var(--font-ui); }
    @media (max-width: 767.98px) {
        .inbox-filters .form-select { flex: 1 1 40%; }
        .inbox-filters .inbox-unread-toggle { flex: 1 1 100%; margin: 0; }
        .inbox-head__from, .inbox-head__type, .inbox-head__msg, .inbox-head__date { display: none; }
        .inbox-head { display: flex; justify-content: space-between; padding-left: 0.25rem; }
        .inbox-row { grid-template-columns: 44px minmax(0, 1fr) auto; grid-template-areas: "chk from date" "chk msg msg" "chk type del"; row-gap: 0.15rem; padding-top: 0.6rem; padding-bottom: 0.6rem; }
        .inbox-row > .inbox-check { grid-area: chk; align-self: start; }
        .inbox-from { grid-area: from; min-width: 0; }
        .inbox-date { grid-area: date; align-self: start; padding-top: 0.15rem; }
        .inbox-snippet { grid-area: msg; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; white-space: normal; font-size: 1rem; }
        .inbox-type { grid-area: type; }
        .inbox-del { grid-area: del; }
    }
    @media (max-width: 420px) { .inbox-del__txt { display: none; } }
</style>
@endpush
