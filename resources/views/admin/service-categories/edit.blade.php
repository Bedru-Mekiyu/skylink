@extends('admin.layouts.master')

@section('content')
<div class="container mt-5">
    <h2 class="mb-1">✏️ Edit Service Category</h2>
    <p class="text-muted mb-4">Web address: <code>/services/category/{{ $category->slug }}</code> (fixed, so existing links keep working).</p>
    <form action="{{ route('admin.service-categories.update', $category) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.service-categories._form')
        <button type="submit" class="btn btn-success mt-2">💾 Save Changes</button>
        <a href="{{ route('admin.service-categories.index') }}" class="btn btn-outline-secondary mt-2">Cancel</a>
    </form>
</div>
@endsection
