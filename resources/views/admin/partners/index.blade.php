@extends('admin.layouts.master')

@section('title', 'Partners')
@section('page-title', 'Trusted Partners')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="fas fa-handshake me-2" aria-hidden="true"></i>Homepage partner logos</h2>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="{{ route('admin.partners.index') }}" class="d-flex gap-2" role="search">
                <label for="q" class="visually-hidden">Search partners</label>
                <input type="search" id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Search by name">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
            </form>
            <a href="{{ route('admin.partners.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i>Add partner</a>
        </div>
    </div>
    <div class="card-body p-0">
        <p class="small text-muted px-4 pt-3 mb-2">
            Logos run in this order on the homepage; <strong>Featured</strong> partners come first.
            @if ($reorderable) Drag a row (or use the arrows) to change the order, it saves by itself. @else Clear the search to reorder. @endif
            Hiding a partner keeps it here, so you can show it again.
        </p>
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards" id="partner-table" data-reorder-url="{{ route('admin.partners.reorder') }}">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4" style="width:5.5rem"><span class="visually-hidden">Order</span></th>
                        <th scope="col">Logo</th>
                        <th scope="col">Name</th>
                        <th scope="col">Status</th>
                        <th scope="col">Featured</th>
                        <th scope="col" class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($partners as $p)
                    <tr data-id="{{ $p->id }}" @if ($reorderable) draggable="true" @endif>
                        <td data-label="Order" class="ps-4 text-nowrap">
                            @if ($reorderable)
                                <span class="text-muted me-1" aria-hidden="true" style="cursor:grab"><i class="fas fa-grip-vertical"></i></span>
                                <button type="button" class="btn btn-sm btn-light" data-move="-1" aria-label="Move {{ $p->name }} up"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
                                <button type="button" class="btn btn-sm btn-light" data-move="1" aria-label="Move {{ $p->name }} down"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
                            @else
                                {{ $p->sort_order }}
                            @endif
                        </td>
                        <td data-label="Logo"><img src="{{ $p->imgUrl() }}" alt="" width="48" height="48" loading="lazy" style="height:48px;width:48px;object-fit:contain;border-radius:50%;background:#f1f3f5"></td>
                        <td data-label="Name" class="fw-semibold">{{ $p->name }}@if ($p->website_url)<div class="small text-muted text-truncate" style="max-width:18rem">{{ $p->website_url }}</div>@endif</td>
                        <td data-label="Status">
                            @if ($p->active) <span class="badge text-bg-success">Visible</span> @else <span class="badge text-bg-secondary">Hidden</span> @endif
                        </td>
                        <td data-label="Featured">{!! $p->featured ? '<span class="badge text-bg-warning">Featured</span>' : '<span class="text-muted">&ndash;</span>' !!}</td>
                        <td data-label="Actions" class="pe-4">
                            <div class="action-buttons">
                                <a href="{{ route('admin.partners.edit', $p) }}" class="btn btn-sm btn-warning" aria-label="Edit {{ $p->name }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i></a>
                                @if ($p->active)
                                    <form action="{{ route('admin.partners.toggle', $p) }}" method="POST" data-sky-confirm="It disappears from the homepage but stays in this list, so you can show it again." data-sky-title="Hide {{ $p->name }}?" data-sky-ok="Hide" data-sky-variant="danger">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Hide {{ $p->name }}"><i class="fas fa-eye-slash" aria-hidden="true"></i></button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.partners.toggle', $p) }}" method="POST">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-success" aria-label="Show {{ $p->name }}"><i class="fas fa-eye" aria-hidden="true"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5">
                        <i class="fas fa-handshake fa-3x text-muted mb-3" aria-hidden="true"></i>
                        <p class="text-muted mb-1">{{ $q !== '' ? 'No partner matches your search.' : 'No partners yet. The homepage shows its built-in logo list.' }}</p>
                        <a href="{{ route('admin.partners.create') }}">Add a partner</a>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($partners->hasPages())<div class="card-footer">{{ $partners->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
</div>
<div class="visually-hidden" role="status" aria-live="polite" id="partner-live"></div>
@endsection

@push('scripts')
@if ($reorderable)
<script>
(function () {
    var table = document.getElementById('partner-table');
    var body = table.tBodies[0], live = document.getElementById('partner-live'), drag = null, busy = false;
    var token = document.querySelector('meta[name="csrf-token"]');
    function save() {
        var ids = [].map.call(body.querySelectorAll('tr[data-id]'), function (r) { return parseInt(r.dataset.id, 10); });
        busy = true;
        fetch(table.dataset.reorderUrl, {
            method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '{{ csrf_token() }}' },
            body: JSON.stringify({ ids: ids })
        }).then(function (r) { live.textContent = r.ok ? 'Order saved.' : 'The order could not be saved. Reload the page and try again.'; })
          .catch(function () { live.textContent = 'The order could not be saved (connection).'; })
          .then(function () { busy = false; });
    }
    body.addEventListener('dragstart', function (e) { drag = e.target.closest('tr'); if (drag) { e.dataTransfer.effectAllowed = 'move'; drag.style.opacity = '.5'; } });
    body.addEventListener('dragend', function () { if (drag) { drag.style.opacity = ''; drag = null; save(); } });
    body.addEventListener('dragover', function (e) {
        e.preventDefault();
        var over = e.target.closest('tr[data-id]');
        if (!drag || !over || over === drag) return;
        var r = over.getBoundingClientRect();
        body.insertBefore(drag, e.clientY > r.top + r.height / 2 ? over.nextSibling : over);
    });
    body.addEventListener('click', function (e) {
        var b = e.target.closest('[data-move]'); if (!b || busy) return;
        var row = b.closest('tr'), dir = parseInt(b.dataset.move, 10);
        var other = dir < 0 ? row.previousElementSibling : row.nextElementSibling;
        if (!other) return;
        body.insertBefore(row, dir < 0 ? other : other.nextSibling);
        b.focus(); save();
    });
})();
</script>
@endif
@endpush
