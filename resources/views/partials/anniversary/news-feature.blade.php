{{--
    News Page Editorial 5-Year Anniversary Announcement Card
    Positioned at the head of the news list, above the category chips.
--}}
@php
    $newsHeadline = \App\Support\Anniversary::headline();
    $newsTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-news-card" id="annivNewsCard" role="region" aria-label="Sky Link 5th Anniversary Milestone">
    <div class="anniv-news-card__glow" aria-hidden="true"></div>

    <div class="anniv-news-card__inner">
        {{-- Emblem with glowing numeral 5 --}}
        <div class="anniv-news-card__emblem-wrap" aria-hidden="true">
            <div class="anniv-news-card__emblem-orbit"></div>
            <span class="anniv-news-card__emblem-num">5</span>
        </div>

        {{-- Editorial text content --}}
        <div class="anniv-news-card__text">
            <div class="anniv-news-card__meta">
                <span class="anniv-news-card__kicker">Company Milestone</span>
                <span class="anniv-news-card__dot" aria-hidden="true">•</span>
                <span class="anniv-news-card__edition">5th Anniversary</span>
            </div>
            <h2 class="anniv-news-card__title">Sky Link — 5 Years</h2>
            @if ($newsTagline)
                <p class="anniv-news-card__tagline">{{ $newsTagline }}</p>
            @endif
        </div>

        {{-- Subtle CTA --}}
        <div class="anniv-news-card__action">
            <a href="{{ route('home') }}#anniversary-story" class="anniv-news-card__cta">
                <span>Explore Journey</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
