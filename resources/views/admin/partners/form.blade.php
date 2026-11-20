@extends('admin.layouts.master')

@php($editing = $partner->exists)
@section('title', $editing ? 'Edit partner' : 'Add partner')
@section('page-title', $editing ? 'Edit partner' : 'Add partner')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-handshake me-2" aria-hidden="true"></i>{{ $editing ? $partner->name : 'New partner' }}</h2></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="partner-form"
                      action="{{ $editing ? route('admin.partners.update', $partner) : route('admin.partners.store') }}" novalidate>
                    @csrf
                    @if ($editing) @method('PUT') @endif

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $partner->name) }}" required maxlength="120">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo @unless ($editing)<span class="text-danger" aria-hidden="true">*</span>@endunless</label>
                        <input type="file" id="logo" name="logo" class="form-control @error('logo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" @unless ($editing) required @endunless aria-describedby="logo-help">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="logo-help" class="form-text">JPG, PNG or WebP, up to 2 MB (transparent PNG / WebP is fine). A small web copy is made automatically; your original is kept privately.@if ($editing) Leave empty to keep the current logo.@endif</div>
                    </div>

                    <div class="mb-3">
                        <label for="alt" class="form-label">Alt text <span class="text-muted">(read aloud by screen readers)</span></label>
                        <input type="text" id="alt" name="alt" class="form-control @error('alt') is-invalid @enderror" value="{{ old('alt', $partner->alt) }}" maxlength="160" placeholder="Defaults to the name">
                        @error('alt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="website_url" class="form-label">Website <span class="text-muted">(optional, https://)</span></label>
                        <input type="url" id="website_url" name="website_url" class="form-control @error('website_url') is-invalid @enderror" value="{{ old('website_url', $partner->website_url) }}" maxlength="255" placeholder="https://example.com">
                        @error('website_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">If set, the logo links to it and opens in a new tab.</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Short description <span class="text-muted">(optional)</span></label>
                        <input type="text" id="description" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $partner->description) }}" maxlength="255">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="since_year" class="form-label">Partner since <span class="text-muted">(year)</span></label>
                            <input type="number" id="since_year" name="since_year" class="form-control @error('since_year') is-invalid @enderror" value="{{ old('since_year', $partner->since_year) }}" min="1980" max="{{ date('Y') }}">
                            @error('since_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label for="sort_order" class="form-label">Order</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $partner->sort_order) }}" min="0">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="featured" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="featured" name="featured" value="1" @checked(old('featured', $partner->featured))>
                            <label class="form-check-label" for="featured"><strong>Featured</strong> <span class="text-muted">&mdash; shown first on the homepage</span></label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="active" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1" @checked(old('active', $partner->active))>
                            <label class="form-check-label" for="active"><strong>Visible</strong> <span class="text-muted">&mdash; untick to hide it from the homepage</span></label>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary" id="partner-submit"><i class="fas fa-save me-1" aria-hidden="true"></i>{{ $editing ? 'Save changes' : 'Add partner' }}</button>
                        <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-eye me-2" aria-hidden="true"></i>Live preview</h2></div>
            <div class="card-body">
                <p class="small text-muted">How the logo looks in the homepage marquee (round, 90 px high).</p>
                <div class="d-flex align-items-center justify-content-center gap-4 py-3 bg-white border rounded overflow-hidden" aria-live="polite">
                    <img id="pv-logo" src="{{ $editing ? $partner->imgUrl() : '' }}" alt="" style="height:90px;max-width:220px;object-fit:contain;border-radius:50%" @unless ($editing) hidden @endunless>
                    <span id="pv-empty" class="text-muted small" @if ($editing) hidden @endif>Choose a logo to preview it.</span>
                </div>
                <p class="fw-semibold text-center mt-3 mb-0" id="pv-name">{{ old('name', $partner->name) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('partner-form'), file = document.getElementById('logo'),
        img = document.getElementById('pv-logo'), empty = document.getElementById('pv-empty'),
        name = document.getElementById('name'), pvName = document.getElementById('pv-name'), btn = document.getElementById('partner-submit');
    file.addEventListener('change', function () {
        var f = file.files[0];
        if (!f) return;
        if (f.size > 2 * 1024 * 1024) { file.value = ''; showLogoError('This logo is larger than 2 MB. Please choose a smaller file.'); return; }
        img.src = URL.createObjectURL(f); img.hidden = false; empty.hidden = true;
    });
    function showLogoError(msg) { // in-page message instead of a browser alert
        var d = document.getElementById('logo-help'); d.textContent = msg; d.classList.add('text-danger');
    }
    name.addEventListener('input', function () { pvName.textContent = name.value; });
    form.addEventListener('submit', function (e) {
        if (btn.disabled) { e.preventDefault(); return; }       // double-submit protection
        if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Saving...';
    });
})();
</script>
@endpush
