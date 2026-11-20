/* Image loading helper (loaded on every public page).
   - fades lazy images in once they have loaded (the CSS keeps them hidden until the "is-loaded" class)
   - a broken image (missing file, dead link) is replaced by the site placeholder, never a broken-image icon */
(function () {
    'use strict';
    var FALLBACK = (document.documentElement.getAttribute('data-img-fallback') || '/assets/images/placeholder.png');
    function ready(img) { img.classList.add('is-loaded'); }
    function broken(img) {
        if (img.dataset.skyFallback) { ready(img); return; }          // the placeholder itself failed: just show the box
        img.dataset.skyFallback = '1';
        var pic = img.parentNode;
        if (pic && pic.tagName === 'PICTURE') pic.querySelectorAll('source').forEach(function (s) { s.remove(); });
        img.removeAttribute('srcset'); img.removeAttribute('sizes');
        img.classList.add('img-fallback', 'is-loaded');
        img.src = FALLBACK;
    }
    document.addEventListener('load', function (e) { if (e.target && e.target.tagName === 'IMG') ready(e.target); }, true);
    document.addEventListener('error', function (e) { if (e.target && e.target.tagName === 'IMG' && !e.target.hasAttribute('data-no-fallback')) broken(e.target); }, true);
    function sweep() {
        document.querySelectorAll('img').forEach(function (img) {
            if (!img.complete) return;                                  // still loading: the listeners above will handle it
            if (img.naturalWidth > 0) ready(img); else if (img.currentSrc || img.getAttribute('src')) broken(img);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', sweep); else sweep();
    window.addEventListener('load', sweep);
})();
