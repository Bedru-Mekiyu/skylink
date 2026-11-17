{{--
    Products Page 5-Year Anniversary Feature
    Connects the 5-year technology solutions milestone to continuous product engineering.
--}}
@php
    $productHeadline = \App\Support\Anniversary::headline();
    $productTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-product-feature" id="annivProductCard" role="region" aria-label="Sky Link 5-Year Technology Solutions Milestone">
    <div class="anniv-product-feature__inner">
        <div class="anniv-product-feature__text">
            <span class="anniv-product-feature__kicker">5 YEARS OF TECHNOLOGY SOLUTIONS</span>
            <h2 class="anniv-product-feature__title">Advancing Practical Hardware &amp; Software Products</h2>
            <p class="anniv-product-feature__desc">{{ $productTagline ?? 'Five years of engineering solutions, advancing technology, and building for what comes next.' }}</p>
        </div>
        <div class="anniv-product-feature__centerpiece">
            @include('partials.anniversary.circular-centerpiece', [
                'size' => 'compact',
                'id' => 'annivProductCenterpiece',
                'showCopy' => false,
            ])
        </div>
    </div>
</div>
