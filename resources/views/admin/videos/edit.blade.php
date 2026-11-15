@extends('admin.layouts.master')

@section('title', 'Edit Video')
@section('page-title', 'Edit Video')

@section('content')

{{-- Breadcrumb for better navigation context --}}
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.videos.index') }}">Videos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Video</li>
    </ol>
</nav>

<div class="row">
    <div class="col-12">
        {{-- Your working form tag is preserved perfectly --}}
        <form action="{{ route('admin.videos.update', $video) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editing: {{ Str::limit($video->title, 40) }}</h5>
                </div>
                <div class="card-body">
                    {{-- This includes your shared form fields. No changes needed to _form.blade.php --}}
                    @include('admin.videos._form')
                </div>
                <div class="card-footer text-end d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.videos.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Video
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
{{-- JavaScript for a live preview of a NEW thumbnail image --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const thumbnailUploadInput = document.getElementById('thumbnail-upload');
        const thumbnailPreviewContainer = document.getElementById('thumbnail-preview-container');
        const thumbnailPreview = document.getElementById('thumbnail-preview');

        if(thumbnailUploadInput) {
            thumbnailUploadInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        thumbnailPreview.src = e.target.result;
                        // Ensure the container's label says "New Image Preview"
                        thumbnailPreviewContainer.querySelector('small').textContent = 'New Image Preview:';
                        thumbnailPreviewContainer.classList.remove('d-none');
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
@endpush