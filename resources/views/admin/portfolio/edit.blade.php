@extends('admin.layouts.master')

@section('title', 'Edit Project')
@section('page-title', 'Edit Portfolio Project')

@section('content')

{{-- Breadcrumb for better navigation context --}}
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.portfolio.index') }}">Portfolio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Project</li>
    </ol>
</nav>

<div class="row">
    <div class="col-12">
        {{-- Your working form tag is preserved perfectly --}}
        <form action="{{ route('admin.portfolio.update', $project) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- The form is now wrapped in our standard, attractive card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editing: {{ $project->title }}</h5>
                </div>
                <div class="card-body">
                    {{-- Validation errors display --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Your working form fields, now with better styling and arrangement --}}
                    <div class="mb-3">
                        <label for="title" class="form-label">Project Title</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $project->title) }}" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Project Description</label>
                        <textarea name="description" id="description" class="form-control" rows="5" required>{{ old('description', $project->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="project_url" class="form-label">Project URL (Live Link)</label>
                        <input type="url" name="project_url" id="project_url" class="form-control" value="{{ old('project_url', $project->project_url) }}" placeholder="https://www.thefinishedproject.com">
                        <small class="form-text text-muted">Optional: The full URL to the live project.</small>
                    </div>

                    {{-- Image Section --}}
                    <div class="row">
                        <div class="col-md-8">
                             <div class="mb-3">
                                <label for="image-upload" class="form-label">Change Image (optional)</label>
                                <input type="file" name="image" id="image-upload" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="mb-3">
                                <label class="form-label">Current Image:</label><br>
                                <img src="{{ asset('storage/' . $project->image) }}" style="width: 100%; max-width: 200px; border-radius: 0.5rem; border: 1px solid #ddd;" alt="{{ $project->title }}">
                            </div>
                        </div>
                    </div>
                     <div id="image-preview-container" class="mt-2 d-none">
                        <label class="form-label">New Image Preview:</label><br>
                        <img id="image-preview" src="#" alt="New Image Preview" style="max-width: 200px; border-radius: 0.5rem; border: 1px solid #ddd;">
                    </div>

                    @include('admin.portfolio._video-fields', ['project' => $project])

                </div>
                <div class="card-footer text-end d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.portfolio.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Project
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection


@push('scripts')
{{-- JavaScript for a live preview of the NEW image --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageUploadInput = document.getElementById('image-upload');
        const imagePreviewContainer = document.getElementById('image-preview-container');
        const imagePreview = document.getElementById('image-preview');

        imageUploadInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('d-none'); // Show the preview container
                }
                reader.readAsDataURL(file);
            }
        });
    });
</script>
@endpush