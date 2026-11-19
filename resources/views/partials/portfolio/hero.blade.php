{{--
    /portfolio banner: the featured project (else the newest project with a
    video, else the newest project). Expects $heroProject (may be null).

    Video: silent, looping, no controls. Nothing is downloaded on page load
    (preload="none", sources added by the script). The poster stays for
    reduced motion, Save-Data and slow-2g/2g connections, like the homepage.
--}}
@php
    $p = $heroProject;
    $poster = $p?->poster_url;
    $hasVideo = $p && $p->has_video;
    $description = $p ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $p->description)), 170) : null;
@endphp

<section class="pf-hero {{ $poster ? '' : 'pf-hero--poster' }}" aria-labelledby="pf-hero-heading"
         @if ($p) data-pf-hero-title="{{ \Illuminate\Support\Str::lower($p->title.' '.$p->description) }}" @endif>
    <div class="pf-hero__media" aria-hidden="true">
        <div class="pf-hero__zoom">
            @if ($poster)
                <img class="pf-hero__poster" src="{{ $poster }}" alt="" width="1920" height="1080" fetchpriority="high" decoding="async">
            @endif
            @if ($hasVideo)
                <video class="pf-hero__video" muted playsinline loop preload="none" tabindex="-1"
                       disablepictureinpicture disableremoteplayback
                       @if ($p->video_webm_url) data-webm="{{ $p->video_webm_url }}" @endif
                       @if ($p->video_mp4_url) data-mp4="{{ $p->video_mp4_url }}" @endif></video>
            @endif
        </div>
        <div class="pf-hero__tint"></div>
        <div class="pf-hero__vignette"></div>
        <div class="pf-hero__grain"></div>
    </div>
    <svg class="pf-hero__curve" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0,56 L0,24 C240,52 480,60 720,44 C960,28 1200,8 1440,22 L1440,56 Z"></path>
    </svg>

    <div class="container pf-hero__content">
        @if ($p)
            <h1 class="pf-hero__title" id="pf-hero-heading">{{ $p->title }}</h1>
            @if ($description)
                <p class="pf-hero__text">{{ $description }}</p>
            @endif
            @if ($p->safe_url)
                <a class="pf-hero__cta" href="{{ $p->safe_url }}" target="_blank" rel="noopener noreferrer">
                    Visit project <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    <span class="visually-hidden">(opens {{ $p->title }} in a new tab)</span>
                </a>
            @endif
        @else
            <h1 class="visually-hidden" id="pf-hero-heading">Portfolio</h1>
            <p class="pf-hero__text">Explore our portfolio of successful IT projects and digital solutions delivered to clients across Ethiopia.</p>
        @endif
    </div>
</section>

@once
@push('styles')
<style>
    /* ===== /portfolio banner (same treatment as the /news and About banners) ===== */
    .pf-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        min-height: 480px;
        padding: 96px 0 104px;
        color: #fff;
        background: #06122b;
    }

    .pf-hero__media { position: absolute; inset: 0; z-index: 0; pointer-events: none; }

    .pf-hero__zoom {
        position: absolute;
        inset: 0;
        transform-origin: 50% 40%;
        animation: pfHeroZoom 26s ease-in-out infinite alternate;
    }

    @keyframes pfHeroZoom {
        from { transform: scale(1); }
        to { transform: scale(1.06); }
    }

    .pf-hero__poster,
    .pf-hero__video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        max-width: none;
        object-fit: cover;
        object-position: 50% 0;
    }

    /* The clip fades in over the poster once it is really playing. */
    /* A recorded website is full of small text; a slight blur keeps it as
       background motion so it never competes with the banner's own text. */
    .pf-hero__video { opacity: 0; transition: opacity 0.8s ease; filter: blur(1.5px) saturate(0.95); }
    .pf-hero__video.is-playing { opacity: 1; }

    .pf-hero__tint {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(90deg, rgba(4, 12, 34, 0.78) 0%, rgba(4, 12, 34, 0.55) 40%, rgba(4, 12, 34, 0.15) 75%, rgba(4, 12, 34, 0) 100%),
            linear-gradient(115deg, rgba(59, 130, 246, 0.38) 0%, rgba(59, 130, 246, 0.1) 45%, rgba(59, 130, 246, 0) 70%),
            linear-gradient(180deg, rgba(4, 12, 34, 0.45) 0%, rgba(4, 12, 34, 0.6) 45%, rgba(4, 12, 34, 0.9) 100%);
    }

    @media (max-width: 767.98px) {
        /* Text spans the full width on phones: darken evenly. */
        .pf-hero__tint { background: linear-gradient(180deg, rgba(4, 12, 34, 0.62) 0%, rgba(4, 12, 34, 0.72) 50%, rgba(4, 12, 34, 0.92) 100%); }
    }

    .pf-hero__vignette {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 50% 40%, rgba(4, 12, 34, 0) 50%, rgba(4, 12, 34, 0.55) 100%);
    }

    .pf-hero__grain {
        position: absolute;
        inset: -50%;
        opacity: 0.06;
        mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    /* No picture at all: navy gradient with a light dot grid (as on /news). */
    .pf-hero--poster .pf-hero__media {
        background:
            radial-gradient(circle at 1px 1px, rgba(147, 197, 253, 0.22) 1px, transparent 0) 0 0 / 22px 22px,
            radial-gradient(ellipse at 20% 10%, rgba(59, 130, 246, 0.55), transparent 55%),
            radial-gradient(ellipse at 90% 90%, rgba(30, 64, 175, 0.6), transparent 60%),
            linear-gradient(160deg, #0b1f4d 0%, #06122b 70%);
    }

    .pf-hero__curve {
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        z-index: 1;
        width: 100%;
        height: 56px;
        display: block;
        fill: #f8f9fa; /* .news-page background */
    }

    .pf-hero__content { position: relative; z-index: 2; }

    .pf-hero__title {
        max-width: 860px;
        margin: 0 0 12px;
        color: #fff;
        font-weight: 700;
        font-size: clamp(1.9rem, 4.4vw, 3.3rem);
        line-height: 1.15;
        text-shadow: 0 2px 18px rgba(4, 12, 34, 0.55);
        overflow-wrap: anywhere;
    }

    .pf-hero__text {
        max-width: 700px;
        margin: 0 0 24px;
        color: rgba(255, 255, 255, 0.88);
        font-size: 1.02rem;
        line-height: 1.7;
    }

    .pf-hero__cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 10px 26px;
        border-radius: 50px;
        background: #fff;
        color: #1e40af;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 10px 30px rgba(4, 12, 34, 0.35);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .pf-hero__cta:hover { color: #1e40af; transform: translateY(-2px); box-shadow: 0 14px 34px rgba(4, 12, 34, 0.45); }
    .pf-hero__cta:focus-visible { outline: 3px solid #93c5fd; outline-offset: 4px; }

    @media (max-width: 575.98px) {
        .pf-hero { min-height: 420px; padding: 72px 0 84px; }
        .pf-hero__curve { height: 40px; }
        .pf-hero__text { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
    }

    @media (prefers-reduced-motion: reduce) {
        .pf-hero__zoom { animation: none; }
        .pf-hero__video { display: none; }
        .pf-hero__cta { transition: none; }
    }
</style>
@endpush

@push('scripts')
<script>
    // /portfolio banner video. Same rules as the homepage hero: no download on
    // page load; poster only for reduced motion, Save-Data and slow-2g/2g; paused
    // while off-screen or in a background tab. Rendered twice by the layout: run once.
    (function () {
        if (window.__skyPortfolioHero) return;
        window.__skyPortfolioHero = true;
        var SLOW_CONNECTIONS = ['slow-2g', '2g'];

        function posterOnly() {
            var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
            return reduce || !!(conn && conn.saveData) || !!(conn && SLOW_CONNECTIONS.indexOf(conn.effectiveType) !== -1);
        }

        function init() {
            var video = document.querySelector('.pf-hero__video');
            if (!video || posterOnly()) return;

            video.muted = true; // required for autoplay
            [['data-webm', 'video/webm'], ['data-mp4', 'video/mp4']].forEach(function (pair) {
                var src = video.getAttribute(pair[0]);
                if (src && video.canPlayType(pair[1])) {
                    var s = document.createElement('source');
                    s.src = src;
                    s.type = pair[1];
                    video.appendChild(s);
                }
            });
            if (!video.querySelector('source')) return;

            var inView = true;
            function sync() {
                if (inView && !document.hidden) {
                    var p = video.play();
                    if (p && p.catch) p.catch(function () { /* autoplay refused: the poster stays */ });
                } else {
                    video.pause();
                }
            }
            video.addEventListener('playing', function () { video.classList.add('is-playing'); });
            video.preload = 'auto';
            video.load();
            sync();

            document.addEventListener('visibilitychange', sync);
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(function (entries) {
                    inView = entries[0].isIntersecting;
                    sync();
                }, { threshold: 0.05 }).observe(video);
            }
            if (window.matchMedia) {
                var motion = matchMedia('(prefers-reduced-motion: reduce)');
                var onMotion = function () { if (motion.matches) { inView = false; sync(); video.classList.remove('is-playing'); } };
                if (motion.addEventListener) motion.addEventListener('change', onMotion); else if (motion.addListener) motion.addListener(onMotion);
            }
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
</script>
@endpush
@endonce
