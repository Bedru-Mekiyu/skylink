@extends('admin.layouts.master')

@section('title', 'Manage Portfolio')
@section('page-title', 'Portfolio Projects')

@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Portfolio Projects</h5>
        <a href="{{ route('admin.portfolio.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Project
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
            {{-- ✨ ADDED: 'table-responsive-cards' enables the mobile card view --}}
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4" style="width: 15%;">Image</th>
                        <th scope="col">Title</th>
                        <th scope="col" style="width: 40%;">Description</th>
                        <th scope="col" class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr>
                        {{-- ✨ ADDED: data-label attributes for mobile view --}}
                        <td data-label="Image" class="ps-4">
                            <img src="{{ asset('storage/' . $project->image) }}" alt="{{ $project->title }}" style="width: 100px; height: 60px; object-fit: cover; border-radius: 0.25rem;">
                        </td>
                        <td data-label="Title" class="fw-bold">{{ $project->title }}</td>
                        <td data-label="Description">{{ Str::limit($project->description, 80) }}</td>
                        <td data-label="Actions" class="pe-4">
                            
                            {{-- ✨ UPGRADE: Buttons are now in a spaced container with icons --}}
                            <div class="action-buttons">
                                <a href="{{ route('admin.portfolio.edit', $project->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                
                                {{-- Your working delete form --}}
                                <form action="{{ route('admin.portfolio.destroy', $project->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No portfolio projects found.</h5>
                            <p>Get started by <a href="{{ route('admin.portfolio.create') }}">adding a new project</a>.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Safely place pagination in the footer --}}
    @if ($projects->hasPages())
    <div class="card-footer">
        {{ $projects->links() }}
    </div>
    @endif
</div>
@endsection