@extends('admin.layouts.master')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">{{ $products->count() }} {{ \Illuminate\Support\Str::plural('product', $products->count()) }}</span>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i>Create new</a>
    </div>
    <div class="table-wrapper">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">Products</caption>
            <thead>
                <tr>
                    <th scope="col" class="ps-3">Product</th>
                    <th scope="col" class="d-none d-md-table-cell">Category</th>
                    <th scope="col" class="text-end">Price (ETB)</th>
                    <th scope="col" class="text-end pe-3"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $product->image }}" alt="" width="64" height="48" loading="lazy" class="rounded border flex-shrink-0" style="object-fit:cover" onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';">
                            <span class="fw-semibold">{{ $product->name }}</span>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell text-muted">{{ $product->category }}</td>
                    <td class="text-end text-nowrap">{{ number_format($product->price) }}</td>
                    <td class="text-end pe-3 text-nowrap">
                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-outline-info btn-sm" title="View" aria-label="View {{ $product->name }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-warning btn-sm" title="Edit" aria-label="Edit {{ $product->name }}"><i class="fas fa-pen" aria-hidden="true"></i></a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline-block" data-sky-confirm="Delete this product? This can't be undone from the admin." data-sky-title="Delete product?" data-sky-variant="danger" data-sky-ok="Delete" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete {{ $product->name }}"><i class="fas fa-trash" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
