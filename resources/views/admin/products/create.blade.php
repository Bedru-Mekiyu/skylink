@extends('admin.layouts.master')

@section('content')
<div class="container my-5">
    <h1 class="mb-4 text-center display-4 fw-bold">Create New Product</h1>
    <div class="card shadow-sm rounded-3 p-4">
        <form action="{{ route('admin.products.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf

            <!-- Category Dropdown -->
            <div class="mb-3">
                <label for="category" class="form-label fw-semibold">Category</label>
                <select class="form-select form-select-lg mb-3" id="category" name="category" required>
                    <option value="" disabled selected>Select a category</option>
                    <option value="industry-solutions">Industry Solutions</option>
                    <option value="business-tools">Business Tools</option>
                    <option value="web-mobile-apps">Web & Mobile Apps</option>
                    <option value="digital-services">Digital Services</option>
                </select>
                <div class="invalid-feedback">Please select a category.</div>
            </div>

            <!-- Slug -->
            <div class="mb-3">
                <label for="slug" class="form-label fw-semibold">Slug</label>
                <input type="text" class="form-control form-control-lg" id="slug" name="slug" placeholder="product-slug" required>
                <div class="invalid-feedback">Please enter a slug.</div>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Product Name</label>
                <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Product Name" required>
                <div class="invalid-feedback">Please enter the product name.</div>
            </div>

            <!-- Image URL -->
            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Image URL</label>
                <input type="url" class="form-control form-control-lg" id="image" name="image" placeholder="https://example.com/image.jpg" required>
                <div class="invalid-feedback">Please enter a valid image URL.</div>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea class="form-control form-control-lg" id="description" name="description" rows="4" placeholder="Describe the product..." required></textarea>
                <div class="invalid-feedback">Please enter a description.</div>
            </div>

            <!-- Price and Display Text -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="price" class="form-label fw-semibold">Price ($ / ETB numeric)</label>
                    <input type="number" class="form-control form-control-lg" id="price" name="price" min="0" step="0.01" placeholder="0.00" value="{{ old('price') }}" required>
                    <div class="invalid-feedback">Please enter a valid price.</div>
                </div>
                <div class="col-md-6">
                    <label for="price_text" class="form-label fw-semibold">Price Display Text (optional, e.g. "From 25,000 ETB")</label>
                    <input type="text" class="form-control form-control-lg" id="price_text" name="price_text" placeholder="e.g. From 25,000 ETB" value="{{ old('price_text') }}">
                    <div class="form-text">Custom text shown to visitors. Leave blank to default to numeric price.</div>
                </div>
            </div>

            <!-- Show Price Checkbox -->
            <div class="mb-3 form-check form-switch">
                <input type="hidden" name="show_price" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="show_price" name="show_price" value="1" {{ old('show_price', '1') == '1' ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="show_price">Show price on the public site</label>
            </div>

            <!-- Features (Tokens) -->
            <div class="mb-3">
                <label for="features" class="form-label fw-semibold">Features (comma separated)</label>
                <input type="text" class="form-control form-control-lg" id="features" name="features" placeholder="Fast, Reliable, User-Friendly" required>
                <div class="form-text">Enter features separated by commas.</div>
            </div>

            <!-- Additional Info -->
            <div class="mb-3">
                <label for="additional_info" class="form-label fw-semibold">Additional Information</label>
                <textarea class="form-control form-control-lg" id="additional_info" name="additional_info" rows="3" placeholder="Any extra details..."></textarea>
            </div>

            <!-- Submit Button -->
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow">Create Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap 5 Validation Script -->
<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (() => {
      'use strict'
      const forms = document.querySelectorAll('.needs-validation')
      Array.from(forms).forEach((form) => {
        form.addEventListener('submit', (event) => {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
          form.classList.add('was-validated')
        }, false)
      })
    })()
</script>
@endsection