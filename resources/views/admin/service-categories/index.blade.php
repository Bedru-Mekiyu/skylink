@extends('admin.layouts.master')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="h3">🗂️ Service Categories</h1>
        <a href="{{ route('admin.service-categories.create') }}" class="btn btn-primary">+ Add New Category</a>
    </div>
    <p class="text-muted mb-4">Each category is a card and a filter button on the <a href="{{ route('services') }}" target="_blank" rel="noopener">Services page</a>, in the order below (lowest number first). Its services are listed on its own page.</p>

    @if ($categories->isEmpty())
        <div class="alert alert-info">No categories yet. Click "Add New Category" to get started.</div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @foreach ($categories as $category)
                <div class="col">
                    <div class="card shadow-sm h-100 {{ $category->is_published ? '' : 'opacity-75' }}">
                        <img src="{{ $category->image_url }}" class="card-img-top" alt="{{ $category->card_heading }}"
                             style="height: 180px; object-fit: cover;"
                             onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1">{{ $category->card_heading }}</h5>
                            <div class="mb-2">
                                <span class="badge bg-secondary">Order {{ $category->sort_order }}</span>
                                <span class="badge bg-info">{{ $category->services_count }} {{ Str::plural('service', $category->services_count) }}</span>
                                @if ($category->is_published)
                                    <span class="badge bg-success">Shown</span>
                                @else
                                    <span class="badge bg-warning text-dark">Hidden</span>
                                @endif
                            </div>
                            <p class="card-text text-muted small mb-3">{{ $category->description }}</p>
                            <div class="mt-auto d-flex justify-content-between">
                                <a href="{{ route('admin.service-categories.edit', $category) }}" class="btn btn-sm btn-outline-warning">✏️ Edit</a>
                                <a href="{{ route('services.category', ['category' => $category->slug]) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">👁️ View</a>
                                <form action="{{ route('admin.service-categories.destroy', $category) }}" method="POST"
                                      data-sky-confirm="Delete the category  “{{ addslashes($category->card_heading) }}”? This cannot be undone.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $categories->links() }}</div>
    @endif
</div>
@endsection
