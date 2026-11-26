@extends('layouts.master')


@push('styles')
    @include('partials.news.styles')
    <style>
        .news-results-head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 20px;
        }

        .news-results-head h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .news-results-head a {
            color: var(--news-blue);
            font-weight: 600;
            text-decoration: none;
        }

        .news-empty {
            background: #fff;
            border-radius: var(--news-radius);
            box-shadow: var(--news-shadow);
            padding: 48px 24px;
            text-align: center;
        }

        .news-empty i {
            font-size: 2.2rem;
            color: var(--news-blue);
            margin-bottom: 12px;
        }

        .news-empty p {
            color: var(--news-muted);
            margin: 6px 0 18px;
        }

        .news-pagination {
            margin: 40px 0 8px;
        }

        .news-pagination .pagination {
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .news-pagination .page-link {
            border: 0;
            border-radius: 50px !important;
            min-width: 42px;
            text-align: center;
            color: var(--news-blue);
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .news-pagination .page-item.active .page-link {
            background: var(--news-blue);
            color: #fff;
        }

        .news-pagination .page-item.disabled .page-link {
            color: #9ca3af;
        }

        .news-pagination p.small {
            display: none; /* "Showing x to y of z" from the default view */
        }

        /* ----- videos strip ----- */
        .news-videos {
            padding: 56px 0 72px;
            background: linear-gradient(180deg, var(--news-bg), #eef4ff);
        }

        .news-videos h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .news-videos__intro {
            color: var(--news-muted);
            margin: 0 0 22px;
        }

        /* Continuous marquee, same technique as the homepage testimonials and Featured Products:
           the cards twice in one track, translated by -50%, linear, infinite, paused on hover.
           --w card width and --g gap are unitless px so the speed stays ~80px/s. */
        .news-videos__row {
            overflow: hidden;
            padding: 4px 0 18px;
            --w: 250;
            --g: 18;
        }

        .news-videos__track {
            display: flex;
            width: max-content;
            margin: 0;
            padding: 0;
            list-style: none;
            animation: slideNewsVideos calc(var(--n) * (var(--w) + var(--g)) / 80 * 1s) linear infinite;
        }

        .news-videos__track > li {
            flex: 0 0 calc(var(--w) * 1px);
            margin: 0 calc(var(--g) * 0.5px);
        }

        .news-videos__row:hover .news-videos__track {
            animation-play-state: paused;
        }

        @keyframes slideNewsVideos {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Reduced motion: no sliding; the row sits still, scrollable by hand, each video once. */
        @media (prefers-reduced-motion: reduce) {
            .news-videos__track { animation: none; }
            .news-videos__row { overflow-x: auto; }
            .news-videos__track > .marquee-dup { display: none; }
        }

        .news-video {
            position: relative;
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--news-shadow);
            height: 100%;
        }

        .news-video__thumb {
            position: relative;
            aspect-ratio: 9 / 12;
            background: linear-gradient(160deg, #06122b, #223c80 60%, #1a6fb0);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .news-video__thumb img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .news-video__play {
            position: relative;
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.92);
            color: var(--news-blue);
            font-size: 1.2rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
        }

        .news-video__platform {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 3px 10px;
            border-radius: 50px;
            background: rgba(4, 12, 34, 0.65);
            color: #fff;
            font-size: 0.75rem;
        }

        .news-video__title {
            margin: 0;
            padding: 12px 14px 16px;
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-video__title a {
            color: var(--news-ink);
            text-decoration: none;
        }

        .news-video__title a::after {
            content: '';
            position: absolute;
            inset: 0;
        }
        /* Landing spot for the filter links: just below the sticky navbar (56px) plus a little air. */
        .news-list {
            scroll-margin-top: 68px;
        }

        /* Focused by the script after paging; no outline box around the whole list. */
        .news-list:focus {
            outline: none;
        }
    </style>
@endpush

@section('content')
<div class="news-page">
    {{-- Top banner: the newest video (or newest article), see partials/news/hero. --}}
    @include('partials.news.hero')

    <div class="container pb-5 news-list" id="news-list">
        {{-- Filters and search work through the URL (?category=&q=&page=). Every link
             and form that changes the list ends in #news-list, so the browser lands
             on the filter bar instead of the top of the page (see the script below). --}}
        <div class="news-toolbar">
            <nav aria-label="News categories">
                <ul class="news-chips">
                    <li>
                        <a class="news-chip" href="{{ route('content.index', array_filter(['q' => $search])) }}#news-list" @if (! $activeCategory) aria-current="page" @endif>
                            All Content
                        </a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a class="news-chip" href="{{ route('content.index', array_filter(['category' => $category->slug, 'q' => $search])) }}#news-list" @if ($activeCategory?->is($category)) aria-current="page" @endif>
                                {{ $category->label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            <form class="news-search" role="search" action="{{ route('content.index') }}#news-list" method="GET">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                @endif
                <label for="news-search-input" class="visually-hidden">Search news</label>
                <input id="news-search-input" type="search" name="q" value="{{ $search }}" placeholder="Search news…" maxlength="100">
                <button type="submit" aria-label="Search"><i class="fas fa-search" aria-hidden="true"></i></button>
            </form>
        </div>

        @if ($search !== '')
            <div class="news-results-head">
                <h2>{{ $resultCount }} {{ \Illuminate\Support\Str::plural('result', $resultCount) }} for “{{ $search }}”@if ($activeCategory) in {{ $activeCategory->label }}@endif</h2>
                @if ($resultInBanner ?? false)
                    <p class="mb-0 small text-muted">One of them is the story at the top of this page.</p>
                @endif
                <a href="{{ route('content.index', array_filter(['category' => $activeCategory?->slug])) }}#news-list">Clear search</a>
            </div>
        @endif

        @if ($featured)
            <article class="news-featured" lang="{{ $featured->language }}">
                <div class="news-featured__media">
                    @if ($featured->image_url)
                        <img src="{{ $featured->image_url }}" alt="{{ $featured->title }}" width="1200" height="675" fetchpriority="high" decoding="async">
                    @endif
                </div>
                <div class="news-featured__body">
                    <span class="news-featured__label">Featured story</span>
                    <span class="news-tag">{{ $featured->category->label ?? 'News' }}</span>
                    <h2 class="news-featured__title"><a href="{{ route('news.show', $featured->slug) }}">{{ $featured->title }}</a></h2>
                    <div class="news-meta">
                        <span><i class="far fa-calendar me-1" aria-hidden="true"></i><time datetime="{{ $featured->display_date->toDateString() }}">{{ $featured->display_date->format('F j, Y') }}</time></span>
                        <span><i class="far fa-clock me-1" aria-hidden="true"></i>{{ $featured->reading_minutes }} min read</span>
                    </div>
                    @if ($featured->excerpt)
                        <p class="news-featured__excerpt">{{ $featured->excerpt }}</p>
                    @endif
                    <span class="news-btn" aria-hidden="true">Read article <i class="fas fa-arrow-right"></i></span>
                </div>
            </article>
        @endif

        @if ($news->count())
            <div class="row g-4">
                @foreach ($news as $post)
                    <div class="col-12 col-md-6 col-lg-4">
                        @include('partials.news.card', ['post' => $post])
                    </div>
                @endforeach
            </div>

            @if ($news->hasPages())
                <nav class="news-pagination" aria-label="News pages">
                    {{ $news->fragment('news-list')->onEachSide(1)->links('pagination::bootstrap-5') }}
                </nav>
            @endif
        @elseif (! $featured && ($resultInBanner ?? false))
            {{-- The only match is the article already shown in the banner. --}}
            <div class="news-empty" role="status">
                <i class="far fa-newspaper" aria-hidden="true"></i>
                <h2 class="h5">The only matching story is the one at the top of this page.</h2>
                <a class="news-btn" href="{{ route('content.index') }}#news-list">See all content</a>
            </div>
        @elseif (! $featured)
            <div class="news-empty" role="status">
                <i class="far fa-newspaper" aria-hidden="true"></i>
                @if ($search !== '')
                    <h2 class="h5">No articles match “{{ $search }}”.</h2>
                    <p>Try another word, or browse all articles.</p>
                @elseif ($activeCategory)
                    <h2 class="h5">No {{ $activeCategory->label }} articles yet.</h2>
                    <p>New stories appear here as soon as they are published.</p>
                @else
                    <h2 class="h5">No news articles yet.</h2>
                    <p>Please check back soon.</p>
                @endif
                <a class="news-btn" href="{{ route('content.index') }}#news-list">See all content</a>
            </div>
        @endif
    </div>

    @if ($videos->count())
        <section class="news-videos" aria-labelledby="news-videos-title">
            <div class="container">
                <h2 id="news-videos-title">Videos</h2>
                <p class="news-videos__intro">Short clips from our projects, products and events.</p>
                @php
                    // Enough repeats that one half of the track is always wider than the screen (no gap when few videos).
                    $reps = max(1, (int) ceil(1920 / max(1, $videos->count() * 268)));
                @endphp
                <div class="news-videos__row" style="--n: {{ $videos->count() * $reps }}">
                    <ul class="news-videos__track">
                        @foreach (range(0, $reps * 2 - 1) as $copy)
                        @foreach ($videos as $video)
                            @php
                                $host = parse_url($video->video_url, PHP_URL_HOST) ?? '';
                                $platform = str_contains($host, 'tiktok') ? ['TikTok', 'fab fa-tiktok'] : (str_contains($host, 'youtu') ? ['YouTube', 'fab fa-youtube'] : ['Video', 'fas fa-video']);
                                $isAmharic = preg_match('/\p{Ethiopic}/u', $video->title);
                            @endphp
                            <li @if ($copy > 0) class="marquee-dup" aria-hidden="true" inert @endif>
                                <article class="news-video" lang="{{ $isAmharic ? 'am' : 'en' }}">
                                    <div class="news-video__thumb">
                                        @if ($video->thumbnail_image)
                                            <img src="{{ asset('storage/'.$video->thumbnail_image) }}" alt="{{ $video->title }}" width="260" height="347" loading="lazy" decoding="async">
                                        @endif
                                        <span class="news-video__platform"><i class="{{ $platform[1] }} me-1" aria-hidden="true"></i>{{ $platform[0] }}</span>
                                        <span class="news-video__play" aria-hidden="true"><i class="fas fa-play"></i></span>
                                    </div>
                                    <h3 class="news-video__title"><a href="{{ route('videos.show', $video->slug) }}">{{ $video->title }}</a></h3>
                                </article>
                            </li>
                        @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // /news: change category, search or page without reloading the whole page.
    // Fetches the new page, swaps only #news-list (the banner and the videos do
    // not depend on the filters), updates the URL and supports Back/Forward.
    // Any failure falls back to a normal page load, where #news-list in the
    // URL makes the browser land on the filter bar. The layout renders the
    // scripts stack twice: run once.
    (function () {
        if (window.__skyNewsList || !window.fetch || !window.history || !history.pushState || !window.DOMParser) return;
        window.__skyNewsList = true;

        function list() { return document.getElementById('news-list'); }

        // Jump to the filter bar, just below the sticky navbar (its scroll-margin-top),
        // like a normal page load. Instant: it also cancels a smooth scroll still running.
        function placeView(target) {
            var margin = parseFloat(getComputedStyle(target).scrollMarginTop) || 0;
            window.scrollTo({ top: target.getBoundingClientRect().top + window.pageYOffset - margin, behavior: 'instant' });
        }

        function load(url, push, focusSelector) {
            var target = list();
            if (!target) { location.href = url; return; }
            target.setAttribute('aria-busy', 'true');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var fresh = doc.getElementById('news-list');
                    if (!fresh) throw new Error('no #news-list');
                    target.innerHTML = fresh.innerHTML;
                    target.removeAttribute('aria-busy');
                    if (doc.title) document.title = doc.title;
                    if (push) history.pushState({ newsList: true }, '', url);
                    // Keep keyboard focus in the list (the clicked element was replaced):
                    // the search box, the active category, or the list itself.
                    var next = focusSelector === 'list' ? target
                        : (focusSelector && target.querySelector(focusSelector)) || target.querySelector('.news-chip[aria-current="page"]') || target;
                    if (next === target) target.setAttribute('tabindex', '-1');
                    next.focus({ preventScroll: true });
                    placeView(target);
                    // Safety net: after a search sent with Enter, Chrome moves the
                    // page once more on the next frame; put it back if it moved.
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            var margin = parseFloat(getComputedStyle(target).scrollMarginTop) || 0;
                            if (Math.abs(target.getBoundingClientRect().top - margin) > 2) placeView(target);
                        });
                    });
                })
                .catch(function () { location.href = url; });
        }

        function sameListPage(url) {
            return url.origin === location.origin && url.pathname === location.pathname && url.hash === '#news-list';
        }

        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target.closest && e.target.closest('#news-list a[href]');
            if (!a || a.target === '_blank') return;
            var url = new URL(a.href, location.href);
            if (!sameListPage(url)) return;
            e.preventDefault();
            load(url.href, true, a.closest('.pagination') ? 'list' : null);
        });

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (e.defaultPrevented || !form.matches || !form.matches('#news-list form.news-search')) return;
            var url = new URL(form.action, location.href);
            if (!sameListPage(url)) return;
            e.preventDefault();
            url.search = new URLSearchParams(new FormData(form)).toString();
            load(url.href, true, '#news-search-input');
        });

        // Back/Forward between list states: reload the list for that URL. The
        // browser's own scroll restoration would run after the swap and move the
        // page, so this page places the view itself (on the filter bar).
        history.scrollRestoration = 'manual';
        history.replaceState({ newsList: true }, '', location.href);
        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.newsList) load(location.href, false, null);
        });
    })();
</script>
@endpush
