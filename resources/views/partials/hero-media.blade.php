{{--
    Homepage hero background: poster -> video -> gradient -> vignette -> grain.
    Included at the end of <section class="hero">. No visible controls of any
    kind (by request); reduced motion, Save-Data and slow-2g/2g get the poster.

    Files are produced by build-video.sh into public/assets/videos/hero/.
    Anything missing is skipped: with no video files only the poster renders
    (no <video>, no requests), and with no poster.jpg the original still
    image assets/images/27075.jpg is used.
--}}
@php
    $heroMediaDir = 'assets/videos/hero/';
    // Versioned URL for a built file, or null when it does not exist yet.
    $heroMediaUrl = static function (string $file) use ($heroMediaDir): ?string {
        $path = public_path($heroMediaDir.$file);

        return is_file($path) ? asset($heroMediaDir.$file).'?v='.filemtime($path) : null;
    };

    // Admin > Homepage Video: an uploaded set replaces the built-in files; none chosen -> the defaults below.
    $heroCustom = \App\Support\HeroVideo::current();

    $heroBuiltPoster = $heroMediaUrl('poster.jpg');
    $heroPoster = ($heroCustom['poster'] ?? null) ?? $heroBuiltPoster ?? asset('assets/images/27075.jpg');
    // The built-in still is the CEO portrait (tools/local/build-ceo-hero.php): WebP + a 900 px phone version.
    // An admin-uploaded poster is a single file and is used as it is.
    $heroCeoStill = ! ($heroCustom['poster'] ?? null) && $heroBuiltPoster;
    $heroPosterWebp = $heroCeoStill ? $heroMediaUrl('poster.webp') : null;
    $heroPosterMobile = $heroCeoStill ? $heroMediaUrl('poster-mobile.jpg') : null;
    $heroPosterMobileWebp = $heroCeoStill ? $heroMediaUrl('poster-mobile.webp') : null;
    $heroDesktopWebm = $heroCustom ? ($heroCustom['desktop_webm'] ?? null) : $heroMediaUrl('hero-desktop.webm');
    $heroDesktopMp4 = $heroCustom ? ($heroCustom['desktop_mp4'] ?? null) : $heroMediaUrl('hero-desktop.mp4');
    $heroMobileMp4 = $heroCustom ? ($heroCustom['mobile_mp4'] ?? null) : $heroMediaUrl('hero-mobile.mp4');
    $heroHasVideo = $heroDesktopMp4 || $heroDesktopWebm || $heroMobileMp4;
@endphp

@push('styles')
    @if ($heroPosterWebp && $heroPosterMobileWebp)
        <link rel="preload" as="image" type="image/webp" href="{{ $heroPosterMobileWebp }}" media="(max-width: 767.98px), (max-aspect-ratio: 1/1)" fetchpriority="high">
        <link rel="preload" as="image" type="image/webp" href="{{ $heroPosterWebp }}" media="(min-width: 768px) and (min-aspect-ratio: 101/100)" fetchpriority="high">
    @else
        <link rel="preload" as="image" href="{{ $heroPoster }}" fetchpriority="high">
    @endif
    <script>
        // Hide the hero copy until the word-by-word reveal is ready (no flash of
        // unanimated text). Failsafe: always show it after 3 s.
        (function (root) {
            if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            root.classList.add('hero-reveal-pending');
            setTimeout(function () { root.classList.remove('hero-reveal-pending'); }, 3000);
        })(document.documentElement);
    </script>
    <style>
        #home.hero {
            isolation: isolate; /* keep the media layers' z-index inside the hero */
            background-color: #06122b;
            @if ($heroBuiltPoster)
            /* poster.jpg replaces the 27075.jpg fallback. The base ends in the
               stats-section blue so the fractional bottom edge shows no dark seam. */
            background-image: linear-gradient(to bottom, #06122b 80%, var(--primary-blue, rgb(26, 111, 176)));
            @endif
        }

        .hero-media {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
            --hero-scroll: 0;
        }

        /* Poster + video scale up and darken together as the hero scrolls out.
           (A separate dark overlay layer left a 1px seam at the hero's
           fractional bottom edge, so the darkening is a filter here.) */
        .hero-media__frame {
            position: absolute;
            inset: 0;
            transform: scale(calc(1 + var(--hero-scroll) * 0.08));
            transform-origin: 50% 40%;
            filter: brightness(calc(1 - var(--hero-scroll) * 0.6));
            will-change: transform, filter;
        }

        .hero-media__poster,
        .hero-media__video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Two stacked copies of the video (double-buffered loop). Only the
           first appearance fades in from the poster; after that the poster is
           always covered by at least one fully opaque video. */
        .hero-media__video {
            opacity: 0;
            transition: opacity 0.9s ease;
        }

        .hero-media.is-playing .hero-media__video.is-shown {
            opacity: 1;
        }

        /* The copy fading in at the loop point sits above the outgoing one. */
        .hero-media__video.is-front {
            z-index: 1;
        }

        .hero-media.is-failed .hero-media__video {
            display: none;
        }

        /* Readability: deep navy on the left where the headline sits. */
        .hero-media__gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg,
                rgba(4, 12, 34, 0.88) 0%,
                rgba(4, 12, 34, 0.72) 38%,
                rgba(4, 12, 34, 0.35) 68%,
                rgba(4, 12, 34, 0.15) 100%);
        }

        .hero-media__vignette {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 60% 45%, rgba(4, 12, 34, 0) 45%, rgba(4, 12, 34, 0.55) 100%);
        }

        /* Static film grain (does not animate). */
        .hero-media__grain {
            position: absolute;
            inset: -50%;
            opacity: 0.06;
            mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* Blend into the stats section below (same color variable). */
        .hero-media__fade {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 120px;
            background: linear-gradient(to bottom, rgba(26, 111, 176, 0), var(--primary-blue, rgb(26, 111, 176)));
        }

        /* No controls or browser overlays on the background video: the iOS
           Safari start-playback button (shown when autoplay is blocked) and
           Chrome-for-Android's overlay play button. */
        .hero-media__video::-webkit-media-controls,
        .hero-media__video::-webkit-media-controls-panel,
        .hero-media__video::-webkit-media-controls-overlay-play-button,
        .hero-media__video::-webkit-media-controls-start-playback-button,
        .hero-media__video::-webkit-media-controls-enclosure {
            display: none !important;
            -webkit-appearance: none;
            opacity: 0 !important;
        }

        /* Word-by-word rise of the existing headline and paragraph. */
        .hero-reveal-pending #home .hero-content > * {
            opacity: 0;
        }

        .hero-word {
            display: inline-block;
            opacity: 0;
            transform: translateY(0.55em);
            animation: heroRiseIn 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) forwards;
            animation-delay: var(--hero-delay, 0ms);
        }

        .hero-rise {
            opacity: 0;
            animation: heroRiseIn 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) forwards;
            animation-delay: var(--hero-delay, 0ms);
        }

        @keyframes heroRiseIn {
            to {
                opacity: 1;
                transform: none;
            }
        }

        @media (max-width: 767.98px) {
            .hero-media__gradient {
                /* Copy spans the full width on phones. */
                background: linear-gradient(180deg, rgba(4, 12, 34, 0.62) 0%, rgba(4, 12, 34, 0.78) 100%);
            }

            .hero-media__grain {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-media__frame {
                transform: none;
            }

            .hero-media__video {
                transition: none;
            }

            .hero-word,
            .hero-rise {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }
    </style>
@endpush

<div class="hero-media" data-hero-media aria-hidden="true">
    <div class="hero-media__frame">
        <picture>
            @if ($heroPosterMobileWebp)<source media="(max-width: 767.98px), (max-aspect-ratio: 1/1)" srcset="{{ $heroPosterMobileWebp }}" type="image/webp">@endif
            @if ($heroPosterMobile)<source media="(max-width: 767.98px), (max-aspect-ratio: 1/1)" srcset="{{ $heroPosterMobile }}" type="image/jpeg">@endif
            @if ($heroPosterWebp)<source srcset="{{ $heroPosterWebp }}" type="image/webp">@endif
            <img class="hero-media__poster" src="{{ $heroPoster }}" alt="{{ $heroCeoStill ? config('team.ceo.name').', CEO of Sky Link Technologies' : '' }}" fetchpriority="high" decoding="async">
        </picture>
        @if ($heroHasVideo)
            {{-- Sources are attached by JS after the viewport / motion / data-saver checks. --}}
            {{-- A second copy is cloned from this one by JS for the seamless loop. --}}
            <video class="hero-media__video is-shown"
                   autoplay muted loop playsinline preload="metadata"
                   poster="{{ $heroPoster }}"
                   disablepictureinpicture disableremoteplayback tabindex="-1"
                   controlslist="nodownload nofullscreen noremoteplayback noplaybackrate"
                   data-desktop-webm="{{ $heroDesktopWebm }}"
                   data-desktop-mp4="{{ $heroDesktopMp4 }}"
                   data-mobile-mp4="{{ $heroMobileMp4 }}"></video>
        @endif
    </div>
    <div class="hero-media__gradient"></div>
    <div class="hero-media__vignette"></div>
    <div class="hero-media__grain"></div>
    <div class="hero-media__fade"></div>
</div>

@push('scripts')
    <script>
        (function () {
            // The layout renders the scripts stack twice; run once.
            if (window.__skyHeroMedia) return;
            window.__skyHeroMedia = true;

            var MOBILE_QUERY = '(max-width: 767.98px)';
            // Poster only on these. 3g and faster play video (Chrome reports many
            // ordinary connections as 3g).
            var SLOW_CONNECTIONS = ['slow-2g', '2g'];
            var LOOP_LEAD = 0.5;      // s before the end: start the standby copy and crossfade
            var STALL_LIMIT = 1500;   // ms without progress while it should play -> heal
            var WARM_AHEAD = 3;       // s before the crossfade: warm up the standby's decoder

            function onReady(fn) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    fn();
                }
            }

            function prefersReducedMotion() {
                return window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
            }

            // Split an element's plain text into staggered word spans. Copy is unchanged.
            // After the animation the original text node is restored, so screen
            // readers don't read the headline one word at a time.
            function splitWords(el, startMs, stepMs) {
                var original = el.textContent;
                var parts = original.trim().split(/(\s+)/);
                var delay = startMs;
                var lastSpan = null;
                el.textContent = '';
                parts.forEach(function (part) {
                    if (/^\s+$/.test(part)) {
                        el.appendChild(document.createTextNode(' '));
                        return;
                    }
                    var span = document.createElement('span');
                    span.className = 'hero-word';
                    span.style.setProperty('--hero-delay', delay + 'ms');
                    span.textContent = part;
                    el.appendChild(span);
                    lastSpan = span;
                    delay += stepMs;
                });
                if (lastSpan) {
                    lastSpan.addEventListener('animationend', function () {
                        el.textContent = original;
                    }, { once: true });
                }
                return delay;
            }

            function initReveal(hero) {
                var root = document.documentElement;
                // Skipped if the failsafe already showed the text, or for reduced motion.
                if (!root.classList.contains('hero-reveal-pending') || prefersReducedMotion()) {
                    root.classList.remove('hero-reveal-pending');
                    return;
                }
                var content = hero.querySelector('.hero-content');
                if (!content) {
                    root.classList.remove('hero-reveal-pending');
                    return;
                }
                var t = 150;
                var heading = content.querySelector('h1');
                var text = content.querySelector('p');
                if (heading) t = splitWords(heading, t, 70);
                if (text) t = splitWords(text, t + 100, 22);
                content.querySelectorAll(':scope > a').forEach(function (link) {
                    link.classList.add('hero-rise');
                    link.style.setProperty('--hero-delay', (t + 100) + 'ms');
                });
                root.classList.remove('hero-reveal-pending');
            }

            function initParallax(hero, media) {
                if (prefersReducedMotion()) return { setActive: function () {} };
                var active = true;
                var queued = false;

                function update() {
                    queued = false;
                    var rect = hero.getBoundingClientRect();
                    var progress = rect.height ? Math.min(Math.max(-rect.top / rect.height, 0), 1) : 0;
                    media.style.setProperty('--hero-scroll', progress.toFixed(3));
                }

                // Passive listener that only schedules one rAF per frame.
                window.addEventListener('scroll', function () {
                    if (!active || queued) return;
                    queued = true;
                    requestAnimationFrame(update);
                }, { passive: true });
                update();

                return {
                    setActive: function (value) {
                        active = value;
                        if (value) update();
                    }
                };
            }

            function initVideo(hero, media, video, onVisibilityChange) {
                var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                var saveData = !!(conn && conn.saveData);
                var slow = !!(conn && SLOW_CONNECTIONS.indexOf(conn.effectiveType) !== -1);

                // Poster only: no sources are attached, so nothing downloads.
                if (prefersReducedMotion() || saveData || slow) return;

                var isMobile = window.matchMedia && matchMedia(MOBILE_QUERY).matches;
                var d = video.dataset;
                var sources = isMobile
                    ? [[d.mobileMp4 || d.desktopMp4, 'video/mp4']]
                    : [[d.desktopWebm, 'video/webm'], [d.desktopMp4 || d.mobileMp4, 'video/mp4']];
                sources = sources.filter(function (s) { return !!s[0]; });
                if (!sources.length) return;

                /*
                 * Seamless loop engine (double-buffered).
                 * Two stacked copies of the same file: `active` is visible, `standby`
                 * waits paused at 0 with the whole file buffered. LOOP_LEAD seconds
                 * before `active` ends, the standby starts from 0 and fades in over
                 * the time that is left (the file's last 0.4 s is already a crossfade
                 * into its opening shot). Then the old copy is paused, rewound from
                 * memory and becomes the standby. Nothing seeks at the visible join,
                 * and the standby's start-up delay is hidden behind the active copy.
                 * The native `loop` attribute stays on as a safety net.
                 */
                var state = { failed: false, blocked: false, inView: true, swapping: false, healing: false };
                var active = video;
                var standby = null;
                var incoming = null;
                var timer = 0;
                var frame = 0;
                var lastTime = -1;
                var lastMove = 0;
                var heals = 0;

                function shouldPlay() {
                    return !state.failed && !state.blocked && state.inView && !document.hidden;
                }

                function bufferedToEnd(el) {
                    var b = el.buffered;
                    return b.length > 0 && isFinite(el.duration) && b.end(b.length - 1) >= el.duration - 0.25;
                }

                function clearTimers() {
                    clearTimeout(timer);
                    cancelAnimationFrame(frame);
                    timer = 0;
                    frame = 0;
                }

                // Real load error with nothing to fall back to: show the poster.
                function fail() {
                    if (state.failed) return;
                    state.failed = true;
                    clearTimers();
                    media.classList.remove('is-playing');
                    media.classList.add('is-failed');
                    [active, standby, incoming].forEach(function (el) { if (el) el.pause(); });
                }

                var standbyFailures = 0;

                function onElementError(el) {
                    // A failed second copy: fall back to the native loop; give up after 2 tries.
                    if (el === standby) { standby = null; standbyFailures++; el.remove(); return; }
                    if (el === incoming) { abortSwap(); standby = null; standbyFailures++; el.remove(); return; }
                    if (el !== active) return;
                    if (standby && standby.readyState >= 2) {
                        // Promote the healthy copy instantly.
                        var broken = active;
                        active = standby;
                        standby = null;
                        active.style.transition = 'none';
                        active.classList.add('is-shown');
                        broken.remove();
                        sync();
                        return;
                    }
                    fail();
                }

                function attach(el) {
                    sources.forEach(function (s, index) {
                        var source = document.createElement('source');
                        source.src = s[0];
                        source.type = s[1];
                        if (index === sources.length - 1) {
                            source.addEventListener('error', function () { onElementError(el); }); // every source failed
                        }
                        el.appendChild(source);
                    });
                    el.muted = true;
                    el.defaultMuted = true;
                    el.addEventListener('error', function () { onElementError(el); });
                    el.addEventListener('playing', function () {
                        if (el !== active && el !== incoming) return;
                        media.classList.add('is-playing');
                        el.preload = 'auto'; // buffer the whole file so restarts come from memory
                    });
                    el.addEventListener('progress', maybeMakeStandby);
                }

                // Autoplay refused (e.g. iOS Low Power Mode): no play button. Keep
                // the poster and retry on the first touch, click, key or scroll
                // anywhere on the page (re-armed if that attempt is refused too).
                var gestureEvents = ['pointerdown', 'touchstart', 'click', 'keydown', 'scroll'];
                function onGesture() {
                    gestureEvents.forEach(function (type) { window.removeEventListener(type, onGesture, true); });
                    if (!state.blocked) return;
                    state.blocked = false;
                    sync();
                }
                function onAutoplayBlocked() {
                    if (state.blocked) return;
                    state.blocked = true;
                    gestureEvents.forEach(function (type) {
                        window.addEventListener(type, onGesture, { capture: true, passive: true });
                    });
                }

                function playEl(el) {
                    el.muted = true;
                    var promise = el.play();
                    if (promise && typeof promise.catch === 'function') {
                        promise.catch(function (err) {
                            if (err && err.name === 'NotAllowedError') onAutoplayBlocked();
                            // AbortError (interrupted by pause) and others: the watchdog heals.
                        });
                    }
                    return promise;
                }

                function maybeMakeStandby() {
                    // Never more than two copies (memory on mobile).
                    if (standby || incoming || state.failed || state.healing || standbyFailures >= 2) return;
                    if (media.querySelectorAll('video').length >= 2) return;
                    var nearEnd = isFinite(active.duration) && active.duration - active.currentTime < 8;
                    if (!bufferedToEnd(active) && !nearEnd) return;
                    var el = document.createElement('video');
                    el.className = 'hero-media__video';
                    ['muted', 'loop', 'playsinline', 'disablepictureinpicture', 'disableremoteplayback'].forEach(function (name) {
                        el.setAttribute(name, '');
                    });
                    el.setAttribute('tabindex', '-1');
                    el.preload = 'auto';
                    attach(el);
                    active.parentNode.insertBefore(el, active.nextSibling);
                    standby = el;
                    el.load();
                }

                function schedule() {
                    clearTimers();
                    if (!shouldPlay() || state.swapping) return;
                    var d = active.duration;
                    if (!isFinite(d) || d <= 0) {
                        timer = setTimeout(schedule, 250);
                        return;
                    }
                    maybeMakeStandby();
                    var untilLead = (d - LOOP_LEAD - active.currentTime) / (active.playbackRate || 1);
                    if (untilLead <= WARM_AHEAD) warmUp(standby);
                    if (untilLead > WARM_AHEAD + 0.25) {
                        timer = setTimeout(schedule, (untilLead - WARM_AHEAD) * 1000);
                    } else if (untilLead > 1) {
                        timer = setTimeout(schedule, (untilLead - 0.75) * 1000);
                    } else {
                        frame = requestAnimationFrame(watchEnd);
                    }
                }

                // Start the standby's decoder a few seconds before the loop point:
                // play a few frames invisibly, then pause and rewind (from memory).
                // A cold decoder start during the crossfade drops frames.
                function warmUp(el) {
                    // Also brings a standby that has not buffered yet up to readyState 4.
                    if (!el || el.__warmed || el.__warming || el.readyState < 1) return;
                    el.__warming = true;
                    var done = false;
                    // Called if the loop point arrives mid warm-up: keep it playing.
                    el.__warmCancel = function () {
                        done = true;
                        el.__warming = false;
                        el.__warmed = true;
                    };
                    var finish = function () {
                        if (done) return;
                        done = true;
                        el.pause();
                        try { el.currentTime = 0; } catch (e) {}
                        el.__warming = false;
                        el.__warmed = true;
                    };
                    el.muted = true;
                    var promise = el.play();
                    if (promise && typeof promise.catch === 'function') promise.catch(finish);
                    if ('requestVideoFrameCallback' in el) {
                        el.requestVideoFrameCallback(function () { el.requestVideoFrameCallback(finish); });
                    } else {
                        el.addEventListener('playing', function () { setTimeout(finish, 80); }, { once: true });
                    }
                    setTimeout(finish, 700);
                }

                function watchEnd() {
                    frame = 0;
                    if (!shouldPlay() || state.swapping) return;
                    if (active.currentTime >= active.duration - LOOP_LEAD) {
                        if (standby && standby.__warming) {
                            // Still warming up = already playing near 0: use it as is.
                            standby.__warmCancel();
                            startSwap();
                            return;
                        }
                        if (standby && standby.readyState >= 2) {
                            startSwap();
                            return;
                        }
                        if (active.duration - active.currentTime > 0.15) {
                            frame = requestAnimationFrame(watchEnd); // keep waiting for the standby
                            return;
                        }
                        // Standby still not ready: the native loop covers this wrap.
                        media.dataset.nativeWraps = String((+media.dataset.nativeWraps || 0) + 1);
                        timer = setTimeout(schedule, (LOOP_LEAD + 0.3) * 1000);
                        return;
                    }
                    frame = requestAnimationFrame(watchEnd);
                }

                function timeLeft(el) {
                    return Math.max(0.1, (el.duration - el.currentTime) / (el.playbackRate || 1));
                }

                function startSwap() {
                    state.swapping = true;
                    incoming = standby;
                    standby = null;
                    var el = incoming;
                    el.style.transition = 'none';
                    el.classList.add('is-front');
                    var begin = function () {
                        if (!state.swapping || incoming !== el) return;
                        // Finish ~0.1 s before the old copy's last frame, so it never
                        // reaches its end (no native-loop seek underneath).
                        var fade = Math.max(0.1, timeLeft(active) - 0.1);
                        void el.offsetWidth; // commit "transition: none" before fading
                        el.style.transition = 'opacity ' + Math.round(fade * 1000) + 'ms linear';
                        el.classList.add('is-shown');
                        timer = setTimeout(finishSwap, fade * 1000 + 20);
                    };
                    var promise = playEl(el);
                    if (promise && typeof promise.then === 'function') {
                        promise.then(function () { requestAnimationFrame(begin); }, abortSwap);
                    } else {
                        requestAnimationFrame(begin);
                    }
                }

                function abortSwap() {
                    if (!state.swapping) return;
                    var el = incoming;
                    state.swapping = false;
                    incoming = null;
                    if (el) {
                        el.pause();
                        el.style.transition = 'none';
                        el.classList.remove('is-shown', 'is-front');
                        try { el.currentTime = 0; } catch (e) {}
                        standby = el;
                    }
                    schedule();
                }

                function finishSwap() {
                    if (!state.swapping) return;
                    var outgoing = active;
                    active = incoming;
                    incoming = null;
                    state.swapping = false;
                    // The new copy is fully opaque on top: hide the old one underneath.
                    outgoing.style.transition = 'none';
                    outgoing.classList.remove('is-shown', 'is-front');
                    active.classList.remove('is-front');
                    active.style.transition = '';
                    outgoing.pause();
                    try { outgoing.currentTime = 0; } catch (e) {}
                    outgoing.__warmed = false; // warm it again before its next turn
                    standby = outgoing;
                    lastTime = -1;
                    schedule();
                }

                function sync() {
                    if (state.failed) return;
                    if (shouldPlay()) {
                        playEl(active);
                        if (incoming) playEl(incoming);
                        schedule();
                    } else {
                        clearTimers();
                        if (state.swapping) finishSwap(); // complete instantly while unseen/paused
                        clearTimers();
                        active.pause();
                    }
                }

                // Self-healing: if it should be playing but time has not moved for
                // STALL_LIMIT ms (stalled, waiting, ended, decoder hiccup), restart.
                function heal() {
                    heals++;
                    var el = active;
                    // Paused by the browser (not the visitor): just resume.
                    if (el.paused && heals < 3) {
                        playEl(el);
                        schedule();
                        return;
                    }
                    heals = 0;
                    var t = el.currentTime;
                    if (standby && standby.readyState >= 2) {
                        // "Playing" but stuck (stalled network, decoder hang): hand over
                        // to the standby copy, which holds the whole file, at the same
                        // moment; reload the stuck copy behind it as the new standby.
                        var fresh = standby;
                        standby = null;
                        state.healing = true;
                        var takeOver = function () {
                            if (!state.healing) return;
                            state.healing = false;
                            fresh.style.transition = 'none';
                            fresh.classList.add('is-shown', 'is-front');
                            playEl(fresh);
                            active = fresh;
                            el.style.transition = 'none';
                            el.classList.remove('is-shown', 'is-front');
                            requestAnimationFrame(function () { fresh.classList.remove('is-front'); });
                            el.pause();
                            el.__warmed = false;
                            el.load();
                            standby = el;
                            lastTime = -1;
                            schedule();
                        };
                        if (Math.abs(fresh.currentTime - t) < 0.05) {
                            takeOver();
                        } else {
                            fresh.addEventListener('seeked', takeOver, { once: true });
                            try { fresh.currentTime = t; } catch (e) { takeOver(); }
                        }
                        return;
                    }
                    // Last resort (no standby yet): reload this copy at the same time.
                    el.addEventListener('loadedmetadata', function () {
                        try { el.currentTime = t; } catch (e) {}
                    }, { once: true });
                    el.load();
                    playEl(el);
                    schedule();
                }

                setInterval(function () {
                    if (!shouldPlay() || state.swapping || state.healing) {
                        lastTime = -1;
                        return;
                    }
                    var now = Date.now();
                    var t = active.currentTime;
                    if (lastTime === -1 || (t !== lastTime && !active.ended)) {
                        if (t !== lastTime && lastTime !== -1) heals = 0;
                        lastTime = t;
                        lastMove = now;
                        return;
                    }
                    if (now - lastMove >= STALL_LIMIT) {
                        lastMove = now;
                        heal();
                    }
                }, 250);

                attach(video);

                document.addEventListener('visibilitychange', sync);
                onVisibilityChange(function (visible) {
                    state.inView = visible;
                    sync();
                });

                // Stop the video if the user turns on reduced motion mid-visit.
                if (window.matchMedia) {
                    var motion = matchMedia('(prefers-reduced-motion: reduce)');
                    var onMotion = function () { if (motion.matches) fail(); };
                    if (motion.addEventListener) motion.addEventListener('change', onMotion);
                }

                video.load();
                sync();
            }

            onReady(function () {
                var media = document.querySelector('[data-hero-media]');
                var hero = media && media.closest('.hero');
                if (!hero) return;

                initReveal(hero);
                var parallax = initParallax(hero, media);

                var listeners = [];
                if ('IntersectionObserver' in window) {
                    new IntersectionObserver(function (entries) {
                        var visible = entries[entries.length - 1].isIntersecting;
                        parallax.setActive(visible);
                        listeners.forEach(function (fn) { fn(visible); });
                    }, { threshold: 0 }).observe(hero);
                }

                var video = media.querySelector('video');
                if (video) {
                    initVideo(hero, media, video, function (fn) { listeners.push(fn); });
                }
            });
        })();
    </script>
@endpush
