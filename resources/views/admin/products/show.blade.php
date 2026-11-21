@extends('admin.layouts.master')

@section('content')
<div class="container my-5">
    <h1 class="mb-4 text-center display-4 fw-bold text-primary">Product Details</h1>
    <div class="card shadow-sm rounded-3 p-4">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr class="table-light">
                        <th scope="row" style="width:20%;">
                            <i class="bi bi-hash-circle-fill me-2 text-primary"></i>ID
                        </th>
                        <td>{{ $product->id }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="table-light">
                            <i class="bi bi-folder-fill me-2 text-primary"></i>Category
                        </th>
                        <td>
                            <span class="badge bg-info text-dark">{{ $product->category }}</span>
                        </td>
                    </tr>
                    <tr class="table-light">
                        <th scope="row">
                            <i class="bi bi-link-45deg me-2 text-primary"></i>Slug
                        </th>
                        <td>{{ $product->slug }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="table-light">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>Name
                        </th>
                        <td>{{ $product->name }}</td>
                    </tr>
                    <tr class="table-light">
                        <th scope="row">
                            <i class="bi bi-image-fill me-2 text-primary"></i>Image
                        </th>
                        <td>
                            @if($product->image)
                                <img src="{{ \Illuminate\Support\Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-width:200px;">
                            @else
                                <span class="text-muted">No image available</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="table-light">
                        <th scope="row">
                            <i class="bi bi-card-text me-2 text-primary"></i>Description
                        </th>
                        <td>{{ $product->description }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="table-light">
                            <i class="bi bi-currency-dollar me-2 text-primary"></i>Price
                        </th>
                        <td>{{ $product->price }}</td>
                    </tr>
                    <tr class="table-light">
                        <th scope="row">
                            <i class="bi bi-star-fill me-2 text-primary"></i>Features
                        </th>
                        <td>
                            @if(is_array($product->features) && count($product->features))
                                <ul class="list-group list-group-flush">
                                    @foreach($product->features as $feature)
                                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-muted">No features available</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="table-light">
                            <i class="bi bi-info-circle-fill me-2 text-primary"></i>Additional Info
                        </th>
                        <td>{{ $product->additional_info ?? 'N/A' }}</td>
                    </tr>
                    <tr class="table-light">
                        <th scope="row">
                            <i class="bi bi-calendar-check-fill me-2 text-primary"></i>Created At
                        </th>
                        <td>{{ $product->created_at }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="table-light">
                            <i class="bi bi-calendar-check me-2 text-primary"></i>Updated At
                        </th>
                        <td>{{ $product->updated_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection