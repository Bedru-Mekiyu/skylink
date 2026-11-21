@extends('layouts.master')


@push('styles')

<style>
    /* ===== Page banner =====
       Photo: assets/images/about/team-banner.jpg (team at the Sky Link logo wall),
       WebP sizes from tools/local/optimize-images.php. Its top holds the logo,
       so nothing is hidden at the top (--about-hero-cut: 0). The zoom is
       anchored below the top edge and the parallax moves the photo up; a buffer
       below covers the parallax. */
    .about-hero {
        --about-hero-h: 460px;
        --about-hero-buffer: 36px;
        position: relative;
        height: var(--about-hero-h);
        overflow: hidden;
        isolation: isolate;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        background: #06122b;
        color: #fff;
        text-align: center;
    }

    .about-hero__media {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        --about-hero-scroll: 0;
    }

    /* Very slow, gentle, alternating zoom (no jump at either end). */
    .about-hero__zoom {
        position: absolute;
        inset: 0;
        transform-origin: 50% 40%;
        animation: aboutHeroZoom 26s ease-in-out infinite alternate;
    }

    @keyframes aboutHeroZoom {
        from { transform: scale(1); }
        to { transform: scale(1.06); }
    }

    .about-hero__img {
        /* Hidden strip above the frame = 19% of the displayed photo height:
           10.8vw when the photo is scaled to the width, otherwise
           0.2346 x (frame + buffer) when it is scaled to the height. */
        --about-hero-cut: 0px;
        position: absolute;
        left: 0;
        top: calc(-1 * var(--about-hero-cut));
        width: 100%;
        max-width: none;
        height: calc(var(--about-hero-h) + var(--about-hero-buffer) + var(--about-hero-cut));
        object-fit: cover;
        object-position: 50% 0;
        transform: translateY(calc(var(--about-hero-scroll) * -36px));
        will-change: transform;
    }

    /* Navy-to-brand-blue grade, darker at the bottom where the title sits. */
    .about-hero__tint {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(115deg, rgba(59, 130, 246, 0.42) 0%, rgba(59, 130, 246, 0.12) 45%, rgba(59, 130, 246, 0) 70%),
            linear-gradient(180deg, rgba(4, 12, 34, 0.30) 0%, rgba(4, 12, 34, 0.42) 45%, rgba(4, 12, 34, 0.82) 100%);
    }

    .about-hero__vignette {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 50% 40%, rgba(4, 12, 34, 0) 50%, rgba(4, 12, 34, 0.5) 100%);
    }

    /* Same faint static grain as the homepage hero. */
    .about-hero__grain {
        position: absolute;
        inset: -50%;
        opacity: 0.06;
        mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    /* Curved bottom edge in the next section's exact colour (.about-intro, white). */
    .about-hero__curve {
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        z-index: 1;
        width: 100%;
        height: 56px;
        display: block;
        fill: #ffffff;
    }

    .about-hero__content {
        position: relative;
        z-index: 2;
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px 84px;
        transform: translateY(calc(var(--about-hero-title-shift, 0) * 1px));
        opacity: var(--about-hero-title-opacity, 1);
        will-change: transform, opacity;
    }

    .about-hero .hero-title {
        color: #fff;
        margin-bottom: 0;
        text-shadow: 0 2px 18px rgba(4, 12, 34, 0.55);
    }

    .hero-title {
        font-size: 72px;
        font-weight: 700;
        margin-bottom: 30px;
        line-height: 1.1;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    /* Word-by-word title reveal (same feel as the homepage). */
    .about-reveal-pending .about-hero .hero-title {
        opacity: 0;
    }

    .about-hero-word {
        display: inline-block;
        opacity: 0;
        transform: translateY(0.45em);
        animation: aboutHeroWordIn 0.8s cubic-bezier(0.2, 0.7, 0.2, 1) forwards;
        animation-delay: var(--about-hero-delay, 0ms);
    }

    @keyframes aboutHeroWordIn {
        to { opacity: 1; transform: none; }
    }

    @media (max-width: 991.98px) {
        .about-hero { --about-hero-h: 380px; }
    }

    @media (max-width: 767.98px) {
        .about-hero__grain { display: none; }
        .about-hero__content { padding-bottom: 64px; }
        .about-hero__curve { height: 40px; }
    }

    @media (max-width: 575.98px) {
        .about-hero { --about-hero-h: 260px; }
    }

    /* The whole team photo in its own shape and natural colours: no crop, zoom
       or parallax shift, no blue grade; only the bottom, behind the title, is
       darkened so it stays readable. */
    /* At most one screen: viewport minus the navbar and any strip under it (--top-strip-h,
       set by that strip while it is shown). The photo always runs edge to edge; when the
       height is capped only the top/bottom are trimmed, keeping the logo and faces (focal
       point ~6% down). Scrolling past the banner slides the photo up to reveal the rest. */
    /* Banner height: about half a screen (News 460-489px, Portfolio 480px, same scale), never a full screen.
       The photo is cropped by object-fit: cover; focal point 6% keeps the logo and faces and hides the strip along
       the very top edge of the photo (where the room signs are). Shorter on tablet, phone and short landscape. */
    .about-hero { width: 100%; aspect-ratio: auto; height: clamp(340px, 50svh, 560px); }
    @media (max-width: 991.98px) { .about-hero { height: clamp(300px, 42svh, 440px); } }
    @media (max-width: 575.98px) { .about-hero { height: clamp(220px, 32svh, 300px); } }
    @media (max-height: 500px) and (orientation: landscape) { .about-hero { height: clamp(220px, 62svh, 320px); } }
    .about-hero__zoom { animation: none; }
    .about-hero__img { top: 0; height: 100%; transform: none; object-fit: cover; object-position: 50% 0%; }
    @supports (animation-timeline: scroll(root)) {
        @media (prefers-reduced-motion: no-preference) {
            .about-hero__img { animation: aboutHeroReveal linear both; animation-timeline: scroll(root); animation-range: 0 80svh; }
            @keyframes aboutHeroReveal { from { object-position: 50% 0%; } to { object-position: 50% 85%; } }
        }
    }
    .about-hero__tint { background: linear-gradient(0deg, rgba(6, 18, 43, 0.85) 0%, rgba(6, 18, 43, 0.5) 20%, rgba(6, 18, 43, 0) 42%); }
    .about-hero__vignette, .about-hero__grain { display: none; }
    @media (max-width: 575.98px) {
        /* Short phone banner: the title covers more of the photo, so the shade reaches higher. */
        .about-hero__tint { background: linear-gradient(0deg, rgba(6, 18, 43, 0.88) 0%, rgba(6, 18, 43, 0.6) 35%, rgba(6, 18, 43, 0) 65%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .about-hero__zoom { animation: none; }
        .about-hero__img { transform: none; }
        .about-hero__content { transform: none; opacity: 1; }
        .about-hero-word { animation: none; opacity: 1; transform: none; }
    }

    /* The Teams Grid row sits outside a .container, so its negative gutter
       margins made the page ~4px wider than phone/tablet screens. */
    .team-section {
        overflow-x: hidden;
        overflow-x: clip;
    }

    .section-tag {
        color: #3B82F6;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
        display: block;
    }


    .about-image-container {
        position: relative;
        padding-left: 20px;
    }

    .about-main-image {
        width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    /* Video Section */
    .video-section {
        padding: 70px 0;
        background: white;
        
    }

    .video-title {
        color: #3B82F6;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 40px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .video-container {
        position: relative;

        max-width: 100%;
        margin: 0 auto;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .video-thumbnail {
        width: 100%;
        height: 450px;
        background: url('{{ asset('assets/images/about/promoo1.png') }}') center/cover;
        position: relative;
    }

    /* Keyboard focus ring, drawn inside because the wrapper clips overflow. */
    .video-thumbnail:focus-visible {
        outline: 3px solid #fff;
        outline-offset: -6px;
        box-shadow: inset 0 0 0 6px #0d6efd;
    }

    .play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .play-button:hover {
        background: white;
        transform: translate(-50%, -50%) scale(1.1);
    }

    .play-button i {
        font-size: 24px;
        color: #3B82F6;
        margin-left: 3px;
    }

    .video-caption {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
    }

    /* Team Section */
    .team-section {
        padding: 100px 0;
        background: #f8f9fa;
    }

    .team-title {
        text-align: center;
        margin-bottom: 60px;
    }

    .team-title .section-tag {
        display: block;
        text-align: center;
    }

    .team-title h2 {
        font-size: 36px;
        font-weight: 700;
        color: #333;
        margin-top: 15px;
    }

    .team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .team-member {
        background: white;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        text-align: center;
        max-width: 300px;
    margin: 0 auto;
    }

    .team-member:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .team-photo {
    width: 100%;
    height: 350px;
    background: #e9ecef;
    position: relative;
    overflow: hidden;
}

.team-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
object-position: 50% 15%; 
}
    /* Management cards: one 4:5 portrait ratio for everyone, 12px corners (no circles). */
    .team-card__photo { position: relative; width: min(100%, 240px); aspect-ratio: 4 / 5; border-radius: 12px; overflow: hidden; display: grid; place-items: center; }
    .team-card__photo picture { display: contents; }
    /* CEO / founder card: clearly the largest (about 1.7x the other photos on desktop, ~86% of the width on phones, capped). */
    .team-card--lead .team-card__photo { --lead-ratio: 4 / 3; width: min(90%, 400px); aspect-ratio: var(--lead-ratio); border-radius: 14px; box-shadow: 0 18px 44px rgba(15, 42, 92, 0.18); margin-bottom: 0.75rem !important; }
    .team-card--lead .team-card__photo img { object-position: 50% 24%; } /* landscape frame: head and shoulders of the large original */
    .team-card--lead p { margin-bottom: 0.5rem; }
    .team-card--lead h3 { font-size: clamp(1.375rem, 1.2rem + 0.7vw, 1.75rem); }
    .team-card--lead p { font-size: clamp(1.0625rem, 1rem + 0.3vw, 1.25rem); }
    .team-card__photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 50% 20%; display: block; }
    .team-card__initials { font: 700 3rem/1 var(--font-heading); color: #64748b; letter-spacing: 0.02em; }
    .team-info {
        padding: 25px;
    }

    .team-name {
        font-size: 20px;
        font-weight: 600;
        color: #3B82F6;
        margin-bottom: 5px;
    }

    .team-role {
        color: #666;
        font-size: 14px;
    }


    /* Awards Section */
    .awards-section {
        display: flow-root; /* keeps the last item margin inside the section (no white strip before the Gallery) */
        padding: 100px 0 8px; /* the Gallery follows: modest gap (about 60 px incl. the Gallery top padding and the last item margin) */
        background: #f8f9fa;
    }
    @media (max-width: 575.98px) { .awards-section { padding-bottom: 0; } }

    .awards-header {
        text-align: center;
        margin-bottom: 44px;
    }

    .awards-header .section-tag {
        display: block;
        text-align: center;
    }

    .awards-header h2 {
        font-size: 36px;
        font-weight: 700;
        color: #333;
        margin-top: 15px;
    }

    .awards-categories {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 50px;
    }

    .award-category {
        text-align: center;
    }

    .award-category h4 {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .award-category span {
        color: #3B82F6;
        font-size: 14px;
        font-weight: 500;
    }

    .awards-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .award-item {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .award-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .award-image {
        width: 100%;
        height: 200px;
        background: #e9ecef;
    }

    .award-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .award-content {
        padding: 20px;
    }

    .award-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        line-height: 1.3;
    }

    .award-year {
        color: #666;
        font-size: 14px;
    }

    .awards-grid-bottom {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }

    
    * {
       
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    .skylink-gradient-primary {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    }
    
    .skylink-gradient-secondary {
        background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);
    }
    
    .skylink-gradient-dark {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }
    
    .skylink-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .skylink-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .skylink-icon {
        transition: all 0.3s ease;
    }
    
    .skylink-icon:hover {
        transform: scale(1.05);
    }
    
    .skylink-text-gradient {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    @media (max-width: 768px) {
        .skylink-timeline-desktop { display: none; }
        .skylink-timeline-mobile { display: block; }
    }
    @media (min-width: 769px) {
        .skylink-timeline-desktop { display: block; }
        .skylink-timeline-mobile { display: none; }
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .hero-title {
            font-size: 48px;
        }

        .section-title {
            font-size: 28px;
        }

        .team-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .team-photo {
        height: 300px;
        width: 300px;
    }

    .team-name {
        font-size: 18px;
    }

    .team-role {
        font-size: 13px;
    }

        .growth-content {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .awards-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
   
        .hero-title {
            font-size: 36px;
        }

        .about-features {
            flex-direction: column;
            gap: 20px;
        }

        .team-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .awards-categories {
            flex-direction: column;
            gap: 20px;
        }

        .awards-grid,
        .awards-grid-bottom {
            grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 568px) {
            .team-grid{
                grid-template-columns: 1fr;

            }
            .team-photo {
        height: 250px;
    }

    .team-name {
        font-size: 16px;
    }

    .team-role {
        font-size: 12px;
    }
            .awards-grid,
            .awards-grid-bottom{
                grid-template-columns: repeat(1, 1fr);

            }

        }

</style>
<link rel="preload" as="image" href="{{ asset('assets/images/about/team-banner.jpg') }}" imagesrcset="{{ \App\Support\ResponsiveImage::webpSrcset('assets/images/about/team-banner.jpg') }}" imagesizes="100vw" fetchpriority="high">
<script>
    // Hide the banner title until its word-by-word reveal is ready; failsafe 3 s.
    (function (root) {
        if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        root.classList.add('about-reveal-pending');
        setTimeout(function () { root.classList.remove('about-reveal-pending'); }, 3000);
    })(document.documentElement);
</script>
@endpush

@section('content')

<!-- Hero Section -->
<section class="hero-section about-hero" data-about-hero>
    {{-- Breadcrumb (Home / About Us) removed: the navbar already shows where you are. --}}
    <div class="about-hero__media" aria-hidden="true">
        <div class="about-hero__zoom">
            <img class="about-hero__img"
                 src="{{ asset('assets/images/about/team-banner.jpg') }}"
                 srcset="{{ \App\Support\ResponsiveImage::webpSrcset('assets/images/about/team-banner.jpg') }}" sizes="100vw"
                 width="{{ \App\Support\ResponsiveImage::size('assets/images/about/team-banner.jpg')[0] ?? 1920 }}"
                 height="{{ \App\Support\ResponsiveImage::size('assets/images/about/team-banner.jpg')[1] ?? 1263 }}" alt=""
                 fetchpriority="high" decoding="async">
        </div>
        <div class="about-hero__tint"></div>
        <div class="about-hero__vignette"></div>
        <div class="about-hero__grain"></div>
    </div>
    <svg class="about-hero__curve" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0,56 L0,24 C240,52 480,60 720,44 C960,28 1200,8 1440,22 L1440,56 Z"></path>
    </svg>
    <div class="about-hero__content">
        <h1 class="hero-title">About Us</h1>
    </div>
</section>

<!-- About Section -->

@include('partials.about-intro')

<section class="bg-light py-5 px-3 px-lg-5">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6 text-center text-lg-start">
                <style>
    .ceo-photo-mask {
        width: 100%;
        max-width: 450px;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border-radius: 50% 50% 0 50%; 
        overflow: hidden;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>

<img src="{{ $managementTeam[0]['image'] }}"
     alt="CEO {{ $managementTeam[0]['name'] }}"
     class="ceo-photo-mask img-fluid">


                <blockquote class="blockquote text-muted fst-italic mt-4">
                    “We're always here to support and continue delivering excellence.”
                </blockquote>
                <p class="fw-semibold text-dark">_CEO, {{ $managementTeam[0]['name'] }}</p>
            </div>

            <div class="col-lg-6">
                <h2 class="display-5 fw-bold text-dark mb-4">CEO MESSAGE</h2>
                <p class="text-muted text-size-medium mb-4">
                    Skylink is simply a learning organization with continuous improvement. Our  team are self-motivated and passionate about  their  career  and  purpose  in life. 
Everyone at Skylink strives to achieve result with best performance everyday. Confidence of clients on us due to our commitment to get the job done with excellence is what  drives us to do better everyday. 
We are not in the business of making quick money by chasing obvious opportunities and shortcuts. In-fact, we already chose to win through difficult situations   with   hard   work,   competence, consistency and integrity. 
We lead the market by creating disciplined culture that will have much bigger effect on the work ethics of our society and  overall  technological development of  Ethiopia in long term.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Video Section -->
<section class="video-section">
    <div class="container">
        <h2 class="video-title">Let's See Our Promo Video</h2>
        <div class="video-container position-relative">
            <!-- Video element -->
            {{-- preload="none": the 5.9 MB file loads only when the visitor presses play. --}}
            <video id="promoVideo" width="100%" height="auto" controls preload="none" style="display: none;">
                <source src="{{ asset('assets/videos/PROMOVIDEO.mp4') }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>

            <!-- Custom Thumbnail & Play Button -->
            <div id="videoThumbnail" class="video-thumbnail" style="cursor: pointer;" role="button" tabindex="0" aria-label="Watch video: Sky Link promo">
                <div class="play-button">
                    <i class="fas fa-play"></i>
                </div>
                <div class="video-caption">Watch video</div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 px-3 container">
    <div class="mb-5">
        <div class="row align-items-start g-4">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold text-black">
                    Why Clients Choose Us
                </h2>
            </div>
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="row g-4 mt-5">
        <!-- Vision -->
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100">
                <div class="card-body p-4">
                    <div class="mb-4 text-primary fs-3">
                        <i class="fa fa-eye"></i>
                    </div>
                    <h5 class="card-title fw-semibold text-dark mb-3">Our Vision</h5>
                    <p class="card-text text-muted mb-4">
                        To be a global leader in digital innovation, empowering businesses through cutting-edge software solutions that shape a smarter, connected future.
                    </p>
                </div>
            </div>
        </div>

        <!-- Mission -->
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100">
                <div class="card-body p-4">
                    <div class="mb-4 text-primary fs-3">
                        <i class="fa fa-bullseye"></i>
                    </div>
                    <h5 class="card-title fw-semibold text-dark mb-3">Our Mission</h5>
                    <p class="card-text text-muted mb-4">
                        To deliver reliable and scalable IT solutions tailored to our clients' unique needs, while maintaining a culture of integrity, innovation, and continuous improvement.
                    </p>
                </div>
            </div>
        </div>

        <!-- Values -->
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100">
                <div class="card-body p-4">
                    <div class="mb-4 text-primary fs-3">
                        <i class="fa fa-star"></i>
                    </div>
                    <h5 class="card-title fw-semibold text-dark mb-3">Our Values</h5>
                    <p class="card-text text-muted mb-4">
                        We value innovation, transparency, collaboration, and excellence. These principles drive our work and define how we build trust with our clients and team.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section pt-5 pb-4 bg-light">
    <div class="container">
        <!-- Section Title -->
        <div class="text-center mb-3">
        <h2 class="display-5 fw-bold text-dark mb-2">Meet the People Behind the Vision</h2>
        
    </div>

        <!-- Management Team -->
        <div class="mb-4">
        <h2 class="h4 text-uppercase text-center fw-semibold mb-3 text-dark">Our Management Team</h2>
        {{-- CEO alone on top, the rest centred below --}}
        <div class="row g-4 justify-content-center">
            <div class="col-12">
                @include('components.team-member-card', ['member' => $managementTeam[0]])
            </div>
        </div>
        <div class="row g-4 mt-0 justify-content-center px-2 px-sm-0">
            @foreach (array_slice($managementTeam, 1) as $member)
                <div class="col-6 col-md-6 col-lg-3">
                    @include('components.team-member-card', ['member' => $member])
                </div>
            @endforeach
        </div>
    </div>
    
    @include('partials.departments')

</section>

<section>
{{-- Compact Journey Map Section --}}
<section id="journey" style="padding: 60px 0; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
 position: relative;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 15px; position: relative;">
        <div style="text-align: center; margin-bottom: 40px;">
<h2 style="font-size: 2.5rem; font-weight: 800; color: rgb(25, 28, 35); margin-bottom: 15px; letter-spacing: -0.5px;">
                Journey <span class="skylink-text-gradient">Timeline</span>
            </h2>
            <p style="font-size: 1rem; color: rgb(25, 28, 35); max-width: 500px; margin: 0 auto; line-height: 1.5;">
                Our evolution in the technology landscape
            </p>
        </div>

        {{-- Compact Desktop Timeline --}}
        <div class="skylink-timeline-desktop" style="position: relative;">
            <div style="position: absolute; left: 46%; top: 0; bottom: 0; width: 3px; background: linear-gradient(to bottom, #d0defb, #94a3b8); transform: translateX(-50%); border-radius: 2px;"></div>
            
            <div style="display: flex; flex-direction: column; gap: 0px;">
                {{-- IT Consultancy --}}
                <div style="display: flex; align-items: center;">
                    <div style="width: 45%; text-align: right; padding-right: 25px;">
                        <div class="skylink-card" style="border-radius: 10px; padding: 15px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);"></div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">IT Consultancy</h3>
                            <p style="color: #2563eb; font-weight: 600; font-size: 0.9rem;">Dec 2021</p>
                        </div>
                    </div>
                    <div style="width: 20px; height: 20px; background: #2563eb; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10;"></div>
                    <div style="width: 45%;"></div>
                </div>

                {{-- Real State Management --}}
                <div style="display: flex; align-items: center;">
                    <div style="width: 45%;"></div>
                    <div style="width: 20px; height: 20px; background: #94a3b8; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10;"></div>
                    <div style="width: 45%; padding-left: 25px;">
                        <div class="skylink-card" style="border-radius: 10px; padding: 15px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);"></div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">Network Infrastructure</h3>
                            <p style="color: #64748b; font-weight: 600; font-size: 0.9rem;">Apr 2022</p>
                        </div>
                    </div>
                </div>

                {{-- ERP Software --}}
                <div style="display: flex; align-items: center;">
                    <div style="width: 45%; text-align: right; padding-right: 25px;">
                        <div class="skylink-card" style="border-radius: 10px; padding: 15px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);"></div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">ERP Software</h3>
                            <p style="color: #2563eb; font-weight: 600; font-size: 0.9rem;">Jun 2022</p>
                        </div>
                    </div>
                    <div style="width: 20px; height: 20px; background: #2563eb; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10;"></div>
                    <div style="width: 45%;"></div>
                </div>

                {{-- Software as a Service --}}
                <div style="display: flex; align-items: center;">
                    <div style="width: 45%;"></div>
                    <div style="width: 20px; height: 20px; background: #94a3b8; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10;"></div>
                    <div style="width: 45%; padding-left: 25px;">
                        <div class="skylink-card" style="border-radius: 10px; padding: 15px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);"></div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">Software as a Service</h3>
                            <p style="color: #64748b; font-weight: 600; font-size: 0.9rem;">Nov 2023</p>
                        </div>
                    </div>
                </div>

                {{-- Network & Cyber Security --}}
                <div style="display: flex; align-items: center;">
                    <div style="width: 45%; text-align: right; padding-right: 25px;">
                        <div class="skylink-card" style="border-radius: 10px; padding: 15px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);"></div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">Network & Cyber Security</h3>
                            <p style="color: #2563eb; font-weight: 600; font-size: 0.9rem;">July 2023</p>
                        </div>
                    </div>
                    <div style="width: 20px; height: 20px; background: #2563eb; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10;"></div>
                    <div style="width: 45%;"></div>
                </div>

                {{-- Technology Training --}}
                <div style="display: flex; align-items: center;">
                    <div style="width: 45%;"></div>
                    <div style="width: 20px; height: 20px; background: #94a3b8; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10;"></div>
                    <div style="width: 45%; padding-left: 25px;">
                        <div class="skylink-card" style="border-radius: 10px; padding: 15px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);"></div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">Technology Training</h3>
                            <p style="color: #64748b; font-weight: 600; font-size: 0.9rem;">Sept 2023</p>
                        </div>
                    </div>
                </div>
                 {{-- interior design --}}
                <div style="display: flex; align-items: center;">
                    <div style="width: 45%; text-align: right; padding-right: 25px;">
                        <div class="skylink-card" style="border-radius: 10px; padding: 15px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);"></div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">Interior Design</h3>
                            <p style="color: #2563eb; font-weight: 600; font-size: 0.9rem;">Sept 2024</p>
                        </div>
                    </div>
                    <div style="width: 20px; height: 20px; background: #2563eb; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10;"></div>
                    <div style="width: 45%;"></div>
                </div>
            </div>
        </div>

        {{-- Compact Mobile Timeline --}}
        <div class="skylink-timeline-mobile">
            <div style="position: relative;">
                <div style="position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, #2563eb, #94a3b8); border-radius: 1px;"></div>
                
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; align-items: flex-start;">
                        <div style="width: 16px; height: 16px; background: #2563eb; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10; flex-shrink: 0; margin-top: 5px;"></div>
                        <div style="margin-left: 15px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.2); flex: 1;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 3px;">IT Consultancy</h3>
                            <p style="color: #2563eb; font-weight: 600; font-size: 0.875rem;">Dec 2021</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start;">
                        <div style="width: 16px; height: 16px; background: #94a3b8; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10; flex-shrink: 0; margin-top: 5px;"></div>
                        <div style="margin-left: 15px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.2); flex: 1;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 3px;">Real State Management</h3>
                            <p style="color: #64748b; font-weight: 600; font-size: 0.875rem;">Jun 2021</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start;">
                        <div style="width: 16px; height: 16px; background: #2563eb; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10; flex-shrink: 0; margin-top: 5px;"></div>
                        <div style="margin-left: 15px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.2); flex: 1;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 3px;">ERP Software</h3>
                            <p style="color: #2563eb; font-weight: 600; font-size: 0.875rem;">Jun 2016</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start;">
                        <div style="width: 16px; height: 16px; background: #94a3b8; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10; flex-shrink: 0; margin-top: 5px;"></div>
                        <div style="margin-left: 15px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.2); flex: 1;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 3px;">Software as a Service</h3>
                            <p style="color: #64748b; font-weight: 600; font-size: 0.875rem;">Nov 2023</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start;">
                        <div style="width: 16px; height: 16px; background: #2563eb; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10; flex-shrink: 0; margin-top: 5px;"></div>
                        <div style="margin-left: 15px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.2); flex: 1;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 3px;">Network & Cyber Security</h3>
                            <p style="color: #2563eb; font-weight: 600; font-size: 0.875rem;">July 2023</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start;">
                        <div style="width: 16px; height: 16px; background: #94a3b8; border-radius: 50%; border: 3px solid #0f172a; position: relative; z-index: 10; flex-shrink: 0; margin-top: 5px;"></div>
                        <div style="margin-left: 15px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.2); flex: 1;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 3px;">Technology Training</h3>
                            <p style="color: #64748b; font-weight: 600; font-size: 0.875rem;">Sept 2023</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Compact Our Process Section --}}
<section style="padding: 50px 0; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-bottom: 15px; letter-spacing: -0.5px;">
                OUR <span class="skylink-text-gradient">PROCESS</span>
            </h2>
            <div style="max-width: 800px; margin: 0 auto;  backdrop-filter: blur(10px); border-radius: 10px; padding: 20px; border: 1px solid rgba(255,255,255,0.3);">
                <p style="font-size: 1rem; color: #475569; line-height: 1.6; margin-bottom: 10px;">
                    Master in-demand skills with SkyLink! We offer comprehensive training and support in web design, development, programming, 
                    graphic design, and IT fundamentals.
                </p>
                <p style="font-size: 1rem; color: #475569; line-height: 1.6;">
                    <span style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 600;">Empower yourself and your team with SkyLink.</span>
                </p>
            </div>
        </div>

        {{-- Compact Process Steps --}}
        <div style="position: relative;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; position: relative; z-index: 10;">
                {{-- Meet & Consult --}}
                <div style="text-align: center;">
                    <div class="skylink-icon" style="width: 70px; height: 70px; margin: 0 auto 15px; background: linear-gradient(135deg, #4f6fc5 0%, #578af7 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);">
                        <svg style="width: 35px; height: 35px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">MEET & CONSULT</h3>
                    <p style="color: #64748b; font-size: 0.875rem; line-height: 1.4;">Understanding your needs</p>
                </div>

                {{-- Design --}}
                <div style="text-align: center;">
                    <div class="skylink-icon" style="width: 70px; height: 70px; margin: 0 auto 15px; background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(100, 116, 139, 0.3);">
                        <svg style="width: 35px; height: 35px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">DESIGN</h3>
                    <p style="color: #64748b; font-size: 0.875rem; line-height: 1.4;">Creating solutions</p>
                </div>

                {{-- Implement --}}
                <div style="text-align: center;">
                    <div class="skylink-icon" style="width: 70px; height: 70px; margin: 0 auto 15px; background: linear-gradient(135deg, #4f6fc5 0%, #578af7 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);">
                        <svg style="width: 35px; height: 35px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">IMPLEMENT</h3>
                    <p style="color: #64748b; font-size: 0.875rem; line-height: 1.4;">Bringing vision to life</p>
                </div>

                {{-- Training --}}
                <div style="text-align: center;">
                    <div class="skylink-icon" style="width: 70px; height: 70px; margin: 0 auto 15px; background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(100, 116, 139, 0.3);">
                        <svg style="width: 35px; height: 35px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">TRAINING</h3>
                    <p style="color: #64748b; font-size: 0.875rem; line-height: 1.4;">Empowering your team</p>
                </div>

                {{-- Support --}}
                <div style="text-align: center;">
                    <div class="skylink-icon" style="width: 70px; height: 70px; margin: 0 auto 15px; background: linear-gradient(135deg, #4f6fc5 0%, #578af7 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);">
                        <svg style="width: 35px; height: 35px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 5px;">SUPPORT</h3>
                    <p style="color: #64748b; font-size: 0.875rem; line-height: 1.4;">Ongoing assistance</p>
                </div>
            </div>
        </div>
    </div>
</section>
</section>
<!-- Awards Section -->
<section class="awards-section">
    <div class="container">
        <div class="awards-header">
            <span class="section-tag">Our Achievements</span>
            <h2>We Have Achieved Different<br>Milestones</h2>
        </div>
        
        <div class="awards-grid">
            <div class="award-item">
                <div class="award-image">
                    <img src="{{ asset('assets/images/about/achieve1.jpg') }}" alt="Award">
                </div>
                <div class="award-content">
                    <h4 class="award-title">Made a strategic partnership with Al-Faruk Multimedia Production for the development of a high-quality Audio-Visual Streaming Mobile Application.</h4>
                    <p class="award-year">2024</p>
                </div>
            </div>
            
            <div class="award-item">
                <div class="award-image">
                    <img src="{{ asset('assets/images/about/achieve4.jpg') }}" alt="Award">
                </div>
                <div class="award-content">
                    <h4 class="award-title">partnership agreement with Jabulani Merkatto Trading Center to develop their complete building management and other related systems.</h4>
                    <p class="award-year">2024</p>
                </div>
            </div>
            
            <div class="award-item">
                <div class="award-image">
                    <img src="{{ asset('assets/images/about/achieve2.jpg') }}" alt="Award">
                </div>
                <div class="award-content">
                    <h4 class="award-title">Agreement with Dire Dawa city administration for the '1st Dire International Tech Expo'.</h4>
                    <p class="award-year">2024</p>
                </div>
            </div>
            
            <div class="award-item">
                <div class="award-image">
                    <img src="{{ asset('assets/images/about/achieve3.jpg') }}" alt="Award">
                </div>
                <div class="award-content">
                    <h4 class="award-title">Attended 'Stride Ethiopia 2024 Expo'  with its strong focus on science, technology, innovation, and digitalization.</h4>
                    <p class="award-year">2024</p>
                </div>
            </div>
        </div>
        
        
    </div>
</section>

{{-- Gallery: last section, right above the footer (hidden when there are no visible photos) --}}
@include('partials.gallery')
@endsection

@push('scripts')
    {{-- Bootstrap JS comes from the layout; a second copy broke the phone menu toggle. --}}
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Video thumbnail click to show video
        document.addEventListener("DOMContentLoaded", function () {
            const thumbnail = document.getElementById("videoThumbnail");
            const video = document.getElementById("promoVideo");

            if (!thumbnail || !video) return;

            function showVideo() {
                thumbnail.style.display = "none";
                video.style.display = "block";
                video.play();
                video.focus();
            }
            thumbnail.addEventListener("click", showVideo);
            // Keyboard: the thumbnail acts as a button (Enter or Space).
            thumbnail.addEventListener("keydown", function (e) {
                if (e.key === "Enter" || e.key === " ") { e.preventDefault(); showVideo(); }
            });
        });
    </script>
    <script>
        // About banner: word-by-word title reveal + light parallax.
        (function () {
            // The layout renders the scripts stack twice; run once.
            if (window.__skyAboutHero) return;
            window.__skyAboutHero = true;

            function reducedMotion() {
                return window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
            }

            function reveal(hero) {
                var root = document.documentElement;
                var title = hero.querySelector('.hero-title');
                if (!title || !root.classList.contains('about-reveal-pending') || reducedMotion()) {
                    root.classList.remove('about-reveal-pending');
                    return;
                }
                var original = title.textContent;
                var delay = 150;
                var last = null;
                title.textContent = '';
                original.trim().split(/(\s+)/).forEach(function (part) {
                    if (/^\s+$/.test(part)) {
                        title.appendChild(document.createTextNode(' '));
                        return;
                    }
                    var span = document.createElement('span');
                    span.className = 'about-hero-word';
                    span.style.setProperty('--about-hero-delay', delay + 'ms');
                    span.textContent = part;
                    title.appendChild(span);
                    last = span;
                    delay += 120;
                });
                // Restore the plain text afterwards (screen readers read it whole).
                if (last) {
                    last.addEventListener('animationend', function () { title.textContent = original; }, { once: true });
                }
                root.classList.remove('about-reveal-pending');
            }

            function parallax(hero) {
                if (reducedMotion()) return;
                var media = hero.querySelector('.about-hero__media');
                var content = hero.querySelector('.about-hero__content');
                var active = true;
                var queued = false;

                function update() {
                    queued = false;
                    var rect = hero.getBoundingClientRect();
                    var p = rect.height ? Math.min(Math.max(-rect.top / rect.height, 0), 1) : 0;
                    media.style.setProperty('--about-hero-scroll', p.toFixed(3));
                    content.style.setProperty('--about-hero-title-shift', (p * 24).toFixed(1));
                    content.style.setProperty('--about-hero-title-opacity', (1 - p * 0.6).toFixed(3));
                }

                // Passive listener; at most one rAF per frame, only while the banner is visible.
                window.addEventListener('scroll', function () {
                    if (!active || queued) return;
                    queued = true;
                    requestAnimationFrame(update);
                }, { passive: true });

                if ('IntersectionObserver' in window) {
                    new IntersectionObserver(function (entries) {
                        active = entries[entries.length - 1].isIntersecting;
                        if (active) update();
                    }).observe(hero);
                }
                update();
            }

            function init() {
                var hero = document.querySelector('[data-about-hero]');
                if (!hero) return;
                reveal(hero);
                parallax(hero);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@endpush

