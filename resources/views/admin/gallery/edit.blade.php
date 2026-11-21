@extends('admin.layouts.master')

@section('title', 'Edit photo')
@section('page-title', 'Edit photo')

@section('content')
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card"><div class="card-body">
            <img src="{{ asset('storage/'.$image->display_path) }}" alt="{{ $image->altText() }}" width="{{ $image->display_w }}" height="{{ $image->display_h }}" class="img-fluid rounded">
        </div></div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header"><h2 class="h5 mb-0">Captions and order</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.gallery.update', $image) }}" id="gallery-edit" novalidate>
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label for="caption_en" class="form-label">Caption (English)</label>
                        <input type="text" id="caption_en" name="caption_en" class="form-control @error('caption_en') is-invalid @enderror" value="{{ old('caption_en', $image->caption_en) }}" maxlength="200">
                        @error('caption_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Also used as the picture's description for screen readers.</div>
                    </div>
                    <div class="mb-3">
                        <label for="caption_am" class="form-label">Caption (Amharic)</label>
                        <input type="text" id="caption_am" name="caption_am" lang="am" class="form-control @error('caption_am') is-invalid @enderror" value="{{ old('caption_am', $image->caption_am) }}" maxlength="200">
                        @error('caption_am')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="taken_on" class="form-label">Date <span class="text-muted">(optional)</span></label>
                            <input type="date" id="taken_on" name="taken_on" class="form-control @error('taken_on') is-invalid @enderror" value="{{ old('taken_on', $image->taken_on?->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
                            @error('taken_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label for="sort_order" class="form-label">Order</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $image->sort_order) }}" min="0">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input type="hidden" name="visible" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="visible" name="visible" value="1" @checked(old('visible', $image->visible))>
                        <label class="form-check-label" for="visible"><strong>Visible</strong> <span class="text-muted">&mdash; untick to hide it from the About page</span></label>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary" id="gallery-save"><i class="fas fa-save me-1" aria-hidden="true"></i>Save</button>
                        <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="card mt-4 border-danger-subtle"><div class="card-body d-flex flex-wrap gap-3 justify-content-between align-items-center">
    <div><h2 class="h6 mb-1">Delete this photo</h2><p class="small text-muted mb-0">Removes the photo and its files (thumbnail, display copy and the archived original) for good. To keep it, untick &quot;Visible&quot; and save instead.</p></div>
    <form action="{{ route('admin.gallery.destroy', $image) }}" method="POST" data-sky-confirm="Delete this photo? The photo and all its files are removed for good. (To keep it, use Hide instead.)" data-sky-title="Delete photo?" data-sky-variant="danger" data-sky-ok="Delete">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger" style="min-height:44px"><i class="fas fa-trash me-1" aria-hidden="true"></i>Delete photo</button>
    </form>
</div></div>
@endsection

@push('scripts')
<script>
document.getElementById('gallery-edit').addEventListener('submit', function (e) {
    var b = document.getElementById('gallery-save'); if (b.disabled) { e.preventDefault(); return; }
    b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Saving...';
});
</script>
@endpush
