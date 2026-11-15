@extends('admin.layouts.master')

@section('title', 'Add New Project')
@section('page-title', 'Add Portfolio Project')

@section('content')

{{-- Breadcrumb for better navigation context --}}
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.portfolio.index') }}">Portfolio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Add New Project</li>
    </ol>
</nav>

<div class="row">
    <div class="col-12">
        <form action="{{ route('admin.portfolio.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- The entire form is now inside our standard, attractive card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>New Portfolio Project</h5>
                </div>
                <div class="card-body">
                    {{-- This block will display any validation errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Your working form fields, now with better spacing and labels --}}
                    <div class="mb-3">
                        <label for="title" class="form-label">Project Title</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Project Description</label>
                        <textarea name="description" id="description" class="form-control" rows="5" required>{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="project_url" class="form-label">Project URL (Live Link)</label>
                        <input type="url" name="project_url" id="project_url" class="form-control" value="{{ old('project_url') }}" placeholder="https://www.thefinishedproject.com">
                        <small class="form-text text-muted">Optional: The full URL to the live project.</small>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Project Image</label>
                        <input type="file" name="image" id="image" class="form-control" required>
                    </div>

                    @include('admin.portfolio._video-fields')

                </div>
                <div class="card-footer text-end d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.portfolio.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Save Project
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection