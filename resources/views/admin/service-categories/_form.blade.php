{{-- Shared create/edit form for a service category. Expects $category. --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-control" maxlength="255" required
               value="{{ old('name', $category->name) }}" placeholder="e.g. Enterprise Network Solution">
        <div class="form-text">Full name, shown on the category's own page and in the admin.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="card_title">Card title</label>
        <input type="text" id="card_title" name="card_title" class="form-control" maxlength="255"
               value="{{ old('card_title', $category->card_title) }}" placeholder="Leave empty to use the name">
        <div class="form-text">Heading on the Services page card (can be shorter than the name).</div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label" for="filter_label">Filter button text</label>
        <input type="text" id="filter_label" name="filter_label" class="form-control" maxlength="60"
               value="{{ old('filter_label', $category->filter_label) }}" placeholder="e.g. Networking">
        <div class="form-text">Short word for the filter button above the cards. Empty = the card title.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="sort_order">Order <span class="text-danger">*</span></label>
        <input type="number" id="sort_order" name="sort_order" class="form-control" min="0" max="65535" required
               value="{{ old('sort_order', $category->sort_order) }}">
        <div class="form-text">Lower numbers come first.</div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
        <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="quote_only" name="quote_only" value="1" @checked(old('quote_only', $category->pricing_mode === 'quote_only')) @disabled(! auth()->user()->hasRole('finance'))>
    <label class="form-check-label" for="quote_only">Priced per project: hide prices for every service here and link to the support form</label>
</div>
<div class="form-check mt-3">
            <input type="hidden" name="is_published" value="0">
            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1"
                   @checked(old('is_published', $category->is_published))>
            <label class="form-check-label" for="is_published">Show on the website</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="description">Short description <span class="text-danger">*</span></label>
    <textarea id="description" name="description" class="form-control" rows="2" maxlength="300" required
              placeholder="One sentence for the card">{{ old('description', $category->description) }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label" for="image">Card image @unless ($category->exists)<span class="text-danger">*</span>@endunless</label>
    @if ($category->exists && $category->image)
        <div class="mb-2"><img src="{{ $category->image_url }}" alt="Current image" class="img-thumbnail" style="max-height: 140px;"></div>
    @endif
    <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"
           @unless ($category->exists) required @endunless onchange="previewCategoryImage(event)">
    <div class="form-text">JPG, PNG or WebP, up to 5 MB. Landscape works best (16:9, about 1280 × 720).
        @if ($category->exists) Leave empty to keep the current image. @endif</div>
    <img id="imagePreview" src="#" alt="Image preview" class="img-thumbnail mt-2" style="display: none; max-height: 140px;">
</div>

@push('scripts')
<script>
    function previewCategoryImage(event) {
        var file = event.target.files && event.target.files[0];
        var img = document.getElementById('imagePreview');
        if (!file) { img.style.display = 'none'; return; }
        img.src = URL.createObjectURL(file);
        img.style.display = 'block';
    }
</script>
@endpush
