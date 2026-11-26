@extends('admin.layouts.master')
@php
    use Illuminate\Support\Str;
@endphp

@section('content')
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-0">📋 Services Catalog</h1>
            <small class="text-muted">{{ $services->count() }} services configured</small>
        </div>
        <a href="{{ route('admin.services.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add New Service
        </a>
    </div>

    @if($services->isEmpty())
        <div class="alert alert-info">No services found. Click "Add New Service" to get started.</div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            @foreach ($services as $service)
            @php
                $imagePath = Str::startsWith($service->image, 'storage/')
                    ? asset($service->image)
                    : asset('assets/images/services/' . ($service->image ?? 'placeholder.png'));
            @endphp
                <div class="col">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="position-relative">
                            <img src="{{ $imagePath }}" 
                                onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';"
                                class="card-img-top" 
                                alt="{{ $service->title }}" 
                                style="height: 180px; object-fit: cover;"
                            >
                            <div class="position-absolute top-0 end-0 m-2">
                                @if($service->is_archived)
                                    <span class="badge bg-secondary shadow-sm"><i class="fas fa-box-archive me-1"></i>Archived</span>
                                @elseif($service->is_published)
                                    <span class="badge bg-success shadow-sm"><i class="fas fa-check-circle me-1"></i>Published</span>
                                @else
                                    <span class="badge bg-warning text-dark shadow-sm"><i class="fas fa-file-pen me-1"></i>Draft</span>
                                @endif
                            </div>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0">{{ $service->title }}</h5>
                            </div>
                            <span class="badge bg-info text-dark align-self-start mb-2">
                                {{ $service->category->name ?? 'Uncategorized' }}
                            </span>
                            <p class="card-text text-muted mb-2 fw-semibold">
                                💰 {{ $service->price ? 'ETB ' . $service->price : 'Quote Request' }}
                            </p>

                            @if($service->features && is_array($service->features))
                                <ul class="list-group list-group-flush mb-3">
                                    @foreach (array_slice($service->features, 0, 3) as $feature)
                                        <li class="list-group-item small px-0 py-1 border-0 text-muted">✅ {{ $feature }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-outline-primary" title="Edit Service">
                                        <i class="fas fa-pen me-1"></i> Edit
                                    </a>
                                    
                                    {{-- Duplicate --}}
                                    <form action="{{ route('admin.services.duplicate', $service) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary" title="Duplicate as Draft">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>

                                    {{-- Toggle Publish --}}
                                    <form action="{{ route('admin.services.toggle-publish', $service) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-{{ $service->is_published ? 'warning' : 'success' }}" title="{{ $service->is_published ? 'Unpublish to Draft' : 'Publish' }}">
                                            <i class="fas {{ $service->is_published ? 'fa-eye-slash' : 'fa-check' }}"></i>
                                        </button>
                                    </form>

                                    {{-- Toggle Archive --}}
                                    <form action="{{ route('admin.services.toggle-archive', $service) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-dark" title="{{ $service->is_archived ? 'Unarchive' : 'Archive' }}">
                                            <i class="fas fa-box-archive"></i>
                                        </button>
                                    </form>
                                </div>

                                <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" class="d-inline-block" data-sky-confirm="Delete this service? It will be archived and hidden." data-sky-title="Delete service?" data-sky-variant="danger" data-sky-ok="Delete" onsubmit="return confirm('Move this service to trash?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Service">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
