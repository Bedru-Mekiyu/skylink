@extends('admin.layouts.master')

@php($editing = $item->exists)
@section('title', $editing ? 'Edit testimonial' : 'Add testimonial')
@section('page-title', $editing ? 'Edit testimonial' : 'Add testimonial')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-comment-dots me-2" aria-hidden="true"></i>{{ $editing ? $item->name : 'New testimonial' }}</h2></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.testimonials.update', $item) : route('admin.testimonials.store') }}" novalidate>
                    @csrf
                    @if ($editing) @method('PUT') @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required maxlength="120">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role / company <span class="text-muted">(optional)</span></label>
                            <input type="text" id="role" name="role" class="form-control @error('role') is-invalid @enderror" value="{{ old('role', $item->role) }}" maxlength="160" placeholder="e.g. CUSTOMER or Manager, Example PLC">
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quote_en" class="form-label">Quote in English</label>
                        <textarea id="quote_en" name="quote_en" rows="4" maxlength="1200" class="form-control @error('quote_en') is-invalid @enderror">{{ old('quote_en', $item->quote_en) }}</textarea>
                        @error('quote_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="quote_am" class="form-label">Quote in Amharic</label>
                        <textarea id="quote_am" name="quote_am" rows="4" maxlength="1200" lang="am" class="form-control @error('quote_am') is-invalid @enderror">{{ old('quote_am', $item->quote_am) }}</textarea>
                        @error('quote_am')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">At least one of the two quotes is required. Visitors see the quote in their language; if it is empty, the other one is shown.</div>
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo <span class="text-muted">(optional)</span></label>
                        <input type="file" id="photo" name="photo" class="form-control @error('photo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">JPG, PNG or WebP, up to 4 MB. It is cropped to a small square; your original is kept privately. Without a photo the person icon is shown.</div>
                        @if ($editing && $item->photoUrl())
                            <div class="d-flex align-items-center gap-3 mt-2">
                                <img src="{{ $item->photoUrl() }}" alt="" width="64" height="64" style="border-radius:50%;object-fit:cover">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="remove_photo"><label class="form-check-label" for="remove_photo">Remove the photo</label></div>
                            </div>
                        @endif
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <label for="rating" class="form-label">Rating <span class="text-muted">(optional)</span></label>
                            <select id="rating" name="rating" class="form-select">
                                <option value="">None</option>
                                @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected((string) old('rating', $item->rating) === (string) $i)>{{ $i }} {{ $i === 1 ? 'star' : 'stars' }}</option>@endfor
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="sort_order" class="form-label">Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" max="100000" class="form-control" value="{{ old('sort_order', $item->sort_order) }}">
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <input type="hidden" name="visible" value="0">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="visible" value="1" id="visible" @checked(old('visible', $item->visible))><label class="form-check-label" for="visible">Visible on the site</label></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary" data-sky-busy>{{ $editing ? 'Save changes' : 'Add testimonial' }}</button>
                        <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
