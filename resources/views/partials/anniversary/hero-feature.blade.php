{{--
    Homepage Hero Right-Hand Cinematic 5-Year Anniversary Circular Centerpiece
    Features the 7-phase circular motion story: ignition, orbit formation,
    number sequence 1-4, convergence, numeral 5 reveal, multi-color celebration explosion,
    and elegant resting state.
--}}
<aside class="anniv-hero-centerpiece" id="annivHeroCard" aria-label="Sky Link 5-Year Anniversary Celebration">
    @include('partials.anniversary.circular-centerpiece', [
        'size' => 'hero',
        'id' => 'annivHeroCenterpiece',
        'headline' => \App\Support\Anniversary::headline(),
        'tagline' => \App\Support\Anniversary::tagline(),
        'showCopy' => true,
    ])
</aside>
