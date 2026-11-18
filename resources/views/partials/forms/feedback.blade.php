{{--
    Shown at the top of a public message form, so the visitor sees the result
    right where they are (the redirect lands on #{{ $anchor }}).
    Expects: $anchor (one of App\Http\Requests\InquiryRequest::ANCHORS).
    Covers: success (with reference number), validation errors, and the
    honeypot / timing / rate-limit messages (error bag "formGuard").
--}}
@php
    $done = session('inquiry_success');
    $isThisForm = old('form_anchor', $done['anchor'] ?? null) === $anchor;
    $guardErrors = $errors->getBag(\App\Http\Middleware\ProtectPublicForm::ERROR_BAG);
@endphp

@if ($done && ($done['anchor'] ?? null) === $anchor)
    <div class="form-feedback form-feedback--ok" role="status" tabindex="-1" data-form-feedback>
        <i class="fas fa-check-circle" aria-hidden="true"></i>
        <div>
            <strong>Thank you! Your message has been received.</strong>
            <span lang="am">መልዕክትዎ ደርሶናል።</span><br>
            Your reference number is <strong>{{ $done['reference'] }}</strong>. We will contact you soon.
            @if (config('contact.confirm_visitor'))
                A confirmation is on its way to your email.
            @endif
        </div>
    </div>
@elseif ($isThisForm && ($errors->any() || $guardErrors->any()))
    <div class="form-feedback form-feedback--error" role="alert" tabindex="-1" data-form-feedback>
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
        <div>
            <strong>Your message was not sent yet.</strong>
            <ul class="mb-0">
                @foreach ($guardErrors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@once
    @push('styles')
        <style>
            /* The redirect lands on #<form>; keep it clear of the sticky top bar. */
            #contact-form, #quote-form, #service-form, #subscribe-form, #newsletter, .form-feedback { scroll-margin-top: 110px; }
            .form-feedback { display: flex; gap: 12px; align-items: flex-start; text-align: left; border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; font-size: 0.95rem; line-height: 1.6; }
            .form-feedback > i { font-size: 1.25rem; margin-top: 2px; }
            .form-feedback ul { padding-left: 1.1em; margin-top: 4px; }
            .form-feedback--ok { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
            .form-feedback--error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
            .form-feedback:focus { outline: 3px solid #3B82F6; outline-offset: 2px; }
            .form-feedback span[lang="am"] { font-family: var(--font-reading); }
        </style>
    @endpush
    @push('scripts')
        <script>
            // Move focus to the result so screen readers announce it and the page
            // scrolls to it. The layout renders the scripts stack twice: run once.
            (function () {
                if (window.__skyFormFeedback) return;
                window.__skyFormFeedback = true;
                function go() {
                    var box = document.querySelector('[data-form-feedback]');
                    if (box) { box.scrollIntoView({ block: 'center' }); box.focus({ preventScroll: true }); }
                }
                if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', go); else go();
            })();
        </script>
    @endpush
@endonce
