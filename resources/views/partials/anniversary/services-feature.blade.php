{{--
    Services Page 5-Year Anniversary Feature
    Seamlessly integrates the shared circular centerpiece alongside the services introduction.
--}}
@php
    $servicesHeadline = \App\Support\Anniversary::headline();
    $servicesTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-services-feature" id="annivServicesCard" role="region" aria-label="Sky Link 5-Year Enterprise Services Milestone">
    <div class="anniv-services-feature__inner">
        <div class="anniv-services-feature__text">
            <span class="anniv-services-feature__kicker">5 YEARS OF EXCELLENCE</span>
            <h2 class="anniv-services-feature__title">Empowering Enterprise &amp; Public Sector IT Solutions</h2>
            <p class="anniv-services-feature__desc">{{ $servicesTagline ?? 'Celebrating five years of delivering innovative digital solutions, mission-critical infrastructure, and dedicated technology support across Ethiopia.' }}</p>
        </div>
        <div class="anniv-services-feature__centerpiece">
            @include('partials.anniversary.circular-centerpiece', [
                'size' => 'compact',
                'id' => 'annivServicesCenterpiece',
                'showCopy' => false,
            ])
        </div>
    </div>
</div>
