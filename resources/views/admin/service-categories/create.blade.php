@extends('admin.layouts.master')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">🗂️ Add Service Category</h2>
    <form action="{{ route('admin.service-categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.service-categories._form')
        <button type="submit" class="btn btn-success mt-2">🚀 Create Category</button>
        <a href="{{ route('admin.service-categories.index') }}" class="btn btn-outline-secondary mt-2">Cancel</a>
    </form>
</div>
@endsection
