{{--
    About Page 5-Year Anniversary Journey Culmination Feature
    Seamlessly integrates the full shared circular centerpiece into the company evolution narrative.
--}}
@php
    $aboutHeadline = \App\Support\Anniversary::headline();
    $aboutTagline = \App\Support\Anniversary::tagline();
@endphp

<div class="anniv-about-showcase" id="annivAboutCard" role="region" aria-label="Sky Link 5-Year Anniversary Celebration">
    <div class="anniv-about-showcase__badge-strip">
        <span class="anniv-about-showcase__pill">2026 Milestone • 5-Year Celebration</span>
    </div>
    <div class="anniv-about-showcase__centerpiece">
        @include('partials.anniversary.circular-centerpiece', [
            'size' => 'hero',
            'id' => 'annivAboutCenterpiece',
            'headline' => $aboutHeadline ?? 'Celebrating Five Years of Sky Link ICT Solutions',
            'tagline' => $aboutTagline ?? 'Join us as we commemorate five years of dedication, progress, and meaningful contributions to the technology industry. We look forward to celebrating this milestone with our team, clients, partners, and community.',
            'showCopy' => true,
        ])
    </div>
</div>
