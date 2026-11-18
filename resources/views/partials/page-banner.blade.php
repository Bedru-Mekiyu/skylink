{{--
    Shared page banner, with the same treatment as the About, /news and /portfolio
    banners: photo, navy-to-brand-blue grade, vignette, faint static grain and
    a curved bottom edge. It is used by /contact; the older banners keep their own copies.

    Parameters:
      $bannerTitle   h1 text
      $bannerText    optional paragraph
      $bannerImage   ['src' => url, 'srcset' => '... 960w, ... 1920w', 'width' => int, 'height' => int]
                     optional; without it the navy gradient with the dot grid is used (as on /news)
      $bannerTopCut  share of the photo hidden at the top (default 0.19, as on About:
                     it hides the room-number signs above the heads)
      $bannerCurve   colour of the curved edge = background of the next section
      $bannerFit     'whole' = show the entire photo in its own shape and natural colours
                     (no crop, zoom or blue grade); only the area behind the text is darkened.
                     Phones: the photo sits on top and the text below it on navy.

    Motion: a very slow zoom (CSS only). Static for prefers-reduced-motion
    and for Save-Data (a tiny inline check adds .page-banner--static).
--}}
@php
    $bannerImage = $bannerImage ?? null;
    $bannerTopCut = $bannerTopCut ?? 0.19;
    $bannerCurve = $bannerCurve ?? '#ffffff';
    $bannerWhole = $bannerImage && ($bannerFit ?? null) === 'whole';
    $bannerRatio = $bannerImage ? $bannerImage['height'] / $bannerImage['width'] : 0;
@endphp

<section class="page-banner {{ $bannerImage ? '' : 'page-banner--plain' }} {{ $bannerWhole ? 'page-banner--whole' : '' }}"
         style="@if ($bannerWhole)--pb-ratio: {{ $bannerImage['width'] }} / {{ $bannerImage['height'] }}; @endif--pb-cut-w: {{ round($bannerTopCut * $bannerRatio * 100, 2) }}vw; --pb-cut-k: {{ round($bannerTopCut / (1 - $bannerTopCut), 4) }}; --pb-curve: {{ $bannerCurve }};">
    <div class="page-banner__media" aria-hidden="true">
        @if ($bannerImage)
            <div class="page-banner__zoom">
                <img class="page-banner__img" src="{{ $bannerImage['src'] }}"
                     @isset($bannerImage['srcset']) srcset="{{ $bannerImage['srcset'] }}" sizes="100vw" @endisset
                     width="{{ $bannerImage['width'] }}" height="{{ $bannerImage['height'] }}" alt=""
                     fetchpriority="high" decoding="async">
            </div>
        @endif
        <div class="page-banner__tint"></div>
        <div class="page-banner__vignette"></div>
        <div class="page-banner__grain"></div>
    </div>
    <svg class="page-banner__curve" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0,56 L0,24 C240,52 480,60 720,44 C960,28 1200,8 1440,22 L1440,56 Z"></path>
    </svg>
    <div class="container page-banner__content">
        <h1 class="page-banner__title">{{ $bannerTitle }}</h1>
        @if (! empty($bannerText))
            <p class="page-banner__text">{{ $bannerText }}</p>
        @endif
    </div>
</section>

<script>
    // Save-Data: keep the banner static (reduced motion is handled in CSS).
    (function () {
        var c = navigator.connection;
        if (c && c.saveData) {
            var b = document.currentScript && document.currentScript.previousElementSibling;
            if (b && b.classList.contains('page-banner')) b.classList.add('page-banner--static');
        }
    })();
</script>

@once
@push('styles')
<style>
    /* ===== Shared page banner (same look as the About / news / portfolio banners) ===== */
    .page-banner {
        --pb-buffer: 36px;
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        min-height: 460px;
        padding: 96px 0 84px;
        background: #06122b;
        color: #fff;
        text-align: center;
    }

    .page-banner__media { position: absolute; inset: 0; z-index: 0; pointer-events: none; }

    /* Very slow, gentle, alternating zoom, anchored below the top edge so it
       only ever increases the top crop. */
    .page-banner__zoom {
        position: absolute;
        inset: 0;
        transform-origin: 50% 40%;
        animation: pageBannerZoom 26s ease-in-out infinite alternate;
    }

    @keyframes pageBannerZoom {
        from { transform: scale(1); }
        to { transform: scale(1.06); }
    }

    /* Hide the top share of the photo (room-number signs), as on About:
       either a share of the width-scaled photo (--pb-cut-w) or, when the
       photo is scaled to the height, k x (banner + buffer). */
    .page-banner__img {
        --pb-cut: max(var(--pb-cut-w), calc(var(--pb-cut-k) * (100% + var(--pb-buffer))));
        position: absolute;
        left: 0;
        top: calc(-1 * var(--pb-cut));
        width: 100%;
        max-width: none;
        height: calc(100% + var(--pb-buffer) + var(--pb-cut));
        object-fit: cover;
        object-position: 50% 0;
    }

    /* Navy-to-brand-blue grade, darker at the bottom where the text sits. */
    .page-banner__tint {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(115deg, rgba(59, 130, 246, 0.42) 0%, rgba(59, 130, 246, 0.12) 45%, rgba(59, 130, 246, 0) 70%),
            linear-gradient(180deg, rgba(4, 12, 34, 0.45) 0%, rgba(4, 12, 34, 0.6) 45%, rgba(4, 12, 34, 0.86) 100%);
    }

    .page-banner__vignette {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 50% 40%, rgba(4, 12, 34, 0) 50%, rgba(4, 12, 34, 0.5) 100%);
    }

    .page-banner__grain {
        position: absolute;
        inset: -50%;
        opacity: 0.06;
        mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    /* No photo: navy gradient with a light dot grid (as on /news). */
    .page-banner--plain .page-banner__media {
        background:
            radial-gradient(circle at 1px 1px, rgba(147, 197, 253, 0.22) 1px, transparent 0) 0 0 / 22px 22px,
            radial-gradient(ellipse at 20% 10%, rgba(59, 130, 246, 0.55), transparent 55%),
            radial-gradient(ellipse at 90% 90%, rgba(30, 64, 175, 0.6), transparent 60%),
            linear-gradient(160deg, #0b1f4d 0%, #06122b 70%);
    }

    .page-banner__curve {
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        z-index: 1;
        width: 100%;
        height: 56px;
        display: block;
        fill: var(--pb-curve, #fff);
    }

    .page-banner__content { position: relative; z-index: 2; }

    .page-banner__title {
        margin: 0 auto 18px;
        max-width: 900px;
        color: #fff;
        font-size: 48px;
        font-weight: 700;
        line-height: 1.15;
        text-shadow: 0 2px 18px rgba(4, 12, 34, 0.55);
    }

    .page-banner__text {
        margin: 0 auto;
        max-width: 640px;
        color: rgba(255, 255, 255, 0.92);
        font-size: 16px;
        line-height: 1.6;
        text-shadow: 0 1px 10px rgba(4, 12, 34, 0.6);
    }

    @media (max-width: 991.98px) {
        .page-banner { min-height: 380px; }
        .page-banner__title { font-size: 36px; }
    }

    @media (max-width: 767.98px) {
        .page-banner { min-height: 0; padding: 48px 0 56px; }
        .page-banner__grain { display: none; }
        .page-banner__curve { height: 40px; }
    }

    /* Text spans the full width on phones and fills short landscape screens: darken evenly. */
    @media (max-width: 767.98px), (max-height: 500px) and (orientation: landscape) {
        .page-banner__tint { background: linear-gradient(180deg, rgba(4, 12, 34, 0.66) 0%, rgba(4, 12, 34, 0.74) 50%, rgba(4, 12, 34, 0.9) 100%); }
    }

    @media (max-width: 575.98px) {
        .page-banner__title { font-size: 28px; }
    }

    /* Short landscape screens (phones turned sideways). */
    @media (max-height: 500px) and (orientation: landscape) {
        .page-banner { min-height: 0; padding: 56px 0 56px; }
    }

    .page-banner--static .page-banner__zoom { animation: none; }

    @media (prefers-reduced-motion: reduce) {
        .page-banner__zoom { animation: none; }
    }

    /* ---- bannerFit 'whole': the entire photo, natural colours ---- */
    /* At most one screen: viewport minus the navbar and any strip under it (--top-strip-h,
       set by that strip while it is shown). When that caps the height, the whole photo
       scales down, centred on navy. */
    .page-banner--whole { width: 100%; min-height: 0; aspect-ratio: var(--pb-ratio); max-height: calc(100svh - 56px - var(--top-strip-h, 0px)); padding: 0 0 84px; }
    .page-banner--whole .page-banner__zoom { animation: none; }
    .page-banner--whole .page-banner__img { top: 0; height: 100%; object-fit: contain; object-position: 50% 50%; }
    .page-banner--whole .page-banner__tint {
        background: linear-gradient(0deg, rgba(4, 12, 34, 0.88) 0%, rgba(4, 12, 34, 0.72) 26%, rgba(4, 12, 34, 0.25) 46%, rgba(4, 12, 34, 0) 60%);
    }
    .page-banner--whole .page-banner__vignette,
    .page-banner--whole .page-banner__grain { display: none; }
    /* Phones and short landscape screens: the photo in full on top, the text below it on navy. */
    @media (max-width: 767.98px), (max-height: 500px) and (orientation: landscape) {
        .page-banner--whole { aspect-ratio: auto; display: block; padding: 0 0 56px; }
        .page-banner--whole .page-banner__media { position: relative; aspect-ratio: var(--pb-ratio); }
        .page-banner--whole .page-banner__tint { display: none; }
        .page-banner--whole .page-banner__content { padding-top: 24px; }
    }
</style>
@endpush
@endonce
