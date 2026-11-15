@extends('admin.layouts.master')

@section('title', 'Manage Videos')
@section('page-title', 'Manage Videos')

@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-film me-2"></i>All Videos</h5>
        <a href="{{ route('admin.videos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Video
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            {{-- ✨ ADDED: 'table-responsive-cards' class enables the mobile card view --}}
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4">Title</th>
                        <th scope="col">Category</th>
                        <th scope="col">Created At</th>
                        <th scope="col" class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($videos as $video)
                        <tr>
                            {{-- ✨ ADDED: data-label attributes for mobile view --}}
                            <td data-label="Title" class="fw-bold ps-4">{{ $video->title }}</td>
                            <td data-label="Category">{{ $video->category->name ?? 'N/A' }}</td>
                            <td data-label="Created">{{ $video->created_at->format('M d, Y') }}</td>
                            <td data-label="Actions" class="pe-4">

                                {{-- ✨ UPGRADE: Buttons are in a spaced container with icons --}}
                                <div class="action-buttons">
                                    <a href="{{ route('admin.videos.edit', $video) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <form action="{{ route('admin.videos.destroy', $video) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this video?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="fas fa-video-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No videos found.</h5>
                                <p>Get started by <a href="{{ route('admin.videos.create') }}">adding a new video</a>.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection