@extends('admin.layouts.master')

@section('title', 'Gallery')
@section('page-title', 'Gallery')

@section('content')
<div class="card mb-4">
    <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-cloud-upload-alt me-2" aria-hidden="true"></i>Add photos</h2></div>
    <div class="card-body">
        <p class="small mb-3"><i class="fas fa-circle-info me-1" aria-hidden="true"></i><strong>Only upload photos you are allowed to show publicly (for example, with the partners' or clients' permission).</strong></p>
        <form id="gallery-upload" data-url="{{ route('admin.gallery.store') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="photos" class="form-label">Choose one or many photos</label>
                <input type="file" id="photos" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="photos-help">
                <div id="photos-help" class="form-text">JPG, PNG or WebP, up to 8 MB each (this server accepts {{ round($limit / 1048576) }} MB per upload). Photos are resized automatically; your originals are kept privately. New photos go to the end of the gallery; add captions afterwards with the pencil button.</div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-12 col-md-6"><label for="cap-en" class="form-label">Caption for all (English, optional)</label><input type="text" id="cap-en" class="form-control" maxlength="200"></div>
                <div class="col-12 col-md-6"><label for="cap-am" class="form-label">Caption for all (Amharic, optional)</label><input type="text" id="cap-am" class="form-control" maxlength="200" lang="am"></div>
            </div>
            <button type="submit" class="btn btn-primary" id="upload-btn"><i class="fas fa-upload me-1" aria-hidden="true"></i>Upload</button>
            <div class="progress mt-3 d-none" id="upload-progress" role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="height:1.25rem"><div class="progress-bar" style="width:0%">0%</div></div>
            <ul class="list-unstyled small mt-3 mb-0" id="upload-log" aria-live="polite"></ul>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="fas fa-images me-2" aria-hidden="true"></i>Photos</h2>
        <form method="GET" action="{{ route('admin.gallery.index') }}" class="d-flex flex-nowrap gap-2" role="search">
            <label for="q" class="visually-hidden">Search captions</label>
            <input type="search" id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Search captions">
            <button class="btn btn-outline-secondary text-nowrap" type="submit">Search</button>
        </form>
    </div>
    <div class="card-body p-0">
        <p class="small text-muted px-4 pt-3 mb-2">
            Photos revolve on the About page in this order. @if ($reorderable) Drag a row (or use the arrows) to change the order; it saves by itself. @else Clear the search to reorder. @endif
            Hiding a photo keeps it here, so you can show it again. With no visible photos the gallery section is not shown at all.
        </p>
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards" id="gallery-table" data-reorder-url="{{ route('admin.gallery.reorder') }}">
                <thead><tr>
                    <th scope="col" class="ps-4" style="width:5.5rem"><span class="visually-hidden">Order</span></th>
                    <th scope="col">Photo</th><th scope="col">Captions</th><th scope="col">Status</th><th scope="col" class="text-end pe-4">Actions</th>
                </tr></thead>
                <tbody>
                @forelse ($images as $g)
                    <tr data-id="{{ $g->id }}" @if ($reorderable) draggable="true" @endif>
                        <td data-label="Order" class="ps-4 text-nowrap">
                            @if ($reorderable)
                                <span class="text-muted me-1" aria-hidden="true" style="cursor:grab"><i class="fas fa-grip-vertical"></i></span>
                                <button type="button" class="btn btn-sm btn-light" data-move="-1" aria-label="Move {{ $g->altText() }} up"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
                                <button type="button" class="btn btn-sm btn-light" data-move="1" aria-label="Move {{ $g->altText() }} down"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
                            @else {{ $g->sort_order }} @endif
                        </td>
                        <td data-label="Photo"><img src="{{ asset('storage/'.$g->thumb_path) }}" alt="{{ $g->altText() }}" width="{{ $g->thumb_w }}" height="{{ $g->thumb_h }}" loading="lazy" style="width:120px;height:80px;object-fit:cover;border-radius:8px"></td>
                        <td data-label="Captions">
                            <div>{{ $g->caption_en ?: '—' }}</div>
                            <div class="text-muted" lang="am">{{ $g->caption_am ?: '' }}</div>
                            @if ($g->taken_on)<div class="small text-muted">{{ $g->taken_on->format('M Y') }}</div>@endif
                        </td>
                        <td data-label="Status">@if ($g->visible)<span class="badge text-bg-success">Visible</span>@else<span class="badge text-bg-secondary">Hidden</span>@endif</td>
                        <td data-label="Actions" class="pe-4">
                            <div class="action-buttons">
                                <a href="{{ route('admin.gallery.edit', $g) }}" class="btn btn-sm btn-warning" aria-label="Edit {{ $g->altText() }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i></a>
                                @if ($g->visible)
                                    <form action="{{ route('admin.gallery.toggle', $g) }}" method="POST" data-sky-confirm="It disappears from the About page but stays here, so you can show it again." data-sky-title="Hide this photo?" data-sky-ok="Hide" data-sky-variant="danger">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Hide {{ $g->altText() }}"><i class="fas fa-eye-slash" aria-hidden="true"></i></button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.gallery.toggle', $g) }}" method="POST">@csrf @method('PUT')<button type="submit" class="btn btn-sm btn-outline-success" aria-label="Show {{ $g->altText() }}"><i class="fas fa-eye" aria-hidden="true"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5"><i class="fas fa-images fa-3x text-muted mb-3" aria-hidden="true"></i><p class="text-muted mb-0">{{ $q !== '' ? 'No photo matches your search.' : 'No photos yet. The gallery section stays hidden until you add one.' }}</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($images->hasPages())<div class="card-footer">{{ $images->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
</div>
<div class="visually-hidden" role="status" aria-live="polite" id="gallery-live"></div>
@endsection

@push('scripts')
<script>
(function () {
    // ---- bulk upload: one request per photo, one overall progress bar ----
    var form = document.getElementById('gallery-upload'), input = document.getElementById('photos'), btn = document.getElementById('upload-btn'),
        bar = document.getElementById('upload-progress'), fill = bar.firstElementChild, log = document.getElementById('upload-log'),
        token = form.querySelector('input[name=_token]').value, MAX = 8 * 1024 * 1024, busy = false;
    function line(text, cls) { var li = document.createElement('li'); li.className = cls || ''; li.textContent = text; log.appendChild(li); }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (busy) return;                                   // double-submit protection
        var files = [].slice.call(input.files);
        log.innerHTML = '';
        if (!files.length) { line('Choose at least one photo.', 'text-danger'); return; }
        busy = true; btn.disabled = true; bar.classList.remove('d-none');
        var total = files.reduce(function (s, f) { return s + f.size; }, 0), done = 0, ok = 0, failed = 0, i = 0;
        function setBar(loadedNow) { var p = Math.min(100, Math.round(((done + loadedNow) / total) * 100)); fill.style.width = p + '%'; fill.textContent = p + '%'; bar.setAttribute('aria-valuenow', p); }
        (function next() {
            if (i >= files.length) {
                busy = false; btn.disabled = false; input.value = '';
                line(ok + ' photo(s) added' + (failed ? ', ' + failed + ' failed' : '') + '.', failed ? 'text-warning' : 'text-success');
                if (ok) setTimeout(function () { window.location.reload(); }, 1200);
                return;
            }
            var f = files[i++];
            if (f.size > MAX) { failed++; done += f.size; line(f.name + ': larger than 8 MB, skipped.', 'text-danger'); setBar(0); return next(); }
            var fd = new FormData(); fd.append('_token', token); fd.append('photo', f);
            fd.append('caption_en', document.getElementById('cap-en').value); fd.append('caption_am', document.getElementById('cap-am').value);
            var x = new XMLHttpRequest(); x.open('POST', form.dataset.url); x.setRequestHeader('Accept', 'application/json');
            x.upload.onprogress = function (ev) { if (ev.lengthComputable) setBar(ev.loaded); };
            x.onload = function () {
                var b = {}; try { b = JSON.parse(x.responseText); } catch (er) {}
                done += f.size;
                if (x.status >= 200 && x.status < 300 && b.ok) { ok++; line(f.name + ': added.', 'text-success'); }
                else { failed++; var m = b.errors && b.errors.photo ? b.errors.photo[0] : (x.status === 429 ? 'Too many uploads, wait a while.' : x.status === 419 ? 'Session expired: reload the page.' : 'Upload failed (error ' + x.status + ').'); line(f.name + ': ' + m, 'text-danger'); }
                setBar(0); next();
            };
            x.onerror = function () { failed++; done += f.size; line(f.name + ': connection dropped.', 'text-danger'); setBar(0); next(); };
            x.send(fd);
        })();
    });

    @if ($reorderable)
    // ---- drag / arrow reorder (saves by itself) ----
    var table = document.getElementById('gallery-table'), body = table.tBodies[0], live = document.getElementById('gallery-live'), drag = null, saving = false;
    function save() {
        var ids = [].map.call(body.querySelectorAll('tr[data-id]'), function (r) { return parseInt(r.dataset.id, 10); }); saving = true;
        fetch(table.dataset.reorderUrl, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ ids: ids }) })
            .then(function (r) { live.textContent = r.ok ? 'Order saved.' : 'The order could not be saved. Reload and try again.'; })
            .catch(function () { live.textContent = 'The order could not be saved (connection).'; }).then(function () { saving = false; });
    }
    body.addEventListener('dragstart', function (e) { drag = e.target.closest('tr'); if (drag) { e.dataTransfer.effectAllowed = 'move'; drag.style.opacity = '.5'; } });
    body.addEventListener('dragend', function () { if (drag) { drag.style.opacity = ''; drag = null; save(); } });
    body.addEventListener('dragover', function (e) { e.preventDefault(); var over = e.target.closest('tr[data-id]'); if (!drag || !over || over === drag) return; var r = over.getBoundingClientRect(); body.insertBefore(drag, e.clientY > r.top + r.height / 2 ? over.nextSibling : over); });
    body.addEventListener('click', function (e) {
        var b = e.target.closest('[data-move]'); if (!b || saving) return;
        var row = b.closest('tr'), dir = parseInt(b.dataset.move, 10), other = dir < 0 ? row.previousElementSibling : row.nextElementSibling;
        if (!other) return; body.insertBefore(row, dir < 0 ? other : other.nextSibling); b.focus(); save();
    });
    @endif
})();
</script>
@endpush
