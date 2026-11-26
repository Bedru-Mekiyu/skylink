@extends('admin.layouts.master')

@section('title', 'Edit ' . $policy->title)
@section('page-title', 'Edit Legal Policy')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Edit Policy: {{ $policy->title }}</h5>
                            <p class="text-muted small mb-0">Slug: <code>/legal/{{ $policy->slug }}</code></p>
                        </div>
                        <a href="{{ route('admin.policies.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-arrow-left me-1"></i> Back to Policies
                        </a>
                    </div>

                    <form method="POST" action="{{ route('admin.policies.update', $policy) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="policy-title">Document Title *</label>
                            <input type="text" name="title" id="policy-title" class="form-control" value="{{ old('title', $policy->title) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="policy-content">Policy Content (English)</label>
                            <textarea name="content" id="policy-content" class="form-control" rows="12" placeholder="Paste or type official policy text here...">{{ old('content', $policy->content) }}</textarea>
                            <div class="form-text small text-muted">HTML paragraphs and bullet points are supported.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small" for="policy-content-am">Policy Content (Amharic - Optional)</label>
                            <textarea name="content_am" id="policy-content-am" class="form-control" rows="8" placeholder="የፖሊሲው የአማርኛ ዝርዝር...">{{ old('content_am', $policy->content_am) }}</textarea>
                        </div>

                        <div class="card bg-light border-0 rounded-3 p-3 mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" @checked(old('is_published', $policy->is_published))>
                                <label class="form-check-label fw-bold text-dark" for="is_published">
                                    Publish this policy publicly
                                </label>
                            </div>
                            <small class="text-muted ps-4">When enabled, this policy becomes accessible at <code>/legal/{{ $policy->slug }}</code> and is linked at checkout.</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.policies.index') }}" class="btn btn-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-save me-1"></i> Save Policy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
