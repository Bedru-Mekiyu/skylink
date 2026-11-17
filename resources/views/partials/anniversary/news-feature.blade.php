{{--
    News Page 5-Year Anniversary Feature
    Features the circular animated centerpiece on the right side of the news introduction.
--}}
@php
    $newsHeadline = \App\Support\Anniversary::headline();
    $newsTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-news-card" id="annivNewsCard" role="region" aria-label="Sky Link 5-Year Technology Solutions Milestone">
    <div class="anniv-news-card__inner">
        <div class="anniv-news-card__text">
            <div class="anniv-news-card__meta">
                <span class="anniv-news-card__kicker">5-YEAR MILESTONE</span>
                <span class="anniv-news-card__sep" aria-hidden="true">•</span>
                <span class="anniv-news-card__edition">Corporate Technology Edition</span>
            </div>
            <h2 class="anniv-news-card__title">{{ $newsHeadline ?? 'Five Years of Practical Technology Solutions' }}</h2>
            <p class="anniv-news-card__tagline">{{ $newsTagline ?? 'Five years of engineering solutions, advancing technology, and building for what comes next.' }}</p>
        </div>
        <div class="anniv-news-card__centerpiece">
            @include('partials.anniversary.circular-centerpiece', [
                'size' => 'compact',
                'id' => 'annivNewsCenterpiece',
                'showCopy' => false,
            ])
        </div>
    </div>
</div>
