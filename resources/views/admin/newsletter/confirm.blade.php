@extends('admin.layouts.master')
@section('title', 'Send Newsletter')
@section('page-title', 'Send Newsletter')
@section('content')
<div class="row">
    <div class="col-12 col-lg-8">
        <form action="{{ route('admin.newsletter.send') }}" method="POST" data-newsletter-form>
            @csrf
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Confirm sending</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        This sends the newsletter email to
                        <strong>{{ number_format($subscriberCount) }} {{ \Illuminate\Support\Str::plural('subscriber', $subscriberCount) }}</strong>
                        immediately. It cannot be undone.
                    </p>

                    @error('confirm')
                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="confirm" value="1" id="newsletterConfirm" required>
                        <label class="form-check-label" for="newsletterConfirm">
                            I understand this emails every subscriber now.
                        </label>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Limited to 3 attempts per hour.</p>
                </div>
                <div class="card-footer text-end d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-danger" @disabled($subscriberCount === 0)>
                        <i class="fas fa-paper-plane me-2"></i>Send newsletter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Prevent double submission (the layout may render this stack more than once).
    (function () {
        if (window.__skyNewsletterForm) return;
        window.__skyNewsletterForm = true;
        document.addEventListener('submit', function (e) {
            var form = e.target.closest('[data-newsletter-form]');
            if (!form) return;
            if (form.dataset.submitted === '1') { e.preventDefault(); return; }
            form.dataset.submitted = '1';
            var button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;
        });
    })();
</script>
@endpush
