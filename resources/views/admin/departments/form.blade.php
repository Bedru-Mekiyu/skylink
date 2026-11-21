@extends('admin.layouts.master')

@php($editing = $item->exists)
@section('title', $editing ? 'Edit department' : 'Add department')
@section('page-title', $editing ? 'Edit department' : 'Add department')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-people-group me-2" aria-hidden="true"></i>{{ $editing ? $item->name : 'New department' }}</h2></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.departments.update', $item) : route('admin.departments.store') }}" novalidate>
                    @csrf
                    @if ($editing) @method('PUT') @endif

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required maxlength="120">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="tagline" class="form-label">Short description <span class="text-muted">(optional)</span></label>
                        <input type="text" id="tagline" name="tagline" class="form-control @error('tagline') is-invalid @enderror" value="{{ old('tagline', $item->tagline) }}" maxlength="200">
                        @error('tagline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="icon" class="form-label">Icon <span class="text-muted">(optional)</span></label>
                            <input type="text" id="icon" name="icon" class="form-control @error('icon') is-invalid @enderror" value="{{ old('icon', $item->icon) }}" maxlength="60" placeholder="fas fa-code">
                            @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">A Font Awesome class such as <code>fas fa-code</code>, <code>fas fa-bullhorn</code>, <code>fas fa-headset</code>. Empty = a people icon.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="accent" class="form-label">Colour</label>
                            <select id="accent" name="accent" class="form-select">
                                @foreach (\App\Models\Department::ACCENTS as $a)<option value="{{ $a }}" @selected(old('accent', $item->accent) === $a)>{{ ucfirst($a) }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Picture <span class="text-muted">(optional)</span></label>
                        <input type="file" id="image" name="image" class="form-control @error('image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">JPG, PNG or WebP, up to 4 MB. A wide web copy is made; your original is kept privately.</div>
                        @if ($editing && $item->imageUrl())
                            <div class="d-flex align-items-center gap-3 mt-2">
                                <img src="{{ $item->imageUrl() }}" alt="" width="120" height="75" style="object-fit:cover;border-radius:8px">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image"><label class="form-check-label" for="remove_image">Remove the picture</label></div>
                            </div>
                        @endif
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label for="image_label" class="form-label">Text on the picture <span class="text-muted">(optional)</span></label><input type="text" id="image_label" name="image_label" class="form-control" maxlength="80" value="{{ old('image_label', $item->image_label) }}"></div>
                        <div class="col-md-6"><label for="image_alt" class="form-label">Picture description <span class="text-muted">(screen readers)</span></label><input type="text" id="image_alt" name="image_alt" class="form-control" maxlength="200" value="{{ old('image_alt', $item->image_alt) }}"></div>
                    </div>

                    <fieldset class="mb-3">
                        <legend class="form-label fs-6">Numbers <span class="text-muted">(0 to 4, each a number plus a label, e.g. 15+ Developers)</span></legend>
                        @error('stat_n')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @php($stats = old('stat_n') !== null ? collect(old('stat_n'))->map(fn ($n, $i) => ['n' => $n, 'l' => old('stat_l')[$i] ?? ''])->all() : ($item->stats ?? []))
                        @for ($i = 0; $i < 4; $i++)
                            <div class="row g-2 mb-2">
                                <div class="col-4 col-md-3"><label class="visually-hidden" for="stat_n_{{ $i }}">Number {{ $i + 1 }}</label><input type="text" id="stat_n_{{ $i }}" name="stat_n[{{ $i }}]" class="form-control" maxlength="12" placeholder="15+" value="{{ $stats[$i]['n'] ?? '' }}"></div>
                                <div class="col-8 col-md-5"><label class="visually-hidden" for="stat_l_{{ $i }}">Label {{ $i + 1 }}</label><input type="text" id="stat_l_{{ $i }}" name="stat_l[{{ $i }}]" class="form-control" maxlength="40" placeholder="Developers" value="{{ $stats[$i]['l'] ?? '' }}"></div>
                            </div>
                        @endfor
                    </fieldset>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3"><label for="sort_order" class="form-label">Order</label><input type="number" id="sort_order" name="sort_order" min="0" max="100000" class="form-control" value="{{ old('sort_order', $item->sort_order) }}"></div>
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <input type="hidden" name="visible" value="0">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="visible" value="1" id="visible" @checked(old('visible', $item->visible))><label class="form-check-label" for="visible">Visible on the About page</label></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add department' }}</button>
                        <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
