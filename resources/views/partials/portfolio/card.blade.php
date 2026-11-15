{{-- One project card (/portfolio and the homepage "Featured Portfolio"). Expects $project.
     Optional $withClip (only /portfolio passes true): the project's showcase clip plays on
     hover/focus on desktop. No video is downloaded until then (preload="none", sources
     added by the script); phones keep the image only. --}}
@php($headingTag = $headingLevel ?? 'h3')
@php($clip = ($withClip ?? false) && $project->has_video)
<article class="project-card" data-title="{{ \Illuminate\Support\Str::lower($project->title) }}" @if ($clip) data-project-clip tabindex="0" @endif>
    <div class="project-card__media">
        @if ($project->image)
            <img src="{{ asset('storage/'.$project->image) }}" alt="{{ $project->title }}" width="800" height="500" loading="lazy" decoding="async">
        @endif
        @if ($clip)
            <video class="project-card__clip" muted loop playsinline preload="none" aria-hidden="true" tabindex="-1"
                   disablepictureinpicture disableremoteplayback
                   @if ($project->video_webm_url) data-webm="{{ $project->video_webm_url }}" @endif
                   @if ($project->video_mp4_url) data-mp4="{{ $project->video_mp4_url }}" @endif></video>
            <span class="project-card__clip-badge" aria-hidden="true"><i class="fas fa-play"></i> Preview</span>
        @endif
    </div>
    <div class="project-card__body">
        <{{ $headingTag }} class="project-card__title">{{ $project->title }}</{{ $headingTag }}>
        <p class="project-card__text">{{ $project->description }}</p>
        @if ($project->project_url)
            <a class="project-card__link" href="{{ $project->project_url }}" target="_blank" rel="noopener noreferrer">
                Visit project <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span class="visually-hidden">(opens {{ $project->title }} in a new tab)</span>
            </a>
        @endif
    </div>
</article>

@if ($clip)
@once
@push('styles')
<style>
    .project-card__media { position: relative; }
    .project-card__clip {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: 50% 0;
        opacity: 0;
        transition: opacity 0.35s ease;
        pointer-events: none;
    }
    .project-card__clip.is-playing { opacity: 1; }
    .project-card__clip-badge {
        position: absolute;
        left: 12px;
        bottom: 12px;
        padding: 4px 10px;
        border-radius: 50px;
        background: rgba(6, 18, 43, 0.72);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
        display: none;
    }
    .has-hover-clips .project-card__clip-badge { display: inline-block; }
    .project-card[data-project-clip]:focus-visible { outline: 3px solid #3B82F6; outline-offset: 4px; }
    @media (prefers-reduced-motion: reduce) {
        .project-card__clip { display: none; }
    }
</style>
@endpush
@push('scripts')
<script>
    // Card clips: desktop with a mouse only, not for reduced motion, Save-Data or
    // slow connections. Plays while hovered/focused AND on screen. Run once.
    (function () {
        if (window.__skyProjectClips) return;
        window.__skyProjectClips = true;

        function allowed() {
            if (!window.matchMedia) return false;
            var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            return matchMedia('(hover: hover) and (pointer: fine) and (min-width: 768px)').matches
                && !matchMedia('(prefers-reduced-motion: reduce)').matches
                && !(conn && (conn.saveData || ['slow-2g', '2g'].indexOf(conn.effectiveType) !== -1));
        }

        function init() {
            var cards = document.querySelectorAll('[data-project-clip]');
            if (!cards.length || !allowed()) return;
            document.documentElement.classList.add('has-hover-clips');

            var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { if (!e.isIntersecting) stop(e.target); });
            }, { threshold: 0.2 }) : null;

            function load(video) {
                if (video.__loaded) return;
                video.__loaded = true;
                [['data-webm', 'video/webm'], ['data-mp4', 'video/mp4']].forEach(function (pair) {
                    var src = video.getAttribute(pair[0]);
                    if (src && video.canPlayType(pair[1])) {
                        var s = document.createElement('source');
                        s.src = src; s.type = pair[1];
                        video.appendChild(s);
                    }
                });
                video.addEventListener('playing', function () { video.classList.add('is-playing'); });
                video.load(); // first download happens here, on the first hover/focus
            }
            function start(card) {
                var video = card.querySelector('.project-card__clip');
                if (!video) return;
                load(video);
                video.muted = true;
                var p = video.play();
                if (p && p.catch) p.catch(function () {});
            }
            function stop(card) {
                var video = card.querySelector('.project-card__clip');
                if (!video) return;
                video.pause();
                video.classList.remove('is-playing');
            }

            cards.forEach(function (card) {
                card.addEventListener('mouseenter', function () { start(card); });
                card.addEventListener('mouseleave', function () { if (!card.contains(document.activeElement)) stop(card); });
                card.addEventListener('focusin', function () { start(card); });
                card.addEventListener('focusout', function (e) { if (!card.contains(e.relatedTarget)) stop(card); });
                if (io) io.observe(card);
            });
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
</script>
@endpush
@endonce
@endif
