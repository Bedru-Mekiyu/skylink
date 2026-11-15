@extends('admin.layouts.master')

@section('title', 'Create News Article')
@section('page-title', 'Create News')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.news.index') }}">News</a></li>
        <li class="breadcrumb-item active" aria-current="page">New article</li>
    </ol>
</nav>

<form action="{{ route('admin.news.store') }}" method="POST" enctype="multipart/form-data" data-news-form>
    @csrf
    <div class="card news-form-card">
        <div class="card-header">
            <h2 class="h5 mb-0">New article</h2>
        </div>
        <div class="card-body">
            @include('admin.news.form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('admin.news.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary" data-save-button>
                <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                <span data-label>Save article</span>
            </button>
        </div>
    </div>
</form>
@endsection
