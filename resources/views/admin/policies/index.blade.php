@extends('admin.layouts.master')

@section('title', 'Legal & Policy Pages')
@section('page-title', 'Legal & Policy Pages')

@section('content')
<div class="container-fluid">

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Store Legal & Policy Pages</h5>
                    <p class="text-muted small mb-0">Manage customer policies for payments, cancellations, and terms. Unpublished policies are hidden from the website.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Policy Document</th>
                            <th>Slug / URL</th>
                            <th>Status</th>
                            <th>Content Status</th>
                            <th>Last Updated</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($policies as $p)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $p->title }}</div>
                                </td>
                                <td>
                                    <code>/legal/{{ $p->slug }}</code>
                                </td>
                                <td>
                                    @if($p->is_published)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                            <i class="fas fa-check-circle me-1"></i> Published
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">
                                            <i class="fas fa-eye-slash me-1"></i> Unpublished (Draft)
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if(empty($p->content))
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                            <i class="fas fa-pen me-1"></i> Empty (Needs Input)
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border rounded-pill px-2 py-1">
                                            {{ Str::words(strip_tags($p->content), 4) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $p->updated_at?->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.policies.edit', $p) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i> Edit Policy
                                    </a>
                                    @if($p->is_published && !empty($p->content))
                                        <a href="{{ route('legal.show', $p->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1" title="View Public Page">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
