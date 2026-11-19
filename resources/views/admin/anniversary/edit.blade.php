@extends('admin.layouts.master')

@section('title', 'Anniversary Ceremony')
@section('page-title', 'Anniversary Ceremony')

@section('content')
@php
    $live = str_starts_with($status, 'Currently LIVE');
@endphp
<div class="alert {{ $live ? 'alert-success' : 'alert-secondary' }}" role="status" id="anniversary-status">
    <i class="fas {{ $live ? 'fa-circle-check' : 'fa-eye-slash' }} me-2" aria-hidden="true"></i><strong>{{ $status }}</strong>
</div>

<div class="card">
    <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-champagne-glasses me-2" aria-hidden="true"></i>5-year anniversary ceremony</h2></div>
    <div class="card-body">
        <p class="text-muted mb-4">
            The ceremony is the full-screen intro, the homepage panel and the small strips on the other pages.
            It never shows unless it is live for visitors (or you are previewing it as a logged-in admin).
            @unless ($saved) Nothing has been saved yet, so the dates below are the built-in defaults. @endunless
        </p>

        <form method="POST" action="{{ route('admin.anniversary.update') }}" id="anniversary-form">
            @csrf
            @method('PUT')

            <fieldset class="mb-4">
                <legend class="h6">Mode</legend>
                @foreach ([
                    'auto' => ['Automatic', 'Live only between the start and end time below.'],
                    'off' => ['Off', 'Hidden for everyone, always.'],
                    'on' => ['On now', 'Live immediately for all visitors, until the end time.'],
                ] as $value => [$label, $help])
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="mode" id="mode-{{ $value }}" value="{{ $value }}" @checked(old('mode', $mode) === $value)>
                        <label class="form-check-label" for="mode-{{ $value }}"><strong>{{ $label }}</strong> <span class="text-muted">&mdash; {{ $help }}</span></label>
                    </div>
                @endforeach
                @error('mode')<div class="text-danger small">{{ $message }}</div>@enderror
            </fieldset>

            <div class="row g-3 mb-2">
                <div class="col-12 col-md-6">
                    <label for="starts_at" class="form-label">Start <span class="text-muted">(Africa/Addis_Ababa time)</span></label>
                    <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" id="starts_at" name="starts_at" value="{{ old('starts_at', $start) }}" required>
                    @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="ends_at" class="form-label">End <span class="text-muted">(Africa/Addis_Ababa time, not included)</span></label>
                    <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" id="ends_at" name="ends_at" value="{{ old('ends_at', $end) }}" required>
                    @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <p class="small text-muted mb-4">All times are Ethiopian local time ({{ $timezone }}), whatever the time zone of your computer.</p>

            <div class="form-check mb-4">
                <input type="hidden" name="admin_preview" value="0">
                <input class="form-check-input" type="checkbox" name="admin_preview" id="admin_preview" value="1" @checked(old('admin_preview', $adminPreview))>
                <label class="form-check-label" for="admin_preview"><strong>Admin preview</strong> <span class="text-muted">&mdash; while you are logged in as an admin, show the ceremony on the live site even when it is hidden for visitors. Visitors never see it.</span></label>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1" aria-hidden="true"></i>Save</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Switching to "On now" publishes the ceremony to every visitor at once: ask first.
    (function () {
        var form = document.getElementById('anniversary-form');
        if (!form) return;
        var wasOn = {{ $mode === 'on' ? 'true' : 'false' }};
        form.addEventListener('submit', function (e) {
            var chosen = form.querySelector('input[name="mode"]:checked');
            if (chosen && chosen.value === 'on' && !wasOn &&
                !window.confirm('Switch the anniversary ceremony ON now?\n\nEvery visitor will see it immediately, until the end time.')) {
                e.preventDefault();
            }
        });
    })();
</script>
@endpush
