@extends('admin.layouts.master')

@section('title', 'Edit News Article')
@section('page-title', 'Edit News')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.news.index') }}">News</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit article</li>
    </ol>
</nav>

<form action="{{ route('admin.news.update', $news->id) }}" method="POST" enctype="multipart/form-data" data-news-form>
    @method('PUT')
    @csrf
    <div class="card news-form-card">
        <div class="card-header">
            <h2 class="h5 mb-0 text-break" lang="{{ $news->language }}"><i class="fas fa-edit me-2" aria-hidden="true"></i>Editing: {{ $news->title }}</h2>
        </div>
        <div class="card-body">
            @include('admin.news.form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('admin.news.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-success" data-save-button>
                <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                <i class="fas fa-save me-1" aria-hidden="true"></i><span data-label>Save changes</span>
            </button>
        </div>
    </div>
</form>
@endsection
