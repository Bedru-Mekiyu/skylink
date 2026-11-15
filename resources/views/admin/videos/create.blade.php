@extends('admin.layouts.master')

@section('title', 'Add New Video')
@section('page-title', 'Add Video')

@section('content')

{{-- Breadcrumb for better navigation context --}}
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.videos.index') }}">Videos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Add New Video</li>
    </ol>
</nav>

<div class="row">
    <div class="col-12">
        <form action="{{ route('admin.videos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-video me-2"></i>New Video Details</h5>
                </div>
                <div class="card-body">
                    {{-- This includes the shared form fields --}}
                    @include('admin.videos._form')
                </div>
                <div class="card-footer text-end d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.videos.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Create Video
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
{{-- JavaScript for a live preview of the thumbnail image --}}
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
                        thumbnailPreviewContainer.classList.remove('d-none');
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
@endpush