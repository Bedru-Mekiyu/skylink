{{-- Sky Link 5-Year Anniversary Cinematic Intro --}}
@php
    $locale = App\Support\Anniversary::locale();
    $headline = App\Support\Anniversary::headline($locale);
    $tagline = App\Support\Anniversary::tagline($locale);
@endphp

<div class="anniv-intro" id="annivIntroOverlay" role="dialog" aria-modal="true" aria-label="5 Years of Sky Link Introduction" style="display: none;">
    <div class="anniv-intro__grid" aria-hidden="true"></div>

    <div class="anniv-intro__particles" aria-hidden="true">
        <span class="anniv-intro__particle"></span>
        <span class="anniv-intro__particle"></span>
        <span class="anniv-intro__particle"></span>
        <span class="anniv-intro__particle"></span>
    </div>

    {{-- Visible Skip button --}}
    <button type="button" class="anniv-intro__skip" id="annivIntroSkip" aria-label="{{ $locale === 'am' ? 'ማለፊያ' : 'Skip introduction' }}">
        {{ $locale === 'am' ? 'እለፍ' : 'Skip' }} <span aria-hidden="true">&times;</span>
    </button>

    {{-- Center Cinematic Stage --}}
    <div class="anniv-intro__center">
        <div class="anniv-intro__ring anniv-intro__ring--1" aria-hidden="true"></div>
        <div class="anniv-intro__ring anniv-intro__ring--2" aria-hidden="true"></div>

        {{-- SVG 5 Numeral with Stroke Animation --}}
        <svg class="anniv-intro__svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <defs>
                <linearGradient id="annivTraceGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="40%" stop-color="#38bdf8" />
                    <stop offset="100%" stop-color="#2563eb" />
                </linearGradient>
                <radialGradient id="annivFillGrad" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#38bdf8" />
                    <stop offset="100%" stop-color="#1e40af" />
                </radialGradient>
            </defs>
            {{-- Background guide track --}}
            <path class="anniv-intro__path-bg" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
            {{-- Inner bloom fill --}}
            <path class="anniv-intro__path-fill" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74 Z" />
            {{-- Animated glowing trace --}}
            <path class="anniv-intro__path-trace" d="M72 20 H34 L28 48 C32 44 40 42 50 42 C65 42 76 52 76 68 C76 82 64 92 48 92 C34 92 24 84 22 74" />
        </svg>

        <p class="anniv-intro__years-kicker" lang="{{ $locale }}">
            {{ $locale === 'am' ? 'አምስት ዓመታት' : '5 YEARS' }}
        </p>
        <p class="anniv-intro__brand" lang="{{ $locale }}">
            {{ $locale === 'am' ? 'በስካይሊንክ አይሲቲ ሶሉሽንስ' : 'of Sky Link ICT Solutions' }}
        </p>
        @if ($tagline)
            <p class="anniv-intro__tagline" lang="{{ $locale }}">
                {{ $tagline }}
            </p>
        @endif
    </div>
</div>

<script>
(function() {
    var overlay = document.getElementById('annivIntroOverlay');
    var skipBtn = document.getElementById('annivIntroSkip');
    if (!overlay || !skipBtn) return;

    // Strict Gating Checks:
    // 1. Reduced motion preference
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) return;

    // 2. Save-Data header / preference
    var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (conn && conn.saveData) return;

    // 3. Network speed: 2g or slow-2g
    if (conn && (conn.effectiveType === 'slow-2g' || conn.effectiveType === '2g')) return;

    // 4. Automated bots or crawlers
    if (navigator.webdriver || /bot|crawler|spider|crawling/i.test(navigator.userAgent)) return;

    // 5. Plays once per visitor for the campaign period (localStorage with cookie fallback)
    var storageKey = 'skylink_anniv_5y_intro';
    var hasSeen = false;
    try {
        if (window.localStorage && window.localStorage.getItem(storageKey)) {
            hasSeen = true;
        } else if (document.cookie.indexOf(storageKey + '=1') !== -1) {
            hasSeen = true;
        }
    } catch (e) {
        hasSeen = false;
    }

    if (hasSeen) return;

    // Record that visitor has seen the intro
    try {
        if (window.localStorage) {
            window.localStorage.setItem(storageKey, '1');
        }
        // Set cookie expiring after campaign (e.g. 30 days)
        var d = new Date();
        d.setTime(d.getTime() + (30 * 24 * 60 * 60 * 1000));
        document.cookie = storageKey + '=1; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
    } catch (e) {}

    // Activate the intro smoothly
    overlay.style.display = 'flex';
    document.body.classList.add('anniv-intro-playing');
    skipBtn.focus();

    var dismissed = false;
    function dismiss() {
        if (dismissed) return;
        dismissed = true;
        document.body.classList.remove('anniv-intro-playing');
        overlay.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        overlay.style.opacity = '0';
        overlay.style.transform = 'scale(1.02)';
        setTimeout(function() {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 400);
    }

    skipBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dismiss();
    });

    overlay.addEventListener('click', function() {
        dismiss();
    });

    document.addEventListener('keydown', function(e) {
        if (!dismissed && e.key === 'Escape') {
            dismiss();
        }
    });

    // Auto dismiss after 5.5s animation completes
    setTimeout(dismiss, 5500);

    // Hard failsafe guaranteed cleanup at 8 seconds
    setTimeout(dismiss, 8000);
})();
</script>
