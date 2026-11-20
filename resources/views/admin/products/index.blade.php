@extends('admin.layouts.master')

@section('content')
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-4 fw-bold">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-lg rounded-pill shadow-sm d-flex align-items-center">
            <i class="bi bi-plus-lg me-2"></i> Create New Product
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Category</th>
                    <th scope="col">Price ($)</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr class="transition-hover">
                    <td class="fw-semibold">{{ $product->name }}</td>
                    <td>{{ $product->category }}</td>
                    <td>{{ number_format($product->price, 2) }}</td>
                    <td class="text-center">
                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-outline-info btn-sm me-2" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-warning btn-sm me-2" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline-block" data-sky-confirm="Delete this product? This can't be undone from the admin." data-sky-title="Delete product?" data-sky-variant="danger" data-sky-ok="Delete" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Optional: Add some custom styles for hover effects -->
<style>
.transition-hover:hover {
    background-color: #f8f9fa;
    transition: background-color 0.3s ease;
}
</style>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ENjdO4Dr2bkBIFxQpeo6Fj6Y6K6f6/p1Q1zE3v7b3b4UGaXKJ0O5Qb7J7VfR5vV" crossorigin="anonymous">

<!-- Bootstrap Icons CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
@endsection