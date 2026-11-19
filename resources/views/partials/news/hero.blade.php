{{--
    /news top banner: always the newest video (App\Models\Video::newestFirst),
    so a video added in the admin appears here by itself. Falls back to the
    newest article when there are no videos. Expects $heroVideo, $heroArticle.

    No third-party request on page load: the YouTube/TikTok player is created
    only when the visitor clicks "Watch video", inside a <dialog>. Without
    JavaScript (or <dialog> support) the button is a normal link to the video.
--}}
@php
    $tz = config('news.display_timezone', 'Africa/Addis_Ababa');
    if ($heroVideo) {
        $kind = 'video';
        $title = $heroVideo->title;
        $image = $heroVideo->thumbnail_url;
        $date = $heroVideo->display_date;
        $category = $heroVideo->category?->label;
        $platform = $heroVideo->platform; // youtube | tiktok | null
        $link = preg_match('~^https?://~i', (string) $heroVideo->video_url) ? $heroVideo->video_url : null;
        $embed = $heroVideo->embed_url;
    } elseif ($heroArticle) {
        $kind = 'article';
        $title = $heroArticle->title;
        $image = $heroArticle->image_url;
        $date = $heroArticle->display_date;
        $category = $heroArticle->category?->label;
        $platform = null;
        $link = route('news.show', $heroArticle->slug);
        $embed = null;
    } else {
        $kind = 'none';
        $title = null;
        $image = null;
        $date = null;
        $category = null;
        $platform = null;
        $link = null;
        $embed = null;
    }
    $titleLang = $title && preg_match('/\p{Ethiopic}/u', $title) ? 'am' : 'en';
    $platformLabel = ['youtube' => 'YouTube', 'tiktok' => 'TikTok'][$platform] ?? null;
@endphp

<section class="news-hero {{ $image ? '' : 'news-hero--poster' }}" aria-labelledby="news-hero-heading">
    <div class="news-hero__media" aria-hidden="true">
        @if ($image)
            <div class="news-hero__zoom">
                <img class="news-hero__img" src="{{ $image }}" alt="" width="1600" height="900" fetchpriority="high" decoding="async">
            </div>
        @endif
        <div class="news-hero__tint"></div>
        <div class="news-hero__vignette"></div>
        <div class="news-hero__grain"></div>
    </div>
    <svg class="news-hero__curve" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0,56 L0,24 C240,52 480,60 720,44 C960,28 1200,8 1440,22 L1440,56 Z"></path>
    </svg>

    <div class="container news-hero__content">
        <h1 class="news-hero__page" id="news-hero-heading">News, Innovations &amp; Events</h1>

        @if ($kind === 'none')
            <p class="news-hero__intro">Discover the latest developments, updates, and event highlights from our team.</p>
        @else
            <p class="news-hero__title" lang="{{ $titleLang }}">{{ $title }}</p>
            <div class="news-hero__row">
                <p class="news-hero__meta">
                    @if ($date)
                        <span><i class="far fa-calendar me-1" aria-hidden="true"></i><time datetime="{{ $date->toIso8601String() }}">{{ $date->format('F j, Y') }}</time></span>
                    @endif
                    @if ($platformLabel)
                        <span><i class="fab {{ $platform === 'youtube' ? 'fa-youtube' : 'fa-tiktok' }} me-1" aria-hidden="true"></i>{{ $platformLabel }}</span>
                    @endif
                </p>
                @if ($kind === 'video' && $link)
                    <a class="news-hero__watch" href="{{ $link }}" target="_blank" rel="noopener noreferrer"
                       @if ($embed)
                           data-news-player-open
                           data-embed="{{ $embed }}"
                           data-platform="{{ $platform }}"
                           data-title="{{ $title }}"
                           aria-haspopup="dialog"
                       @endif>
                        <span class="news-hero__watch-icon" aria-hidden="true"><i class="fas fa-play"></i></span>
                        Watch video
                        <span class="visually-hidden">: {{ $title }}{{ $embed ? '' : ' (opens in a new tab)' }}</span>
                    </a>
                @elseif ($kind === 'article')
                    <a class="news-hero__watch" href="{{ $link }}">
                        <span class="news-hero__watch-icon" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                        Read article
                    </a>
            </div>
            @endif
        @endif
    </div>
</section>

@if ($kind === 'video' && $embed)
    <dialog class="news-player" id="newsPlayer" aria-labelledby="newsPlayerTitle">
        <div class="news-player__box" data-player-box>
            <div class="news-player__bar">
                <h2 class="news-player__title" id="newsPlayerTitle" lang="{{ $titleLang }}">{{ $title }}</h2>
                <button type="button" class="news-player__close" data-player-close aria-label="Close video">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="news-player__frame" data-player-frame></div>
        </div>
    </dialog>
@endif

@once
@push('styles')
<style>
    /* ===== /news top banner (same treatment as the About banner) ===== */
    .news-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        min-height: 460px;
        padding: 96px 0 104px;
        color: #fff;
        background: #06122b;
    }

    .news-hero__media { position: absolute; inset: 0; z-index: 0; pointer-events: none; }

    /* Very slow, gentle, alternating zoom (no jump at either end). */
    .news-hero__zoom {
        position: absolute;
        inset: 0;
        transform-origin: 50% 40%;
        animation: newsHeroZoom 26s ease-in-out infinite alternate;
    }

    @keyframes newsHeroZoom {
        from { transform: scale(1); }
        to { transform: scale(1.06); }
    }

    .news-hero__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        max-width: none;
        object-fit: cover;
        object-position: 50% 30%;
    }

    /* Navy-to-brand-blue grade, darker at the bottom where the text sits. */
    .news-hero__tint {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(115deg, rgba(59, 130, 246, 0.42) 0%, rgba(59, 130, 246, 0.12) 45%, rgba(59, 130, 246, 0) 70%),
            linear-gradient(180deg, rgba(4, 12, 34, 0.40) 0%, rgba(4, 12, 34, 0.55) 45%, rgba(4, 12, 34, 0.88) 100%);
    }

    .news-hero__vignette {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 50% 40%, rgba(4, 12, 34, 0) 50%, rgba(4, 12, 34, 0.55) 100%);
    }

    /* Same faint static grain as the homepage and About banners. */
    .news-hero__grain {
        position: absolute;
        inset: -50%;
        opacity: 0.06;
        mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    /* Poster style (no picture): navy gradient with a light dot grid. */
    .news-hero--poster .news-hero__media {
        background:
            radial-gradient(circle at 1px 1px, rgba(147, 197, 253, 0.22) 1px, transparent 0) 0 0 / 22px 22px,
            radial-gradient(ellipse at 20% 10%, rgba(59, 130, 246, 0.55), transparent 55%),
            radial-gradient(ellipse at 90% 90%, rgba(30, 64, 175, 0.6), transparent 60%),
            linear-gradient(160deg, #0b1f4d 0%, #06122b 70%);
    }

    .news-hero__curve {
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

    .news-hero__content { position: relative; z-index: 2; }

    .news-hero__page {
        margin: 0 0 14px;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(219, 234, 254, 0.9);
    }

    .news-hero__intro { max-width: 720px; margin: 0; color: rgba(255, 255, 255, 0.88); line-height: 1.7; }

    .news-hero__title {
        max-width: 860px;
        margin: 0 0 12px;
        color: #fff;
        font-weight: 700;
        font-size: clamp(1.7rem, 4vw, 3rem);
        line-height: 1.2;
        text-shadow: 0 2px 18px rgba(4, 12, 34, 0.55);
        overflow-wrap: anywhere;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-hero__title[lang="am"] { font-family: var(--font-heading); line-height: 1.35; }

    /* Date, platform and the Watch/Read button sit side by side; they wrap on narrow screens. */
    .news-hero__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px 28px;
    }

    .news-hero__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 18px;
        margin: 0;
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.92rem;
    }

    .news-hero__watch {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        min-height: 48px;
        padding: 8px 24px 8px 8px;
        border-radius: 50px;
        background: #fff;
        color: #1e40af;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 10px 30px rgba(4, 12, 34, 0.35);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .news-hero__watch:hover { color: #1e40af; transform: translateY(-2px); box-shadow: 0 14px 34px rgba(4, 12, 34, 0.45); }

    .news-hero__watch:focus-visible { outline: 3px solid #93c5fd; outline-offset: 4px; }

    .news-hero__watch-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #3B82F6;
        color: #fff;
        font-size: 0.85rem;
    }

    .news-hero__watch-icon .fa-play { margin-left: 2px; }

    /* ----- pop-up player ----- */
    .news-player {
        /* The site resets margin on every element; a modal <dialog> is
           centred by margin:auto, so restore it explicitly. */
        position: fixed;
        inset: 0;
        margin: auto;
        width: fit-content;
        height: fit-content;
        padding: 0;
        border: 0;
        background: transparent;
        max-width: 96vw;
        max-height: 96vh;
        overflow: visible;
    }

    .news-player::backdrop { background: rgba(4, 12, 34, 0.86); }

    .news-player__box { width: min(92vw, 960px); color: #fff; }

    .news-player__box--tiktok { width: min(92vw, 400px, calc((86vh - 52px) * 9 / 16)); }

    .news-player__bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }

    .news-player__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .news-player__title[lang="am"] { font-family: var(--font-heading); }

    .news-player__close {
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        font-size: 1.2rem;
        cursor: pointer;
    }

    .news-player__close:hover { background: rgba(255, 255, 255, 0.26); }

    .news-player__close:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }

    .news-player__frame {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
    }

    .news-player__box--tiktok .news-player__frame { aspect-ratio: 9 / 16; }

    .news-player__frame iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; opacity: 0; transition: opacity 0.3s ease; }

    .news-player__frame iframe.is-loaded { opacity: 1; }

    /* Shown until the player has loaded (TikTok can take a few seconds). */
    .news-player__loading {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
    }

    .news-player__spinner {
        width: 36px;
        height: 36px;
        border: 3px solid rgba(255, 255, 255, 0.25);
        border-top-color: #93c5fd;
        border-radius: 50%;
        animation: newsPlayerSpin 0.9s linear infinite;
    }

    @keyframes newsPlayerSpin { to { transform: rotate(360deg); } }

    html.news-player-open { overflow: hidden; }

    @media (max-width: 575.98px) {
        .news-hero { min-height: 400px; padding: 72px 0 84px; }
        .news-hero__curve { height: 40px; }
        .news-hero__title { -webkit-line-clamp: 4; }
    }

    @media (prefers-reduced-motion: reduce) {
        .news-hero__zoom { animation: none; }
        .news-hero__watch, .news-player__frame iframe { transition: none; }
        .news-player__spinner { animation-duration: 2.4s; }
    }
</style>
@endpush

@push('scripts')
<script>
    // /news "Watch video" pop-up. The layout renders the scripts stack twice: run once.
    (function () {
        if (window.__skyNewsPlayer) return;
        window.__skyNewsPlayer = true;

        // Only these players may be loaded (built server-side from the video id).
        var ALLOWED = /^https:\/\/(www\.youtube-nocookie\.com\/embed\/|www\.tiktok\.com\/player\/v1\/)/;

        function init() {
            var dialog = document.getElementById('newsPlayer');
            var opener = document.querySelector('[data-news-player-open]');
            if (!dialog || !opener || typeof dialog.showModal !== 'function') return; // plain link fallback

            var box = dialog.querySelector('[data-player-box]');
            var frame = dialog.querySelector('[data-player-frame]');
            var closeBtn = dialog.querySelector('[data-player-close]');

            opener.addEventListener('click', function (e) {
                var src = opener.getAttribute('data-embed') || '';
                if (!ALLOWED.test(src)) return; // unknown player: follow the normal link
                e.preventDefault();

                box.classList.toggle('news-player__box--tiktok', opener.getAttribute('data-platform') === 'tiktok');
                var iframe = document.createElement('iframe');
                iframe.src = src; // loaded only now, after the click
                iframe.title = opener.getAttribute('data-title') || 'Video';
                iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
                iframe.allowFullscreen = true;
                iframe.referrerPolicy = 'strict-origin-when-cross-origin';
                iframe.addEventListener('load', function () { iframe.classList.add('is-loaded'); });
                frame.innerHTML = '<div class="news-player__loading" role="status"><span class="news-player__spinner" aria-hidden="true"></span>Loading video…</div>';
                frame.appendChild(iframe);

                document.documentElement.classList.add('news-player-open');
                dialog.showModal(); // modal: the rest of the page is inert, Esc closes
                closeBtn.focus();
            });

            closeBtn.addEventListener('click', function () { dialog.close(); });

            // A click on the dark backdrop (outside the box) closes.
            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) dialog.close();
            });

            // On any close (X, Esc, backdrop): remove the player so the video stops.
            dialog.addEventListener('close', function () {
                frame.innerHTML = '';
                document.documentElement.classList.remove('news-player-open');
                opener.focus();
            });
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
</script>
@endpush
@endonce
