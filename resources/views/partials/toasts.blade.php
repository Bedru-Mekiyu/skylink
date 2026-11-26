{{--
    The only place a one-off message is shown, on the public site and in the admin: do not print session messages in a view.
    Confirmations leave by themselves; an error stays until it is closed. (Field errors stay beside their form.)
--}}
@php
    $toasts = [];
    foreach (['error' => 'error', 'success' => 'success', 'info' => 'info', 'warning' => 'warning', 'newsletter_status' => 'success'] as $key => $kind) {
        if (is_string($message = session($key)) && $message !== '') {
            $toasts[] = ['kind' => $kind, 'text' => $message, 'reference' => null];
        }
    }
    if ($sent = session('inquiry_success')) {
        $toasts[] = ['kind' => 'success', 'text' => 'Thank you! Your message has been received. መልዕክትዎ ደርሶናል።', 'reference' => $sent['reference'] ?? null];
    }
    $toasts = collect($toasts)->unique('text')->all();
@endphp
<div class="sky-toasts" id="skyToasts" aria-live="polite">
    @foreach($toasts as $toast)
        <div class="sky-toast sky-toast--{{ $toast['kind'] }}" role="{{ $toast['kind'] === 'error' ? 'alert' : 'status' }}" data-toast="{{ $toast['kind'] }}"
             data-timeout="{{ $toast['kind'] === 'error' ? 0 : ($toast['reference'] ? 15000 : ($toast['kind'] === 'warning' ? 9000 : 5000)) }}">
            <i class="fas {{ ['error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle', 'info' => 'fa-info-circle'][$toast['kind']] ?? 'fa-check-circle' }}" aria-hidden="true"></i>
            <div class="sky-toast__text">{{ $toast['text'] }}
                @if($toast['reference'])<br>Your reference number is <strong>{{ $toast['reference'] }}</strong>.@endif
            </div>
            <button type="button" class="sky-toast__close" aria-label="{{ __('Close') }}">&times;</button>
        </div>
    @endforeach
</div>
<link rel="stylesheet" href="{{ asset('assets/css/sky-toast.css') }}?v={{ @filemtime(public_path('assets/css/sky-toast.css')) }}">
<script src="{{ asset('assets/js/sky-toast.js') }}?v={{ @filemtime(public_path('assets/js/sky-toast.js')) }}"></script>
