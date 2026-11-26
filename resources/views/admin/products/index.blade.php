@extends('admin.layouts.master')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-0 fw-bold">📦 Products Directory</h5>
            <small class="text-muted">{{ $products->count() }} {{ \Illuminate\Support\Str::plural('product', $products->count()) }} configured</small>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i>Create new</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">Products</caption>
            <thead class="table-light">
                <tr>
                    <th scope="col" class="ps-3">Product</th>
                    <th scope="col" class="d-none d-md-table-cell">Category</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Price (ETB)</th>
                    <th scope="col" class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $product->image }}" alt="" width="56" height="42" loading="lazy" class="rounded border flex-shrink-0" style="object-fit:cover" onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';">
                            <div>
                                <span class="fw-semibold d-block text-dark">{{ $product->name }}</span>
                                <small class="text-muted d-block font-monospace">{{ $product->slug }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell text-muted">{{ $product->category }}</td>
                    <td>
                        @if($product->is_archived)
                            <span class="badge bg-secondary"><i class="fas fa-box-archive me-1"></i>Archived</span>
                        @elseif($product->is_published)
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Published</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="fas fa-file-pen me-1"></i>Draft</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap fw-semibold">{{ number_format($product->price) }}</td>
                    <td class="text-end pe-3 text-nowrap">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Product actions">
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-outline-info" title="View details" aria-label="View {{ $product->name }}">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-primary" title="Edit product" aria-label="Edit {{ $product->name }}">
                                <i class="fas fa-pen" aria-hidden="true"></i>
                            </a>
                            
                            {{-- Duplicate --}}
                            <form action="{{ route('admin.products.duplicate', $product) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary" title="Duplicate as Draft" aria-label="Duplicate {{ $product->name }}">
                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                </button>
                            </form>

                            {{-- Toggle Publish --}}
                            <form action="{{ route('admin.products.toggle-publish', $product) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-outline-{{ $product->is_published ? 'warning' : 'success' }}" title="{{ $product->is_published ? 'Unpublish to Draft' : 'Publish Product' }}" aria-label="Toggle publish for {{ $product->name }}">
                                    <i class="fas {{ $product->is_published ? 'fa-eye-slash' : 'fa-check' }}" aria-hidden="true"></i>
                                </button>
                            </form>

                            {{-- Toggle Archive --}}
                            <form action="{{ route('admin.products.toggle-archive', $product) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-outline-dark" title="{{ $product->is_archived ? 'Unarchive' : 'Archive' }}" aria-label="Toggle archive for {{ $product->name }}">
                                    <i class="fas fa-box-archive" aria-hidden="true"></i>
                                </button>
                            </form>

                            {{-- Soft Delete --}}
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline-block" data-sky-confirm="Delete this product? It will be archived and hidden from the public catalog." data-sky-title="Delete product?" data-sky-variant="danger" data-sky-ok="Delete" onsubmit="return confirm('Move this product to trash?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Delete product" aria-label="Delete {{ $product->name }}">
                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No products found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
