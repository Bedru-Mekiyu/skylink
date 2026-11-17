{{--
    Homepage Hero Right-Hand 5-Year Anniversary Centerpiece
    Integrated into the hero section when the campaign is active.
--}}
@php
    $heroHeadline = \App\Support\Anniversary::headline();
    $heroTagline = \App\Support\Anniversary::tagline();
    $heroFoundedYear = \App\Support\Anniversary::foundedYear();
@endphp

<aside class="anniv-hero-card" id="annivHeroCard" aria-label="Sky Link 5-Year Anniversary Celebration">
    <div class="anniv-hero-card__ambient-glow" aria-hidden="true"></div>

    {{-- Orbital Visual Centerpiece with Numeral 5 --}}
    <div class="anniv-hero-card__stage" aria-hidden="true">
        {{-- Concentric Orbital Rings --}}
        <div class="anniv-hero-card__ring anniv-hero-card__ring--outer"></div>
        <div class="anniv-hero-card__ring anniv-hero-card__ring--inner"></div>
        <div class="anniv-hero-card__orbit-node"></div>

        {{-- Glowing Signature Numeral 5 SVG --}}
        <svg class="anniv-hero-card__num-svg" viewBox="0 0 100 110" width="100" height="110" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="hero5TraceGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="35%" stop-color="#38bdf8" />
                    <stop offset="100%" stop-color="#2563eb" />
                </linearGradient>
                <radialGradient id="hero5BloomGrad" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.25" />
                    <stop offset="100%" stop-color="#1e40af" stop-opacity="0" />
                </radialGradient>
            </defs>
            {{-- Background guide track --}}
            <path class="anniv-hero-card__path-bg" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
            {{-- Inner soft bloom --}}
            <path class="anniv-hero-card__path-fill" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74 Z" />
            {{-- Animated glowing trace --}}
            <path class="anniv-hero-card__path-trace" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
            {{-- Specular flare at head of trace --}}
            <circle cx="72" cy="20" r="3.2" fill="#ffffff" filter="drop-shadow(0 0 6px #38bdf8)" />
        </svg>
    </div>

    {{-- Content Hierarchy --}}
    <div class="anniv-hero-card__content">
        <div class="anniv-hero-card__badge-row">
            <span class="anniv-hero-card__kicker">5 YEARS</span>
            @if ($heroFoundedYear)
                <span class="anniv-hero-card__years">{{ $heroFoundedYear }} — 2026</span>
            @endif
        </div>

        <p class="anniv-hero-card__sublabel">OF SKY LINK ICT SOLUTIONS</p>

        @if ($heroHeadline)
            <h2 class="anniv-hero-card__headline">{{ $heroHeadline }}</h2>
        @endif

        @if ($heroTagline)
            <p class="anniv-hero-card__tagline">{{ $heroTagline }}</p>
        @endif

        <div class="anniv-hero-card__actions">
            <a href="#anniversary-story" class="anniv-hero-card__cta">
                <span>Explore Our Journey</span>
                <i class="fas fa-arrow-down" aria-hidden="true"></i>
            </a>
            <button type="button" class="anniv-hero-card__badge-trigger" onclick="document.getElementById('annivBadgeBtn')?.click()" aria-label="Open celebration overview">
                <i class="fas fa-sparkles me-1" aria-hidden="true"></i> Overview
            </button>
        </div>
    </div>
</aside>
