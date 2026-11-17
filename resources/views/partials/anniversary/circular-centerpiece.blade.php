{{--
    Sky Link ICT Solutions 5-Year Anniversary
    Reusable Cinematic Circular Animated Centerpiece

    Props:
    - $size: 'hero' (default, ~300px), 'compact' (~250px), 'inline' (~220px)
    - $id: unique element id (defaults to 'annivCircHero')
    - $headline: optional string
    - $tagline: optional string
    - $showCopy: boolean (default: true if headline or tagline present)
--}}
@php
    $centerId = $id ?? 'annivCirc' . uniqid();
    $centerSize = $size ?? 'hero';
    $primaryHeadline = $headline ?? \App\Support\Anniversary::headline();
    $primaryTagline = $tagline ?? \App\Support\Anniversary::tagline();
    $displayCopy = $showCopy ?? true;
@endphp

<div class="anniv-circ-wrap anniv-circ-wrap--{{ $centerSize }}" id="{{ $centerId }}" aria-label="Sky Link 5-Year Anniversary Celebration">
    {{-- Main Circular Stage --}}
    <div class="anniv-circ-stage" role="button" tabindex="0" title="Click to replay anniversary animation" aria-label="Sky Link 5-Year Anniversary interactive circular centerpiece">
        
        {{-- Deep Midnight Atmosphere Background --}}
        <div class="anniv-circ__atmosphere" aria-hidden="true"></div>

        {{-- Phase 01: Initial Ignition & Circular Perimeter --}}
        <div class="anniv-circ__ignite-core" aria-hidden="true"></div>
        <div class="anniv-circ__pulse-ring" aria-hidden="true"></div>
        <div class="anniv-circ__perimeter" aria-hidden="true"></div>

        {{-- Phase 02: Three Concentric Orbital Rings --}}
        <div class="anniv-circ__ring anniv-circ__ring--outer" aria-hidden="true"></div>
        <div class="anniv-circ__ring anniv-circ__ring--middle" aria-hidden="true"></div>
        <div class="anniv-circ__ring anniv-circ__ring--inner" aria-hidden="true"></div>

        {{-- Orbiting Travelers --}}
        <div class="anniv-circ__traveler anniv-circ__traveler--cyan" aria-hidden="true"></div>
        <div class="anniv-circ__traveler anniv-circ__traveler--violet" aria-hidden="true"></div>

        {{-- Phase 03: Sequential Number Stages 1 -> 2 -> 3 -> 4 --}}
        <div class="anniv-circ__seq" aria-hidden="true">
            <div class="anniv-circ__seq-num anniv-circ__seq-num--1"><span>1</span></div>
            <div class="anniv-circ__seq-num anniv-circ__seq-num--2"><span>2</span></div>
            <div class="anniv-circ__seq-num anniv-circ__seq-num--3"><span>3</span></div>
            <div class="anniv-circ__seq-num anniv-circ__seq-num--4"><span>4</span></div>
        </div>

        {{-- Phase 04: Inward Convergence Energy Core --}}
        <div class="anniv-circ__converge-core" aria-hidden="true"></div>

        {{-- Phase 05: Grand Illuminated Numeral 5 Reveal --}}
        <div class="anniv-circ__numeral-wrap" aria-hidden="true">
            <svg class="anniv-circ__num-svg" viewBox="0 0 100 110" width="116" height="126" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="annivCircTraceGrad-{{ $centerId }}" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#ffffff" />
                        <stop offset="25%" stop-color="#22D3EE" />
                        <stop offset="65%" stop-color="#3B82F6" />
                        <stop offset="100%" stop-color="#8B5CF6" />
                    </linearGradient>
                    <radialGradient id="annivCircBloomGrad-{{ $centerId }}" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#22D3EE" stop-opacity="0.38" />
                        <stop offset="45%" stop-color="#3B82F6" stop-opacity="0.2" />
                        <stop offset="80%" stop-color="#8B5CF6" stop-opacity="0.08" />
                        <stop offset="100%" stop-color="#101A35" stop-opacity="0" />
                    </radialGradient>
                </defs>
                {{-- Background track guide --}}
                <path class="anniv-circ__path-bg" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
                {{-- Dimensional luminous bloom fill --}}
                <path class="anniv-circ__path-fill" fill="url(#annivCircBloomGrad-{{ $centerId }})" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74 Z" />
                {{-- Illuminated glowing trace stroke --}}
                <path class="anniv-circ__path-trace" stroke="url(#annivCircTraceGrad-{{ $centerId }})" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
                {{-- Specular highlight flare --}}
                <circle class="anniv-circ__specular-flare" cx="72" cy="20" r="3.4" fill="#ffffff" />
            </svg>
        </div>

        {{-- Phase 06: Controlled Multi-Color Celebration Energy Burst --}}
        {{-- Concentric Shockwaves --}}
        <div class="anniv-circ__burst-wave anniv-circ__burst-wave--cyan" aria-hidden="true"></div>
        <div class="anniv-circ__burst-wave anniv-circ__burst-wave--blue" aria-hidden="true"></div>

        {{-- 8 Radial Spark Rays (Violet & Magenta) --}}
        <div class="anniv-circ__spark-rays" aria-hidden="true">
            <span class="anniv-circ__ray anniv-circ__ray--1"></span>
            <span class="anniv-circ__ray anniv-circ__ray--2"></span>
            <span class="anniv-circ__ray anniv-circ__ray--3"></span>
            <span class="anniv-circ__ray anniv-circ__ray--4"></span>
            <span class="anniv-circ__ray anniv-circ__ray--5"></span>
            <span class="anniv-circ__ray anniv-circ__ray--6"></span>
            <span class="anniv-circ__ray anniv-circ__ray--7"></span>
            <span class="anniv-circ__ray anniv-circ__ray--8"></span>
        </div>

        {{-- Celebratory Gold Spark Embers --}}
        <div class="anniv-circ__embers" aria-hidden="true">
            <span class="anniv-circ__ember anniv-circ__ember--1"></span>
            <span class="anniv-circ__ember anniv-circ__ember--2"></span>
            <span class="anniv-circ__ember anniv-circ__ember--3"></span>
            <span class="anniv-circ__ember anniv-circ__ember--4"></span>
            <span class="anniv-circ__ember anniv-circ__ember--5"></span>
            <span class="anniv-circ__ember anniv-circ__ember--6"></span>
        </div>

        {{-- Phase 07: Settled Resting Identity Elements --}}
        <div class="anniv-circ__tracer" aria-hidden="true"></div>

        <div class="anniv-circ__label-box" aria-hidden="true">
            <span class="anniv-circ__kicker">5 YEARS</span>
            <span class="anniv-circ__brand">SKY LINK ICT SOLUTIONS</span>
        </div>
    </div>

    {{-- Supporting Corporate Copy (When enabled) --}}
    @if ($displayCopy && ($primaryHeadline || $primaryTagline))
        <div class="anniv-circ__copy-wrap">
            @if ($primaryHeadline)
                <h3 class="anniv-circ__headline">{{ $primaryHeadline }}</h3>
            @endif
            @if ($primaryTagline)
                <p class="anniv-circ__tagline">{{ $primaryTagline }}</p>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
(function() {
    var wrap = document.getElementById('{{ $centerId }}');
    if (!wrap) return;
    var stage = wrap.querySelector('.anniv-circ-stage');
    if (!stage) return;

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
        wrap.classList.add('is-settled');
        return;
    }

    var isRunning = false;
    var timer = null;

    function playSevenPhases() {
        if (isRunning) return;
        isRunning = true;
        wrap.classList.remove('is-settled');
        wrap.classList.remove('is-animating');
        void wrap.offsetWidth; // Force CSS reflow
        wrap.classList.add('is-animating');

        clearTimeout(timer);
        // The 7-phase sequence completes at 6.2s, then gracefully settles into Phase 07
        timer = setTimeout(function() {
            wrap.classList.remove('is-animating');
            wrap.classList.add('is-settled');
            isRunning = false;
        }, 6200);
    }

    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                playSevenPhases();
                obs.disconnect();
            }
        }, { threshold: 0.15 });
        obs.observe(wrap);
    } else {
        playSevenPhases();
    }

    // Interactive replay on click or Enter/Space key
    stage.addEventListener('click', function() {
        playSevenPhases();
    });
    stage.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            playSevenPhases();
        }
    });
})();
</script>
@endpush
