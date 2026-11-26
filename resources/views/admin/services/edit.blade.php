@extends('admin.layouts.master')

@section('content')
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">Edit Service: {{ $service->title }}</h1>
            <div class="d-flex align-items-center gap-2">
                @if($service->is_archived)
                    <span class="badge bg-secondary"><i class="fas fa-box-archive me-1"></i>Archived</span>
                @elseif($service->is_published)
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Published</span>
                @else
                    <span class="badge bg-warning text-dark"><i class="fas fa-file-pen me-1"></i>Draft</span>
                @endif
                <span class="text-muted small">Slug: <code>{{ $service->slug }}</code></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.services.duplicate', $service) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-copy me-1"></i> Duplicate
                </button>
            </form>
            <form action="{{ route('admin.services.toggle-publish', $service) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-{{ $service->is_published ? 'warning' : 'success' }} btn-sm">
                    <i class="fas {{ $service->is_published ? 'fa-eye-slash' : 'fa-check' }} me-1"></i>
                    {{ $service->is_published ? 'Unpublish' : 'Publish' }}
                </button>
            </form>
            <a href="{{ route('admin.services.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Services
            </a>
        </div>
    </div>


    @if(auth()->user()->hasRole('finance'))
        @include('admin.partials.sale-mode', ['type' => 'service', 'item' => $service])
    @endif

    <form action="{{ route('admin.services.update', $service->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Main Column --}}
            <div class="col-lg-8">
                <div class="card shadow-sm rounded-3 p-4 border-0 mb-4">
                    <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">🛠️ Service Core Details (English)</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Title (English) *</label>
                            <input name="title" value="{{ old('title', $service->title) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Slug *</label>
                            <input name="slug" value="{{ old('slug', $service->slug) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $cat->id == $service->category_id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description (English) *</label>
                        <textarea name="desc" class="form-control" rows="4" required>{{ old('desc', $service->desc) }}</textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Service Image</label><br>
                            <img src="{{ asset($service->image) }}" alt="Service Image" class="img-thumbnail mb-2" style="max-height: 120px;" onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';">
                            <input type="file" name="image" class="form-control" onchange="previewImage(event)">
                            <img id="imagePreview" class="img-thumbnail mt-2" style="display: none; max-height: 120px;">
                        </div>

                        @if($service->options->isNotEmpty())
                            <div class="col-md-6"><div class="alert alert-info small mb-0">Prices are set per plan in <strong>Pricing tiers</strong> below. The card shows the cheapest one as the starting price.</div></div>
                        @else
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price (Display Text / ETB)</label>
                            <input name="price" value="{{ old('price', $service->price) }}" class="form-control" @disabled(! auth()->user()->hasRole('finance')) placeholder="e.g. From 10,000 ETB">
                            <small class="text-muted d-block mt-1">Starting price shown on service cards when price visibility is turned on.</small>
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Key Features</label>
                        <div id="feature-list">
                            @foreach ($service->features as $feature)
                                <div class="input-group mb-2">
                                    <input type="text" name="features[]" class="form-control" value="{{ $feature }}" required>
                                    <button class="btn btn-danger remove-feature" type="button"><i class="fas fa-times"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-feature"><i class="fas fa-plus me-1"></i> Add Feature</button>
                    </div>
                </div>

                {{-- Bilingual Section (Amharic) --}}
                <div class="card shadow-sm rounded-3 p-4 border-0 mb-4 bg-light">
                    <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">🇪🇹 Bilingual Localization (Amharic / አማርኛ)</h5>
                    <div class="mb-3">
                        <label for="title_am" class="form-label fw-semibold">Service Title (Amharic / የአገልግሎት ስም)</label>
                        <input type="text" class="form-control" id="title_am" name="title_am" value="{{ old('title_am', $service->title_am) }}" placeholder="የአገልግሎቱ ስም በአማርኛ">
                    </div>
                    <div class="mb-3">
                        <label for="desc_am" class="form-label fw-semibold">Description (Amharic / መግለጫ በአማርኛ)</label>
                        <textarea class="form-control" id="desc_am" name="desc_am" rows="3" placeholder="የአገልግሎቱ ሙሉ መግለጫ...">{{ old('desc_am', $service->desc_am) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="features_am" class="form-label fw-semibold">Features (Amharic / ዝርዝር ባህሪያት - Comma separated)</label>
                        @php
                            $featuresAmVal = is_array($service->features_am) ? implode(', ', $service->features_am) : $service->features_am;
                        @endphp
                        <textarea class="form-control" id="features_am" name="features_am" rows="2" placeholder="ባህሪ 1, ባህሪ 2, ባህሪ 3">{{ old('features_am', $featuresAmVal) }}</textarea>
                    </div>
                </div>

                {{-- Page Header Customization --}}
                <div class="card shadow-sm rounded-3 p-4 border-0 mb-4">
                    <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">🎨 Detail Page Header Banner Overrides</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="plans_heading" class="form-label fw-semibold">Heading above the plans</label>
                            <input type="text" class="form-control" id="plans_heading" name="plans_heading" maxlength="150" value="{{ old('plans_heading', $service->plans_heading) }}" placeholder="Leave empty for the standard heading">
                        </div>
                        <div class="col-md-6">
                            <label for="header_title" class="form-label fw-semibold">Header Custom Title</label>
                            <input type="text" class="form-control" id="header_title" name="header_title" value="{{ old('header_title', $service->header_title) }}" placeholder="Default: Service title">
                        </div>
                        <div class="col-md-6">
                            <label for="header_cta_text" class="form-label fw-semibold">Header CTA Button Text</label>
                            <input type="text" class="form-control" id="header_cta_text" name="header_cta_text" value="{{ old('header_cta_text', $service->header_cta_text) }}" placeholder="e.g. Request a Quote / View Plans">
                        </div>
                        <div class="col-12">
                            <label for="header_subtitle" class="form-label fw-semibold">Header Subtitle</label>
                            <input type="text" class="form-control" id="header_subtitle" name="header_subtitle" value="{{ old('header_subtitle', $service->header_subtitle) }}" placeholder="Short compelling subtitle">
                        </div>
                        <div class="col-12">
                            <label for="header_image" class="form-label fw-semibold">Header Banner Background Image URL</label>
                            <input type="text" class="form-control" id="header_image" name="header_image" value="{{ old('header_image', $service->header_image) }}" placeholder="storage/... or https://...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar Column: Publishing, Sorting, and Save --}}
            <div class="col-lg-4">
                <div class="card shadow-sm rounded-3 p-4 border-0 mb-4 sticky-top" style="top: 20px;">
                    <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">⚙️ Status & Settings</h5>

                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_published" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" {{ old('is_published', $service->is_published ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_published">Published (Visible in Catalog)</label>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_archived" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_archived" name="is_archived" value="1" {{ old('is_archived', $service->is_archived ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_archived">Archived (Hidden from visitors)</label>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="show_price" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="show_price" name="show_price" value="1" {{ old('show_price', $service->show_price ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="show_price">Show Price publicly</label>
                    </div>

                    <div class="mb-4">
                        <label for="sort_order" class="form-label fw-semibold">Catalog Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}">
                        <small class="text-muted">Lower numbers appear first in the catalog.</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Pricing Packages & Tiers Management --}}
    <div class="card shadow-sm border-0 mt-2 mb-5 rounded-3">
        @if(auth()->user()->hasRole('finance'))
        @include('admin.partials.option-editor', ['type' => 'service', 'item' => $service])
    @endif
</div>

@push('scripts')
<script>
    // Add new feature input
    document.getElementById('add-feature').addEventListener('click', function () {
        const featureList = document.getElementById('feature-list');
        const inputGroup = document.createElement('div');
        inputGroup.classList.add('input-group', 'mb-2');
        inputGroup.innerHTML = `
            <input type="text" name="features[]" class="form-control" placeholder="Enter feature" required>
            <button class="btn btn-danger remove-feature" type="button"><i class="fas fa-times"></i></button>
        `;
        featureList.appendChild(inputGroup);
    });

    // Remove feature input
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-feature')) {
            e.target.closest('.input-group').remove();
        }
    });

    // Preview new image
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function () {
            const output = document.getElementById('imagePreview');
            output.src = reader.result;
            output.style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
@endpush
@endsection
