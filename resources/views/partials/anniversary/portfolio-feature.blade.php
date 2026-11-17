{{--
    Portfolio & Projects Page 5-Year Anniversary Feature
    Seamlessly integrates the shared circular centerpiece alongside the portfolio introduction.
--}}
@php
    $portfolioHeadline = \App\Support\Anniversary::headline();
    $portfolioTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-portfolio-feature" id="annivPortfolioCard" role="region" aria-label="Sky Link 5-Year Technology Solutions Deliverables">
    <div class="anniv-portfolio-feature__inner">
        <div class="anniv-portfolio-feature__text">
            <span class="anniv-portfolio-feature__kicker">5 YEARS OF IMPACT</span>
            <h2 class="anniv-portfolio-feature__title">Transformative Technology Projects Delivered</h2>
            <p class="anniv-portfolio-feature__desc">{{ $portfolioTagline ?? 'Celebrating five years of successful implementations, enterprise systems, and digital transformations delivered across Ethiopia.' }}</p>
        </div>
        <div class="anniv-portfolio-feature__centerpiece">
            @include('partials.anniversary.circular-centerpiece', [
                'size' => 'compact',
                'id' => 'annivPortfolioCenterpiece',
                'showCopy' => false,
            ])
        </div>
    </div>
</div>
