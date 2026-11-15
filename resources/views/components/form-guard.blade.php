{{--
    Spam protection for public forms (no CAPTCHA). Put <x-form-guard /> inside
    every form that posts to a route using the "form.guard" middleware.
    - Honeypot: hidden from people (off-screen, aria-hidden, not focusable).
    - Encrypted render timestamp for the minimum time-to-submit check.
    - Shows the friendly message when a submission was blocked or rate limited.
--}}
@php
    $guard = \App\Http\Middleware\ProtectPublicForm::class;
    $honeypotId = 'hp-'.\Illuminate\Support\Str::random(8);
    $message = $errors->getBag($guard::ERROR_BAG)->first('form');
@endphp

@if ($message)
    <div class="alert alert-warning" role="alert">{{ $message }}</div>
@endif

<div aria-hidden="true" style="position:absolute !important; left:-10000px !important; top:auto; width:1px; height:1px; overflow:hidden;">
    <label for="{{ $honeypotId }}">Leave this field empty</label>
    <input type="text" id="{{ $honeypotId }}" name="{{ $guard::HONEYPOT }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ $guard::TIMESTAMP }}" value="{{ $guard::issueTimestamp() }}">
