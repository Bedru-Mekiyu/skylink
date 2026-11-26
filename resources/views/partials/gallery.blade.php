{{--
    About-page gallery (Admin > Gallery): ONE collection of photos of our work and our time with partners and clients.
    3+ photos: a slow, endless, revolving coverflow (CSS transforms + opacity, vanilla JS, no library) that pauses on
    hover / focus / off-screen / hidden tab. No visible controls: swipe, arrow keys and click-to-open lightbox only.
    1-2 photos, reduced motion, Save-Data / 2G, or no JavaScript: a plain responsive grid (still opens the lightbox).
    No visible photos: nothing is rendered at all (no gap). Thumbnails are in the page, large images load only in the lightbox.
--}}
@php($gallery = \App\Models\GalleryImage::publicList())
@if (count($gallery))
@php($n = count($gallery))
<section class="sk-gallery pt-4 pb-4" id="gallery" aria-labelledby="sk-gallery-title" data-count="{{ $n }}">
    <script>
        // Decide the mode before first paint so the section never changes size after load.
        (function (s) {
            var c = navigator.connection || {};
            var calm = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
            if ({{ $n }} >= 3 && !calm && !c.saveData && !/(^|-)2g$/.test(c.effectiveType || '')) s.classList.add('is-cinematic');
        })(document.currentScript.parentNode);
    </script>
    <div class="sk-gallery__head">
        <h2 class="display-5 fw-bold text-dark" id="sk-gallery-title">Gallery</h2>
    </div>

    <div class="sk-gallery__stage" role="region" aria-roledescription="carousel" aria-label="Photo gallery">
        <div class="sk-gallery__track">
            @foreach ($gallery as $i => $g)
                <div class="sk-gallery__slide" data-i="{{ $i }}" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $n }}">
                    <button type="button" class="sk-gallery__open" aria-haspopup="dialog" aria-label="Open photo: {{ $g['alt'] }}">
                        <img src="{{ $g['thumb'] }}" alt="{{ $g['alt'] }}" width="{{ $g['tw'] }}" height="{{ $g['th'] }}" loading="lazy" decoding="async" draggable="false">
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <p class="sk-gallery__caption" data-caption aria-live="polite"></p>

    <script type="application/json" id="sk-gallery-data">@json($gallery)</script>
</section>

<style>
    /* Plain section: same background as its neighbours (no panel, gradient or glow). */
    .sk-gallery { position: relative; overflow: hidden; isolation: isolate; }
    .sk-gallery__head { text-align: center; padding: 0 20px; margin-bottom: clamp(12px, 2vw, 24px); }
    .sk-gallery__head h2 { margin: 0; }
    .sk-gallery__track { list-style: none; margin: 0 auto; padding: 0 20px; max-width: 1200px; display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 220px), 1fr)); gap: 14px; }
    .sk-gallery[data-count="1"] .sk-gallery__track { grid-template-columns: minmax(0, 720px); justify-content: center; }
    .sk-gallery[data-count="2"] .sk-gallery__track { grid-template-columns: repeat(2, minmax(0, 520px)); justify-content: center; }
    @media (max-width: 575.98px) { .sk-gallery[data-count="2"] .sk-gallery__track { grid-template-columns: minmax(0, 1fr); } }
    .sk-gallery__slide { margin: 0; aspect-ratio: 3 / 2; border-radius: 14px; overflow: hidden; background: #e2e8f0; box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16); }
    .sk-gallery__open { display: block; width: 100%; height: 100%; padding: 0; border: 0; background: none; cursor: zoom-in; border-radius: inherit; }
    .sk-gallery__open:focus-visible { outline: 3px solid #1a6fb0; outline-offset: 3px; }
    .sk-gallery__open img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .sk-gallery__caption { display: none; }

    /* ---- cinematic: coverflow arc with depth + parallax ---- */
    .sk-gallery.is-cinematic .sk-gallery__stage { --h: clamp(230px, 31vw, 440px); --sw: min(62vw, calc(var(--h) * 1.3)); position: relative; height: var(--h); perspective: 1400px; perspective-origin: 50% 45%; contain: layout paint; touch-action: pan-y; }
    .sk-gallery.is-cinematic .sk-gallery__track { display: block; position: absolute; inset: 0; max-width: none; padding: 0; transform-style: preserve-3d; }
    .sk-gallery.is-cinematic .sk-gallery__slide { position: absolute; top: 50%; left: 50%; width: var(--sw); aspect-ratio: 3 / 2; margin: calc(var(--sw) / -3) 0 0 calc(var(--sw) / -2);
        transform: translate3d(var(--x, 0px), 0, var(--z, 0px)) rotateY(var(--ry, 0deg)); opacity: var(--o, 0); z-index: var(--zi, 0); filter: brightness(var(--b, 1));
        transition: transform 1s cubic-bezier(0.22, 0.7, 0.2, 1), opacity 1s ease, filter 1s ease; will-change: transform, opacity; backface-visibility: hidden; }
    .sk-gallery.is-cinematic .sk-gallery__slide.is-far { display: none; }
    .sk-gallery.is-cinematic .sk-gallery__slide.is-jump { transition: none; }
    .sk-gallery.is-cinematic .sk-gallery__slide.is-edge { pointer-events: none; }
    .sk-gallery.is-cinematic .sk-gallery__open img { transform: translateX(calc(var(--off, 0) * -4%)) scale(1.12); transition: transform 1s cubic-bezier(0.22, 0.7, 0.2, 1); }
    .sk-gallery.is-cinematic .sk-gallery__slide.is-jump .sk-gallery__open img { transition: none; }
    .sk-gallery.is-cinematic .sk-gallery__caption { display: block; text-align: center; min-height: 2.4em; margin: 6px auto 0; padding: 0 20px; max-width: 760px; color: #334155; }
    .sk-gallery__caption [lang="am"] { display: block; margin-top: 2px; }

    /* ---- lightbox ---- */
    .sk-lb { position: fixed; inset: 0; z-index: 2000; display: grid; grid-template-rows: 1fr auto; background: rgba(3, 9, 22, 0.94); color: #fff; touch-action: pan-y; }
    .sk-lb[hidden] { display: none; }
    .sk-lb__fig { margin: 0; display: grid; place-items: center; min-height: 0; padding: 56px 64px 8px; }
    .sk-lb__fig img { user-select: none; -webkit-user-drag: none; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 6px; }
    .sk-lb__cap { text-align: center; padding: 8px 64px 22px; min-height: 4.2em; }
    .sk-lb__cap [lang="am"] { display: block; }
    .sk-lb__n { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    .sk-lb__count { display: block; font-size: 0.875rem; opacity: 0.75; margin-top: 4px; }
    .sk-lb__btn { position: absolute; width: 48px; height: 48px; border-radius: 50%; border: 1px solid rgba(255, 255, 255, 0.4); background: rgba(255, 255, 255, 0.1); color: #fff; font-size: 1.25rem; display: grid; place-items: center; cursor: pointer; }
    .sk-lb__btn:hover { background: rgba(255, 255, 255, 0.25); }
    .sk-lb__btn:focus-visible { outline: 3px solid #7ccdee; outline-offset: 3px; }
    .sk-lb__close { top: 12px; right: 12px; } .sk-lb__prev { left: 10px; top: 50%; transform: translateY(-50%); } .sk-lb__next { right: 10px; top: 50%; transform: translateY(-50%); }
    @media (max-width: 575.98px) { .sk-lb__fig { padding: 56px 8px 8px; } .sk-lb__cap { padding: 8px 12px 18px; } .sk-lb__prev, .sk-lb__next { top: auto; bottom: 8px; transform: none; } .sk-lb__prev { left: 12px; } .sk-lb__next { right: 12px; } }
    @media (prefers-reduced-motion: reduce) { .sk-gallery__slide, .sk-gallery__open img { transition: none !important; } }
</style>

<script>
(function () {
    var root = document.getElementById('gallery'); if (!root) return;
    var data = JSON.parse(document.getElementById('sk-gallery-data').textContent), n = data.length;
    var slides = [].slice.call(root.querySelectorAll('.sk-gallery__slide')), stage = root.querySelector('.sk-gallery__stage');
    var capEl = root.querySelector('[data-caption]');
    var cinematic = root.classList.contains('is-cinematic'), cur = 0, timer = null;
    var flags = { hover: false, focus: false, lb: false, hidden: false, off: false };

    function caption(g, el) {
        el.textContent = '';
        if (g.en) { var a = document.createElement('span'); a.textContent = g.en; el.appendChild(a); }
        if (g.am) { var b = document.createElement('span'); b.lang = 'am'; b.textContent = g.am; el.appendChild(b); }
        if (g.date) { var d = document.createElement('span'); d.className = 'sk-lb__count'; d.textContent = g.date; if (el.classList.contains('sk-lb__cap')) el.appendChild(d); }
    }

    // ---------------- cinematic coverflow ----------------
    var prevOff = [];
    function render(first) {
        var sw = slides[0].offsetWidth, half = Math.floor(n / 2), X = Math.max(90, sw * 0.6);
        slides.forEach(function (s, i) {
            var off = ((i - cur + n + half) % n) - half, abs = Math.abs(off);
            var jump = !first && prevOff[i] !== undefined && Math.abs(off - prevOff[i]) > 1;
            s.classList.toggle('is-jump', jump);
            s.classList.toggle('is-far', abs > 3);
            s.classList.toggle('is-edge', abs >= 3);
            var o = abs === 0 ? 1 : abs === 1 ? 0.92 : abs === 2 ? 0.6 : 0;
            s.style.setProperty('--x', (off * X) + 'px');
            s.style.setProperty('--z', (-abs * 170) + 'px');
            s.style.setProperty('--ry', (-Math.sign(off) * Math.min(abs, 3) * 24) + 'deg');
            s.style.setProperty('--o', o);
            s.style.setProperty('--b', 1 - Math.min(abs, 3) * 0.06); // light page: only a gentle dim
            s.style.setProperty('--zi', 10 - abs);
            s.style.setProperty('--off', off);
            var btn = s.firstElementChild; btn.tabIndex = off === 0 ? 0 : -1;
            s.setAttribute('aria-hidden', off === 0 ? 'false' : 'true');
            prevOff[i] = off;
        });
        caption(data[cur], capEl);
        if (jumpFix) { void stage.offsetWidth; slides.forEach(function (s) { s.classList.remove('is-jump'); }); }
    }
    var jumpFix = true;
    function go(to) { cur = (to + n) % n; render(false); }
    function running() { return cinematic && !flags.hover && !flags.focus && !flags.lb && !flags.hidden && !flags.off; }
    function schedule() { clearTimeout(timer); if (running()) timer = setTimeout(function () { go(cur + 1); schedule(); }, 4800); }

    if (cinematic) {
        render(true);
        stage.addEventListener('mouseenter', function () { flags.hover = true; schedule(); });
        stage.addEventListener('mouseleave', function () { flags.hover = false; schedule(); });
        root.addEventListener('focusin', function () { flags.focus = true; schedule(); });
        root.addEventListener('focusout', function (e) { if (!root.contains(e.relatedTarget)) { flags.focus = false; schedule(); } });
        document.addEventListener('visibilitychange', function () { flags.hidden = document.hidden; schedule(); });
        if ('IntersectionObserver' in window) new IntersectionObserver(function (en) { flags.off = !en[0].isIntersecting; schedule(); }, { threshold: 0.15 }).observe(root);
        window.addEventListener('resize', function () { render(true); });
        stage.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') { go(cur - 1); schedule(); } else if (e.key === 'ArrowRight') { go(cur + 1); schedule(); } });
        // swipe / drag
        var sx = null, moved = false;
        stage.addEventListener('pointerdown', function (e) { sx = e.clientX; moved = false; });
        stage.addEventListener('pointermove', function (e) { if (sx !== null && Math.abs(e.clientX - sx) > 12) moved = true; });
        stage.addEventListener('pointerup', function (e) { if (sx === null) return; var dx = e.clientX - sx; sx = null; if (Math.abs(dx) > 45) { go(cur + (dx < 0 ? 1 : -1)); schedule(); } });
        stage.addEventListener('click', function (e) { if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; } }, true);
        schedule();
    }

    // ---------------- lightbox ----------------
    var lb = null, lbImg, lbCap, lbIdx = 0, opener = null;
    function build() {
        lb = document.createElement('div'); lb.className = 'sk-lb'; lb.hidden = true; lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true'); lb.setAttribute('aria-label', 'Photo viewer');
        lb.innerHTML = '<button type="button" class="sk-lb__btn sk-lb__close" aria-label="Close">&times;</button>' +
            '<button type="button" class="sk-lb__btn sk-lb__prev" aria-label="Previous photo"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>' +
            '<button type="button" class="sk-lb__btn sk-lb__next" aria-label="Next photo"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>' +
            '<figure class="sk-lb__fig"><img alt="" draggable="false"></figure><div class="sk-lb__cap" aria-live="polite"></div>';
        document.body.appendChild(lb);
        lbImg = lb.querySelector('img'); lbCap = lb.querySelector('.sk-lb__cap');
        lb.addEventListener('click', function (e) {
            if (e.target.closest('.sk-lb__close') || e.target === lb || e.target.classList.contains('sk-lb__fig') || e.target.classList.contains('sk-lb__cap')) close();
            else if (e.target.closest('.sk-lb__prev')) show(lbIdx - 1);
            else if (e.target.closest('.sk-lb__next')) show(lbIdx + 1);
        });
        lb.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { e.preventDefault(); close(); }
            else if (e.key === 'ArrowLeft') show(lbIdx - 1);
            else if (e.key === 'ArrowRight') show(lbIdx + 1);
            else if (e.key === 'Tab') { // focus trap
                var f = [].slice.call(lb.querySelectorAll('button')), first = f[0], last = f[f.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
        var sx = null;
        lb.addEventListener('pointerdown', function (e) { sx = e.clientX; });
        lb.addEventListener('pointerup', function (e) { if (sx === null) return; var dx = e.clientX - sx; sx = null; if (Math.abs(dx) > 50) show(lbIdx + (dx < 0 ? 1 : -1)); });
    }
    function show(i) {
        lbIdx = (i + n) % n; var g = data[lbIdx];
        lbImg.src = g.full; lbImg.alt = g.alt; lbImg.width = g.fw; lbImg.height = g.fh;
        caption(g, lbCap);
        var c = document.createElement('span'); c.className = 'sk-lb__n'; c.textContent = 'Photo ' + (lbIdx + 1) + ' of ' + n; lbCap.appendChild(c); // screen readers only
        [lbIdx + 1, lbIdx - 1].forEach(function (k) { new Image().src = data[(k + n) % n].full; }); // neighbours ready
    }
    function open(i, from) {
        if (!lb) build();
        opener = from; flags.lb = true; schedule();
        lb.hidden = false; document.body.style.overflow = 'hidden';
        show(i); lb.querySelector('.sk-lb__close').focus();
    }
    function close() {
        lb.hidden = true; document.body.style.overflow = ''; flags.lb = false;
        if (cinematic && lbIdx !== cur) go(lbIdx);
        schedule();
        var back = opener && document.contains(opener) ? opener : slides[cinematic ? cur : lbIdx].firstElementChild;
        if (back) back.focus();
    }
    root.addEventListener('click', function (e) {
        var b = e.target.closest('.sk-gallery__open'); if (!b) return;
        open(parseInt(b.parentNode.dataset.i, 10), b);
    });
})();
</script>
@endif
