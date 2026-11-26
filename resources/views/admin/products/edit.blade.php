@extends('admin.layouts.master')

@section('content')
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">Edit Product: {{ $product->name }}</h1>
            <div class="d-flex align-items-center gap-2">
                @if($product->is_archived)
                    <span class="badge bg-secondary"><i class="fas fa-box-archive me-1"></i>Archived</span>
                @elseif($product->is_published)
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Published</span>
                @else
                    <span class="badge bg-warning text-dark"><i class="fas fa-file-pen me-1"></i>Draft</span>
                @endif
                <span class="text-muted small">Slug: <code>{{ $product->slug }}</code></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.products.duplicate', $product) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-copy me-1"></i> Duplicate
                </button>
            </form>
            <form action="{{ route('admin.products.toggle-publish', $product) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-{{ $product->is_published ? 'warning' : 'success' }} btn-sm">
                    <i class="fas {{ $product->is_published ? 'fa-eye-slash' : 'fa-check' }} me-1"></i>
                    {{ $product->is_published ? 'Unpublish' : 'Publish' }}
                </button>
            </form>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Products
            </a>
        </div>
    </div>


    @if(auth()->user()->hasRole('finance'))
        @include('admin.partials.sale-mode', ['type' => 'product', 'item' => $product])
    @endif

    <form action="{{ route('admin.products.update', $product) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Main Column: Core Product Info --}}
            <div class="col-lg-8">
                <div class="card shadow-sm rounded-3 p-4 border-0 mb-4">
                    <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">📦 Core Details (English)</h5>
                    
                    <!-- Category Dropdown -->
                    <div class="mb-3">
                        <label for="category" class="form-label fw-semibold">Category *</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary w-100 dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ $product->category ?? 'Select Category' }}
                            </button>
                            <ul class="dropdown-menu w-100" aria-labelledby="categoryDropdown">
                                @php
                                    $categories = ['industry-solutions', 'business-tools', 'web-mobile-apps', 'digital-services'];
                                @endphp
                                @foreach($categories as $category)
                                <li>
                                    <a class="dropdown-item {{ $product->category == $category ? 'active' : '' }}" href="#" data-category="{{ $category }}">
                                        {{ ucfirst(str_replace('-', ' ', $category)) }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                            <input type="hidden" name="category" id="categoryInput" value="{{ $product->category }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Name (English) *</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="slug" class="form-label fw-semibold">URL Slug *</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $product->slug) }}" required>
                        </div>
                    </div>

                    <!-- Image URL -->
                    <div class="mb-3">
                        <label for="image" class="form-label fw-semibold">Image URL *</label>
                        <input type="url" class="form-control" id="image" name="image" value="{{ old('image', $product->image) }}" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description (English) *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description', $product->description) }}</textarea>
                    </div>

                    <!-- Features -->
                    <div class="mb-3">
                        <label for="features" class="form-label fw-semibold">Features (Comma-separated)</label>
                        @php
                            $featuresVal = is_array($product->features) ? implode(', ', $product->features) : $product->features;
                        @endphp
                        <textarea class="form-control" id="features" name="features" rows="2" placeholder="Feature 1, Feature 2, Feature 3">{{ old('features', $featuresVal) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="additional_info" class="form-label fw-semibold">Technical specifications</label>
                        <textarea class="form-control" id="additional_info" name="additional_info" rows="3">{{ old('additional_info', $product->additional_info) }}</textarea>
                    </div>

                    @if($product->options->isNotEmpty())
                        <div class="alert alert-info small mb-3">Prices are set per plan in <strong>Pricing tiers</strong> below. The card shows the cheapest one as the starting price.</div>
                    @else
                    <!-- Price and Price Display Text -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label fw-semibold">Starting Price (Numeric ETB)</label>
                            <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label for="price_text" class="form-label fw-semibold">Display Price Text</label>
                            <input type="text" class="form-control" id="price_text" name="price_text" value="{{ old('price_text', $product->price_text) }}" placeholder="e.g. From 25,000 ETB">
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Bilingual Section (Amharic) --}}
                <div class="card shadow-sm rounded-3 p-4 border-0 mb-4 bg-light">
                    <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">🇪🇹 Bilingual Localization (Amharic / አማርኛ)</h5>
                    <div class="mb-3">
                        <label for="name_am" class="form-label fw-semibold">Product Name (Amharic / ስም በአማርኛ)</label>
                        <input type="text" class="form-control" id="name_am" name="name_am" value="{{ old('name_am', $product->name_am) }}" placeholder="የምርቱ ስም">
                    </div>
                    <div class="mb-3">
                        <label for="description_am" class="form-label fw-semibold">Description (Amharic / መግለጫ በአማርኛ)</label>
                        <textarea class="form-control" id="description_am" name="description_am" rows="3" placeholder="የምርቱ ሙሉ መግለጫ...">{{ old('description_am', $product->description_am) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="features_am" class="form-label fw-semibold">Features (Amharic / ዝርዝር ባህሪያት - Comma separated)</label>
                        @php
                            $featuresAmVal = is_array($product->features_am) ? implode(', ', $product->features_am) : $product->features_am;
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
                            <input type="text" class="form-control" id="plans_heading" name="plans_heading" maxlength="150" value="{{ old('plans_heading', $product->plans_heading) }}" placeholder="Leave empty for the standard heading">
                        </div>
                        <div class="col-md-6">
                            <label for="header_title" class="form-label fw-semibold">Header Custom Title</label>
                            <input type="text" class="form-control" id="header_title" name="header_title" value="{{ old('header_title', $product->header_title) }}" placeholder="Default: Product name">
                        </div>
                        <div class="col-md-6">
                            <label for="header_cta_text" class="form-label fw-semibold">Header CTA Button Text</label>
                            <input type="text" class="form-control" id="header_cta_text" name="header_cta_text" value="{{ old('header_cta_text', $product->header_cta_text) }}" placeholder="e.g. Request a Quote / View Plans">
                        </div>
                        <div class="col-12">
                            <label for="header_subtitle" class="form-label fw-semibold">Header Subtitle</label>
                            <input type="text" class="form-control" id="header_subtitle" name="header_subtitle" value="{{ old('header_subtitle', $product->header_subtitle) }}" placeholder="Short compelling subtitle">
                        </div>
                        <div class="col-12">
                            <label for="header_image" class="form-label fw-semibold">Header Banner Background Image URL</label>
                            <input type="url" class="form-control" id="header_image" name="header_image" value="{{ old('header_image', $product->header_image) }}" placeholder="https://...">
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
                        <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" {{ old('is_published', $product->is_published ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_published">Published (Visible in Catalog)</label>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_archived" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_archived" name="is_archived" value="1" {{ old('is_archived', $product->is_archived ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_archived">Archived (Hidden from visitors)</label>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="show_price" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="show_price" name="show_price" value="1" {{ old('show_price', $product->show_price ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="show_price">Show Price publicly</label>
                    </div>

                    <div class="mb-4">
                        <label for="sort_order" class="form-label fw-semibold">Catalog Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}">
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
        @include('admin.partials.option-editor', ['type' => 'product', 'item' => $product])
    @endif
</div>

<!-- Custom Scripts for Dropdown -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle category selection
    document.querySelectorAll('.dropdown-menu a').forEach(function (element) {
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedCategory = this.getAttribute('data-category');
            const displayText = this.textContent.trim();
            document.querySelector('#categoryDropdown').textContent = displayText;
            document.querySelector('#categoryInput').value = selectedCategory;
            // Highlight active item
            document.querySelectorAll('.dropdown-item').forEach(function (item) {
                item.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
});
</script>
@endsection
