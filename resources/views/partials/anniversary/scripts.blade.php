{{-- 5-year seal: pause the loop when it is off-screen or the tab is hidden. --}}
<script>
(function () {
    var seals = Array.prototype.slice.call(document.querySelectorAll('[data-anniv5-seal]'));
    if (!seals.length) return;

    var visible = new WeakMap();
    function apply(seal) { seal.classList.toggle('is-paused', document.hidden || visible.get(seal) === false); }

    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { visible.set(e.target, e.isIntersecting); apply(e.target); });
        });
        seals.forEach(function (seal) { io.observe(seal); });
    }
    document.addEventListener('visibilitychange', function () { seals.forEach(apply); });
})();
</script>
