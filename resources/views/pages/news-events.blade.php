@extends('layouts.master')

@section('title', $activeCategory ? $activeCategory->label.' | News, Innovations & Events | Sky Link' : 'News, Innovations & Events | Sky Link')
@section('description', 'Discover the latest developments, updates, and event highlights from our team.')

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

        .news-videos__row {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(230px, 260px);
            gap: 18px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            padding: 4px 4px 18px;
            margin: 0;
            list-style: none;
        }

        .news-video {
            position: relative;
            scroll-snap-align: start;
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--news-shadow);
            height: 100%;
        }

        .news-video__thumb {
            position: relative;
            aspect-ratio: 9 / 12;
            background: linear-gradient(160deg, #06122b, #1e3a8a 60%, #3B82F6);
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
    </style>
@endpush

@section('content')
<div class="news-page">
    {{-- Top banner: the newest video (or newest article), see partials/news/hero. --}}
    @include('partials.news.hero')

    <div class="container pb-5">
        {{-- Filters and search work through the URL (?category=&q=&page=). --}}
        <div class="news-toolbar">
            <nav aria-label="News categories">
                <ul class="news-chips">
                    <li>
                        <a class="news-chip" href="{{ route('content.index', array_filter(['q' => $search])) }}" @if (! $activeCategory) aria-current="page" @endif>
                            All Content <span class="news-chip__count">{{ $totalCount }}</span>
                        </a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a class="news-chip" href="{{ route('content.index', array_filter(['category' => $category->slug, 'q' => $search])) }}" @if ($activeCategory?->is($category)) aria-current="page" @endif>
                                {{ $category->label }} <span class="news-chip__count">{{ $category->news_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            <form class="news-search" role="search" action="{{ route('content.index') }}" method="GET">
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
                <a href="{{ route('content.index', array_filter(['category' => $activeCategory?->slug])) }}">Clear search</a>
            </div>
        @endif

        @if ($featured)
            <article class="news-featured" lang="{{ $featured->language }}">
                <div class="news-featured__media">
                    @if ($featured->image_url)
                        <img src="{{ $featured->image_url }}" alt="" width="1200" height="675" decoding="async">
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
                    {{ $news->onEachSide(1)->links('pagination::bootstrap-5') }}
                </nav>
            @endif
        @elseif (! $featured && ($resultInBanner ?? false))
            {{-- The only match is the article already shown in the banner. --}}
            <div class="news-empty" role="status">
                <i class="far fa-newspaper" aria-hidden="true"></i>
                <h2 class="h5">The only matching story is the one at the top of this page.</h2>
                <a class="news-btn" href="{{ route('content.index') }}">See all content</a>
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
                <a class="news-btn" href="{{ route('content.index') }}">See all content</a>
            </div>
        @endif
    </div>

    @if ($videos->count())
        <section class="news-videos" aria-labelledby="news-videos-title">
            <div class="container">
                <h2 id="news-videos-title">Videos</h2>
                <p class="news-videos__intro">Short clips from our projects, products and events.</p>
                <ul class="news-videos__row">
                    @foreach ($videos as $video)
                        @php
                            $host = parse_url($video->video_url, PHP_URL_HOST) ?? '';
                            $platform = str_contains($host, 'tiktok') ? ['TikTok', 'fab fa-tiktok'] : (str_contains($host, 'youtu') ? ['YouTube', 'fab fa-youtube'] : ['Video', 'fas fa-video']);
                            $isAmharic = preg_match('/\p{Ethiopic}/u', $video->title);
                        @endphp
                        <li>
                            <article class="news-video" lang="{{ $isAmharic ? 'am' : 'en' }}">
                                <div class="news-video__thumb">
                                    @if ($video->thumbnail_image)
                                        <img src="{{ asset('storage/'.$video->thumbnail_image) }}" alt="" width="260" height="347" loading="lazy" decoding="async">
                                    @endif
                                    <span class="news-video__platform"><i class="{{ $platform[1] }} me-1" aria-hidden="true"></i>{{ $platform[0] }}</span>
                                    <span class="news-video__play" aria-hidden="true"><i class="fas fa-play"></i></span>
                                </div>
                                <h3 class="news-video__title"><a href="{{ route('videos.show', $video->slug) }}">{{ $video->title }}</a></h3>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</div>
@endsection
