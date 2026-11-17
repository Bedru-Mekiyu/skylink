{{-- Sky Link 5-Year Anniversary Badge & Celebration Modal --}}
@php
    $locale = App\Support\Anniversary::locale();
    $headline = App\Support\Anniversary::headline($locale);
    $tagline = App\Support\Anniversary::tagline($locale);
    $thanks = App\Support\Anniversary::thanks($locale);
    $cta = App\Support\Anniversary::cta($locale);
    $foundedYear = App\Support\Anniversary::foundedYear();
    $milestones = App\Support\Anniversary::milestones($locale);
@endphp

{{-- 1. Refined Navbar Emblem --}}
<button type="button"
        class="anniv-badge"
        id="annivBadgeBtn"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="annivCelebrationModal"
        aria-label="{{ $locale === 'am' ? 'የስካይሊንክ 5 ዓመታት - ዝርዝሩን ይመልከቱ' : '5 Years of Sky Link - View celebration details' }}">
    <span class="anniv-badge__glow" aria-hidden="true"></span>
    <span class="anniv-badge__sheen" aria-hidden="true"></span>
    <span class="anniv-badge__num-wrap" aria-hidden="true">
        <span class="anniv-badge__num">5</span>
    </span>
    <span class="anniv-badge__copy">
        <span class="anniv-badge__label" lang="{{ $locale }}">{{ $locale === 'am' ? 'ዓመታት' : 'YEARS' }}</span>
        <span class="anniv-badge__sub" lang="{{ $locale }}">{{ $locale === 'am' ? 'ስካይሊንክ' : 'SKY LINK' }}</span>
    </span>
</button>

{{-- 2. Celebration Modal Panel --}}
<div class="anniv-modal" id="annivCelebrationModal" role="dialog" aria-modal="true" aria-labelledby="annivModalTitle" hidden>
    <div class="anniv-modal__backdrop" id="annivModalBackdrop" tabindex="-1"></div>
    <div class="anniv-modal__dialog" role="document">
        <button type="button" class="anniv-modal__close" id="annivModalClose" aria-label="{{ $locale === 'am' ? 'መዝጊያ' : 'Close celebration details' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div class="text-center">
            <div class="anniv-modal__emblem" aria-hidden="true">
                <div class="anniv-modal__emblem-rings"></div>
                <span class="anniv-modal__emblem-num">5</span>
            </div>
            <p class="anniv-modal__kicker" lang="{{ $locale }}">
                {{ $locale === 'am' ? 'የ5ኛ ዓመት የምሥረታ በዓል' : '5-YEAR ANNIVERSARY' }}
            </p>
            <h2 class="anniv-modal__title" id="annivModalTitle" lang="{{ $locale }}">
                {{ $headline ?? ($locale === 'am' ? 'አምስት ዓመታት በምህንድስና እና ቴክኖሎጂ' : 'Five Years of Practical Technology Solutions') }}
            </h2>
            @if ($tagline)
                <p class="anniv-modal__tagline" lang="{{ $locale }}">{{ $tagline }}</p>
            @endif
            @if ($foundedYear)
                <span class="anniv-modal__founded">
                    {{ $locale === 'am' ? 'ከ ' . $foundedYear . ' ጀምሮ' : 'Since ' . $foundedYear }}
                </span>
            @endif
        </div>

        {{-- Mini Timeline (milestones from config) --}}
        @if (!empty($milestones))
            <div class="anniv-modal__timeline">
                <h3 class="anniv-modal__section-title" lang="{{ $locale }}">
                    {{ $locale === 'am' ? 'የዕድገት ጉዟችን' : 'Our Milestones' }}
                </h3>
                <ol class="anniv-mini-timeline" aria-label="5-Year Milestones">
                    @foreach ($milestones as $ms)
                        <li class="anniv-mini-timeline__item">
                            <span class="anniv-mini-timeline__dot" aria-hidden="true"></span>
                            <div class="anniv-mini-timeline__content">
                                <span class="anniv-mini-timeline__year">{{ $ms['year'] }}</span>
                                <h4 class="anniv-mini-timeline__title" lang="{{ $locale }}">{{ $ms['title'] }}</h4>
                                @if (!empty($ms['text']))
                                    <p class="anniv-mini-timeline__text" lang="{{ $locale }}">{{ $ms['text'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Thank-you message & CTA --}}
        <div class="anniv-modal__footer text-center">
            @if ($thanks)
                <p class="anniv-modal__thanks" lang="{{ $locale }}">
                    &ldquo;{{ $thanks }}&rdquo;
                </p>
            @endif
            @if ($cta)
                <a href="{{ route('services') }}#quote-form" class="anniv-btn anniv-btn--glow" id="annivModalCta">
                    {{ $cta }}
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-2" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            @endif
        </div>
    </div>
</div>

<script>
(function() {
    var btn = document.getElementById('annivBadgeBtn');
    var modal = document.getElementById('annivCelebrationModal');
    var closeBtn = document.getElementById('annivModalClose');
    var backdrop = document.getElementById('annivModalBackdrop');
    if (!btn || !modal || !closeBtn || !backdrop) return;

    function openModal() {
        modal.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('anniv-modal-open');
        closeBtn.focus();
    }

    function closeModal() {
        modal.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('anniv-modal-open');
        btn.focus();
    }

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        openModal();
    });

    closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeModal();
    });

    backdrop.addEventListener('click', function() {
        closeModal();
    });

    document.addEventListener('keydown', function(e) {
        if (!modal.hidden && e.key === 'Escape') {
            e.preventDefault();
            closeModal();
        }
    });

    // Trap focus inside modal
    modal.addEventListener('keydown', function(e) {
        if (modal.hidden || e.key !== 'Tab') return;
        var focusable = modal.querySelectorAll('button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable.length === 0) return;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });
})();
</script>
