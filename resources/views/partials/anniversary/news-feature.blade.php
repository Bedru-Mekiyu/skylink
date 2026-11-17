{{--
    News Page Editorial 5-Year Anniversary Announcement Card
    Distinct composition: circular orbital 5 emblem with sequential year dots,
    editorial milestone header, subtle light sweep, and corporate journey CTA.
--}}
@php
    $newsHeadline = \App\Support\Anniversary::headline();
    $newsTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-news-card" id="annivNewsCard" role="region" aria-label="Sky Link 5th Anniversary Milestone">
    <div class="anniv-news-card__glow" aria-hidden="true"></div>
    <div class="anniv-news-card__sheen" aria-hidden="true"></div>

    <div class="anniv-news-card__inner">
        {{-- Left: Distinct Circular Orbital 5 Emblem with Sequential Year Markers --}}
        <div class="anniv-news-card__emblem-stage" aria-hidden="true">
            <div class="anniv-news-card__orbit-ring"></div>
            {{-- Year markers on the orbit --}}
            <span class="anniv-news-card__dot-year anniv-news-card__dot-year--1">01</span>
            <span class="anniv-news-card__dot-year anniv-news-card__dot-year--2">02</span>
            <span class="anniv-news-card__dot-year anniv-news-card__dot-year--3">03</span>
            <span class="anniv-news-card__dot-year anniv-news-card__dot-year--4">04</span>
            <span class="anniv-news-card__dot-year anniv-news-card__dot-year--5">05</span>

            <div class="anniv-news-card__emblem-core">
                <span class="anniv-news-card__emblem-num">5</span>
                <span class="anniv-news-card__emblem-unit">YRS</span>
            </div>
        </div>

        {{-- Right: Editorial Text Content --}}
        <div class="anniv-news-card__text">
            <div class="anniv-news-card__meta">
                <span class="anniv-news-card__kicker">Company Milestone</span>
                <span class="anniv-news-card__sep" aria-hidden="true">•</span>
                <span class="anniv-news-card__edition">5 Years of Innovation &amp; Trust</span>
            </div>
            <h2 class="anniv-news-card__title">Sky Link — 5 Years</h2>
            @if ($newsTagline)
                <p class="anniv-news-card__tagline">{{ $newsTagline }}</p>
            @endif
        </div>

        {{-- Action Button --}}
        <div class="anniv-news-card__action">
            <a href="{{ route('home') }}#anniversary-story" class="anniv-news-card__cta">
                <span>Explore Journey</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
