{{-- Sky Link 5-Year Anniversary Homepage Section --}}
@php
    $locale = App\Support\Anniversary::locale();
    $headline = App\Support\Anniversary::headline($locale);
    $tagline = App\Support\Anniversary::tagline($locale);
    $thanks = App\Support\Anniversary::thanks($locale);
    $cta = App\Support\Anniversary::cta($locale);
    $milestones = App\Support\Anniversary::milestones($locale);
    $counters = App\Support\Anniversary::counters($locale);
@endphp

<section class="anniv-home-section" id="anniversary-story" aria-labelledby="annivSectionHeading">
    <div class="anniv-home-section__bg-grid" aria-hidden="true"></div>
    <div class="anniv-home-section__orbit-glow" aria-hidden="true"></div>
    <div class="anniv-home-section__rings" aria-hidden="true"></div>

    <div class="container position-relative" style="z-index: 2;">
        {{-- Section Header --}}
        <div class="text-center">
            <span class="anniv-home-badge-kicker" lang="{{ $locale }}">
                {{ $locale === 'am' ? 'የ5ኛ ዓመት የምሥረታ በዓል' : '5-YEAR ANNIVERSARY' }}
            </span>
            <h2 class="anniv-home-title" id="annivSectionHeading" lang="{{ $locale }}">
                <span class="highlight">{{ $headline ?? ($locale === 'am' ? 'አምስት ዓመታት በስካይሊንክ' : '5 Years of Sky Link') }}</span>
            </h2>
            @if ($tagline)
                <p class="anniv-home-tagline" lang="{{ $locale }}">{{ $tagline }}</p>
            @endif
        </div>

        {{-- Counters Row (if configured) --}}
        @if (!empty($counters))
            <div class="row g-4 justify-content-center anniv-counters-row">
                @foreach ($counters as $cnt)
                    <div class="col-6 col-md-3">
                        <div class="anniv-counter-card">
                            <div class="anniv-counter-number" data-target="{{ $cnt['value'] }}" data-suffix="{{ $cnt['suffix'] }}">0{{ $cnt['suffix'] }}</div>
                            <p class="anniv-counter-label" lang="{{ $locale }}">{{ $cnt['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Milestone Timeline (if configured) --}}
        @if (!empty($milestones))
            <div class="anniv-timeline-wrap">
                <ol class="anniv-timeline" aria-label="5-Year Milestone Journey">
                    @foreach ($milestones as $ms)
                        <li class="anniv-timeline-item">
                            <span class="anniv-timeline-node" aria-hidden="true"></span>
                            <div class="anniv-timeline-content">
                                <div class="anniv-timeline-card">
                                    <span class="anniv-timeline-year">{{ $ms['year'] }}</span>
                                    <h3 class="anniv-timeline-title" lang="{{ $locale }}">{{ $ms['title'] }}</h3>
                                    @if (!empty($ms['text']))
                                        <p class="anniv-timeline-text" lang="{{ $locale }}">{{ $ms['text'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Closing Thank You & Calm CTA --}}
        <div class="anniv-home-closing">
            @if ($thanks)
                <p class="anniv-home-thanks" lang="{{ $locale }}">
                    &ldquo;{{ $thanks }}&rdquo;
                </p>
            @endif
            @if ($cta)
                <a href="{{ route('services') }}#quote-form" class="anniv-btn anniv-btn--glow" id="annivHomeCta">
                    {{ $cta }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-2" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            @endif
        </div>
    </div>
</section>

<script>
(function() {
    var section = document.getElementById('anniversary-story');
    if (!section) return;

    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1. Counter animation
    var counters = section.querySelectorAll('.anniv-counter-number');
    if (counters.length > 0) {
        if (reducedMotion || !('IntersectionObserver' in window)) {
            counters.forEach(function(el) {
                var target = el.getAttribute('data-target') || '0';
                var suffix = el.getAttribute('data-suffix') || '';
                el.textContent = target + suffix;
            });
        } else {
            var counterObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var el = entry.target;
                        var target = parseInt(el.getAttribute('data-target'), 10) || 0;
                        var suffix = el.getAttribute('data-suffix') || '';
                        var duration = 1600;
                        var start = performance.now();

                        function update(now) {
                            var elapsed = now - start;
                            var progress = Math.min(elapsed / duration, 1);
                            // ease-out cubic
                            var ease = 1 - Math.pow(1 - progress, 3);
                            var current = Math.floor(ease * target);
                            el.textContent = current + suffix;
                            if (progress < 1) {
                                requestAnimationFrame(update);
                            } else {
                                el.textContent = target + suffix;
                            }
                        }
                        requestAnimationFrame(update);
                        observer.unobserve(el);
                    }
                });
            }, { threshold: 0.3 });

            counters.forEach(function(el) { counterObserver.observe(el); });
        }
    }
})();
</script>
