@extends('admin.layouts.master')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
@php
    $pe = $errors->getBag('profile');
    $pw = $errors->getBag('password');
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-user-edit me-2" aria-hidden="true"></i>Profile details</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.profile.update') }}" id="profile-form" novalidate>
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" class="form-control @if ($pe->has('name')) is-invalid @endif" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                        @if ($pe->has('name'))<div class="invalid-feedback">{{ $pe->first('name') }}</div>@endif
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control @if ($pe->has('email')) is-invalid @endif" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                        @if ($pe->has('email'))<div class="invalid-feedback">{{ $pe->first('email') }}</div>@endif
                    </div>

                    <div class="mb-4">
                        <label for="profile_current_password" class="form-label">Current password <span class="text-muted">(only needed to change your email)</span></label>
                        <div class="input-group">
                            <input type="password" id="profile_current_password" name="current_password" class="form-control @if ($pe->has('current_password')) is-invalid @endif" autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="profile_current_password" aria-label="Show password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                            @if ($pe->has('current_password'))<div class="invalid-feedback">{{ $pe->first('current_password') }}</div>@endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1" aria-hidden="true"></i>Save profile</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-key me-2" aria-hidden="true"></i>Change password</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.profile.password') }}" id="password-form" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password" class="form-control @if ($pw->has('current_password')) is-invalid @endif" required autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="current_password" aria-label="Show password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                            @if ($pw->has('current_password'))<div class="invalid-feedback">{{ $pw->first('current_password') }}</div>@endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control @if ($pw->has('password')) is-invalid @endif" required minlength="8" autocomplete="new-password" aria-describedby="password_help">
                            <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="password" aria-label="Show password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                            @if ($pw->has('password'))<div class="invalid-feedback">{{ $pw->first('password') }}</div>@endif
                        </div>
                        <div class="form-text" id="password_help">At least 8 characters.</div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <div class="input-group">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="password_confirmation" aria-label="Show password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <p class="small text-muted">After the change, every other device or browser that was signed in to this account is signed out. This one stays signed in.</p>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-lock me-1" aria-hidden="true"></i>Change password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Show/hide for every password box.
    document.querySelectorAll('.js-toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    });
</script>
@endpush
