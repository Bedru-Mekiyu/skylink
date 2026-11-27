{{-- One small way back to the page the customer was looking at (cart page). --}}
@php [$returnName, $returnUrl] = \App\Http\Middleware\RememberBrowsedPage::target(); @endphp
<a href="{{ $returnUrl }}" class="sky-return" data-return-link>
    <i class="fas fa-arrow-left" aria-hidden="true"></i>
    <span>{{ __('Back to') }} {{ $returnName }}</span>
</a>
<script>
    // If that page is the one just left, go back through history: the scroll position and chosen category are kept.
    (function () {
        var link = document.querySelector('a[data-return-link]');
        var from = null;
        try { from = document.referrer ? new URL(document.referrer) : null; } catch (e) { from = null; }
        if (!link || !from || history.length < 2 || from.href.split('#')[0] !== link.href) return;
        link.addEventListener('click', function (event) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;
            event.preventDefault();
            history.back();
        });
    })();
</script>
