{{--
    About Page 5-Year Anniversary Journey Culmination Feature
    Seamlessly integrates the circular centerpiece into the company evolution narrative.
--}}
@php
    $aboutHeadline = \App\Support\Anniversary::headline();
    $aboutTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-about-feature" id="annivAboutCard" role="region" aria-label="Sky Link 5-Year Milestone">
    <div class="anniv-about-feature__inner">
        <div class="anniv-about-feature__text">
            <div class="anniv-about-feature__tag">
                <span class="anniv-about-feature__year">2026 Milestone</span>
                <span class="anniv-about-feature__sep" aria-hidden="true">•</span>
                <span>5-Year Anniversary</span>
            </div>
            <h3 class="anniv-about-feature__title">{{ $aboutHeadline ?? 'Five Years of Practical Technology Solutions' }}</h3>
            <p class="anniv-about-feature__desc">{{ $aboutTagline ?? 'Five years of engineering solutions, advancing technology, and building for what comes next.' }}</p>
        </div>
        <div class="anniv-about-feature__centerpiece">
            @include('partials.anniversary.circular-centerpiece', [
                'size' => 'compact',
                'id' => 'annivAboutCenterpiece',
                'showCopy' => false,
            ])
        </div>
    </div>
</div>
