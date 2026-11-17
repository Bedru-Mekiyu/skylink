{{--
    Homepage Hero Right-Hand Cinematic 5-Year Anniversary Motion Experience
    Open celestial orbital stage with kinetic 01->05 sequence, radial light burst,
    luminous numeral 5, and restrained cosmic idle state.
--}}
@php
    $heroHeadline = \App\Support\Anniversary::headline();
    $heroTagline = \App\Support\Anniversary::tagline();
    $heroFoundedYear = \App\Support\Anniversary::foundedYear();
@endphp

<aside class="anniv-hero-motion" id="annivHeroCard" aria-label="Sky Link 5-Year Anniversary Celebration">
    {{-- Open Celestial Stage Container --}}
    <div class="anniv-motion-stage" id="annivMotionStage" role="button" tabindex="0" title="Click to replay motion sequence" aria-label="5-Year anniversary kinetic orbital centerpiece">
        {{-- Ambient Core & Atmospheric Glow --}}
        <div class="anniv-motion__ambient-glow" aria-hidden="true"></div>
        <div class="anniv-motion__grid" aria-hidden="true"></div>

        {{-- Astronomical Coordinates / Compass Markers --}}
        <div class="anniv-motion__ticks" aria-hidden="true">
            <span class="anniv-motion__tick anniv-motion__tick--n">INNOVATION</span>
            <span class="anniv-motion__tick anniv-motion__tick--e">TRUST</span>
            <span class="anniv-motion__tick anniv-motion__tick--s">GROWTH</span>
            <span class="anniv-motion__tick anniv-motion__tick--w">TECHNOLOGY</span>
        </div>

        {{-- Concentric Orbital Rings --}}
        <div class="anniv-motion__ring anniv-motion__ring--outer" aria-hidden="true"></div>
        <div class="anniv-motion__ring anniv-motion__ring--middle" aria-hidden="true"></div>
        <div class="anniv-motion__ring anniv-motion__ring--inner" aria-hidden="true"></div>

        {{-- Idle State Photon Tracer Node --}}
        <div class="anniv-motion__tracer" aria-hidden="true"></div>

        {{-- Kinetic Sequential Year Nodes (01 -> 02 -> 03 -> 04 -> 05) --}}
        <div class="anniv-motion__nodes" aria-hidden="true">
            <div class="anniv-motion__node anniv-motion__node--1" data-val="01">
                <span class="anniv-motion__node-core"></span>
                <span class="anniv-motion__node-num">01</span>
            </div>
            <div class="anniv-motion__node anniv-motion__node--2" data-val="02">
                <span class="anniv-motion__node-core"></span>
                <span class="anniv-motion__node-num">02</span>
            </div>
            <div class="anniv-motion__node anniv-motion__node--3" data-val="03">
                <span class="anniv-motion__node-core"></span>
                <span class="anniv-motion__node-num">03</span>
            </div>
            <div class="anniv-motion__node anniv-motion__node--4" data-val="04">
                <span class="anniv-motion__node-core"></span>
                <span class="anniv-motion__node-num">04</span>
            </div>
            <div class="anniv-motion__node anniv-motion__node--5" data-val="05">
                <span class="anniv-motion__node-core"></span>
                <span class="anniv-motion__node-num">05</span>
            </div>
        </div>

        {{-- Controlled Radial Light Burst Reveal Flare --}}
        <div class="anniv-motion__burst" aria-hidden="true"></div>

        {{-- Grand Luminous Numeral 5 --}}
        <div class="anniv-motion__numeral-wrap" aria-hidden="true">
            <svg class="anniv-motion__num-svg" viewBox="0 0 100 110" width="120" height="130" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="cinematic5TraceGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#ffffff" />
                        <stop offset="30%" stop-color="#38bdf8" />
                        <stop offset="70%" stop-color="#0ea5e9" />
                        <stop offset="100%" stop-color="#2563eb" />
                    </linearGradient>
                    <radialGradient id="cinematic5BloomGrad" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.36" />
                        <stop offset="50%" stop-color="#2563eb" stop-opacity="0.16" />
                        <stop offset="85%" stop-color="#1e40af" stop-opacity="0.06" />
                        <stop offset="100%" stop-color="#0f172a" stop-opacity="0" />
                    </radialGradient>
                </defs>
                {{-- Background track guide --}}
                <path class="anniv-motion__path-bg" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
                {{-- Luminous bloom fill --}}
                <path class="anniv-motion__path-fill" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74 Z" />
                {{-- Illuminated glowing trace stroke --}}
                <path class="anniv-motion__path-trace" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
                {{-- Specular flare node at head of stroke --}}
                <circle class="anniv-motion__specular-flare" cx="72" cy="20" r="3.4" fill="#ffffff" filter="drop-shadow(0 0 8px #38bdf8)" />
            </svg>
        </div>
    </div>

    {{-- Editorial Anniversary Message & Staggered Reveal Content --}}
    <div class="anniv-motion__content">
        <div class="anniv-motion__badge-row">
            <span class="anniv-motion__kicker">5 YEARS</span>
            @if ($heroFoundedYear)
                <span class="anniv-motion__years">{{ $heroFoundedYear }} — 2026</span>
            @endif
        </div>

        <p class="anniv-motion__sublabel">OF SKY LINK ICT SOLUTIONS</p>

        @if ($heroHeadline)
            <h2 class="anniv-motion__headline">{{ $heroHeadline }}</h2>
        @endif

        @if ($heroTagline)
            <p class="anniv-motion__tagline">{{ $heroTagline }}</p>
        @endif

        <div class="anniv-motion__actions">
            <a href="#anniversary-story" class="anniv-motion__cta">
                <span>Explore Our Journey</span>
                <i class="fas fa-arrow-down" aria-hidden="true"></i>
            </a>
            <button type="button" class="anniv-motion__overview-trigger" onclick="document.getElementById('annivBadgeBtn')?.click()" aria-label="Open celebration overview panel">
                <i class="fas fa-sparkles me-1" aria-hidden="true"></i> Overview
            </button>
        </div>
    </div>
</aside>

@push('scripts')
<script>
(function() {
    var stage = document.getElementById('annivMotionStage');
    var wrapper = document.getElementById('annivHeroCard');
    if (!stage || !wrapper) return;

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
        wrapper.classList.add('is-settled');
        return;
    }

    var isRunning = false;

    function playSequence() {
        if (isRunning) return;
        isRunning = true;
        wrapper.classList.remove('is-settled');
        wrapper.classList.remove('is-animating');
        void wrapper.offsetWidth; // Force reflow
        wrapper.classList.add('is-animating');

        // Total sequence runs 5.6s, then settles into perpetual restrained idle state
        setTimeout(function() {
            wrapper.classList.remove('is-animating');
            wrapper.classList.add('is-settled');
            isRunning = false;
        }, 5600);
    }

    // Auto-play when hero enters viewport
    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                playSequence();
                obs.disconnect();
            }
        }, { threshold: 0.2 });
        obs.observe(wrapper);
    } else {
        playSequence();
    }

    // Replay on stage click or keyboard Enter/Space
    stage.addEventListener('click', function() {
        playSequence();
    });
    stage.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            playSequence();
        }
    });
})();
</script>
@endpush
