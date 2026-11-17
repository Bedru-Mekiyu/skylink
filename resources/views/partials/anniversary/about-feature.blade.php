{{--
    About Page 5-Year Anniversary Journey Culmination Feature
    Seamlessly closes the company evolution narrative in the Journey Timeline section.
--}}
@php
    $aboutHeadline = \App\Support\Anniversary::headline();
    $aboutTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-about-feature" id="annivAboutCard" role="region" aria-label="Sky Link 5-Year Milestone">
    <div class="anniv-about-feature__glow" aria-hidden="true"></div>
    <div class="anniv-about-feature__inner">
        <div class="anniv-about-feature__badge" aria-hidden="true">
            <span class="anniv-about-feature__num">5</span>
            <span class="anniv-about-feature__unit">YEARS</span>
        </div>
        <div class="anniv-about-feature__text">
            <div class="anniv-about-feature__tag">
                <span class="anniv-about-feature__year">2026 Milestone</span>
                <span class="anniv-about-feature__sep" aria-hidden="true">•</span>
                <span>5-Year Anniversary</span>
            </div>
            <h3 class="anniv-about-feature__title">{{ $aboutHeadline ?? '5 Years of Sky Link' }}</h3>
            @if ($aboutTagline)
                <p class="anniv-about-feature__desc">{{ $aboutTagline }}</p>
            @endif
        </div>
        <div class="anniv-about-feature__cta-wrap">
            <a href="{{ route('home') }}#anniversary-story" class="anniv-about-feature__cta">
                <span>View Full Journey</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
