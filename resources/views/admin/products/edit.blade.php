@extends('admin.layouts.master')

@section('content')
<div class="container my-5">
    <h1 class="mb-4 text-center display-4 fw-bold text-primary">Edit Product</h1>
    <div class="card shadow-sm rounded-3 p-4">
        <form action="{{ route('admin.products.update', $product) }}" method="POST" class="needs-validation" novalidate>
            @csrf
            @method('PUT')
            
            <!-- Category Dropdown -->
            <div class="mb-3">
                <label for="category" class="form-label fw-semibold">Category</label>
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

            <!-- Slug -->
            <div class="mb-3">
                <label for="slug" class="form-label fw-semibold">Slug</label>
                <input type="text" class="form-control" id="slug" name="slug" value="{{ $product->slug }}" required>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $product->name }}" required>
            </div>

            <!-- Image URL -->
            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Image URL</label>
                <input type="url" class="form-control" id="image" name="image" value="{{ $product->image }}" required>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required>{{ $product->description }}</textarea>
            </div>

            <!-- Price and Price Display Text -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="price" class="form-label fw-semibold">Price (Numeric ETB)</label>
                    <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01">
                </div>
                <div class="col-md-6">
                    <label for="price_text" class="form-label fw-semibold">Display Price Text (e.g. "From 25,000 ETB")</label>
                    <input type="text" class="form-control" id="price_text" name="price_text" value="{{ old('price_text', $product->price_text) }}" placeholder="e.g. From 25,000 ETB">
                </div>
            </div>

            <!-- Show Price Checkbox -->
            <div class="mb-3 form-check form-switch">
                <input type="hidden" name="show_price" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="show_price" name="show_price" value="1" {{ old('show_price', $product->show_price ?? true) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="show_price">Show price on the public site</label>
            </div>

            <!-- Submit Button -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Scripts for Dropdown -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle category selection
    document.querySelectorAll('.dropdown-menu a').forEach(function (element) {
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedCategory = this.getAttribute('data-category');
            const displayText = this.textContent;
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