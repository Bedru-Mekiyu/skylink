@extends('layouts.master')

@php
    $pageUrl = route('news.show', $newsItem->slug);
    $summary = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', $newsItem->excerpt ?: \App\Support\NewsBody::toText($newsItem->body_html))), 160);
    $shareText = rawurlencode($newsItem->title);
    $shareUrl = rawurlencode($pageUrl);
    $isPreview = $isPreview ?? false;
    // Show the excerpt as a lead only when the body doesn't already start with it.
    $excerptStart = mb_substr(trim((string) $newsItem->excerpt), 0, 40);
    // Bodies from the new admin form get an automatic excerpt, so no lead for them.
    $showLead = $newsItem->content_format !== 'html' && $newsItem->excerpt && ! str_starts_with(trim((string) $newsItem->content), $excerptStart);
@endphp

@php
    // Head data (title = the article headline, canonical, share tags, NewsArticle JSON-LD) come from App\Support\Seo.
    \App\Support\Seo::set([
        'title' => $newsItem->title, 'title_is_headline' => true, 'description' => $summary, 'type' => 'article',
        'image' => $newsItem->share_image_url, 'image_type' => $newsItem->image_share ? 'image/jpeg' : null, 'lang' => $newsItem->language === 'am' ? 'am' : 'en',
        'published' => $newsItem->display_date->toIso8601String(), 'modified' => optional($newsItem->updated_at)->toIso8601String(),
        'noindex' => $isPreview, 'breadcrumb' => [['News', route('content.index')], [$newsItem->title, $pageUrl]],
    ]);
@endphp

@push('styles')
    {{-- Share previews (Telegram, Facebook, LinkedIn, X) --}}
    @include('partials.news.styles')
    <style>
        .article-banner {
            min-height: 380px;
        }

        .article-banner .news-tag {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            margin-bottom: 14px;
            display: inline-block;
        }

        .article-banner h1 {
            font-size: clamp(1.7rem, 3.6vw, 2.8rem);
            max-width: 900px;
        }

        .article-banner .news-meta {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.9rem;
        }

        .article-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .article-back:hover {
            color: #fff;
        }

        .article {
            max-width: 780px;
            margin: -24px auto 0;
            position: relative;
            z-index: 2;
            background: #fff;
            border-radius: var(--news-radius);
            box-shadow: var(--news-shadow);
            padding: clamp(22px, 4vw, 48px);
        }

        .article__figure {
            margin: 0 0 28px;
            border-radius: 14px;
            overflow: hidden;
            background: #eef2f7;
        }

        .article__figure img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 560px;
            object-fit: contain;
            margin: 0 auto;
        }

        .article__lead {
            font-size: 1.15rem;
            line-height: 1.75;
            color: var(--news-ink);
            font-weight: 500;
            margin-bottom: 22px;
        }

        .article__body p {
            font-size: 1.04rem;
            line-height: 1.85;
            color: #374151;
            margin-bottom: 1.15em;
            overflow-wrap: anywhere;
        }

        .article__body[lang="am"] p {
            font-size: 1.08rem;
            line-height: 2;
        }

        /* Formatting from the admin editor */
        .article__body h2,
        .article__body h3,
        .article__body h4 {
            color: var(--news-ink);
            font-weight: 700;
            line-height: 1.4;
            margin: 1.6em 0 0.6em;
        }

        .article__body h2 { font-size: 1.45rem; }
        .article__body h3 { font-size: 1.25rem; }
        .article__body h4 { font-size: 1.1rem; }

        .article__body ul,
        .article__body ol {
            padding-left: 1.4em;
            margin-bottom: 1.15em;
            color: #374151;
            line-height: 1.85;
        }

        .article__body li { margin-bottom: 0.35em; }

        .article__body blockquote {
            border-left: 4px solid var(--news-blue);
            padding: 4px 0 4px 18px;
            margin: 1.4em 0;
            color: var(--news-ink);
            font-style: italic;
        }

        .article__body a {
            color: var(--news-blue);
            text-decoration: underline;
            text-underline-offset: 3px;
            overflow-wrap: anywhere;
        }

        .article-preview-notice {
            background: #fef3c7;
            color: #78350f;
            border-bottom: 1px solid #fcd34d;
            padding: 10px 0;
            font-weight: 600;
            text-align: center;
        }

        .share {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #eef1f6;
        }

        .share__label {
            font-weight: 600;
            margin-right: 6px;
        }

        .share a,
        .share button {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--news-blue-soft);
            color: var(--news-blue);
            font-size: 1.05rem;
            text-decoration: none;
            transition: background 0.2s ease, color 0.2s ease;
            position: relative;
        }

        .share a:hover,
        .share button:hover {
            background: var(--news-blue);
            color: #fff;
        }

        .share__copied {
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: #fff;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 6px;
            white-space: nowrap;
            pointer-events: none;
        }

        .share__copied:empty {
            display: none;
        }

        .related {
            padding: 56px 0 72px;
        }

        .related h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 22px;
        }
    </style>
@endpush

@section('content')
<div class="news-page">
    @if ($isPreview)
        <div class="article-preview-notice" role="status">
            Preview: this article is {{ $newsItem->status === 'scheduled' ? 'scheduled' : 'a draft' }} and is not visible to visitors.
            <a href="{{ route('admin.news.edit', $newsItem->id) }}">Back to editing</a>
        </div>
    @endif
    <section class="news-banner article-banner">
        @if ($newsItem->image_url)
            <img class="news-banner__img" src="{{ $newsItem->image_url }}" alt="{{ $newsItem->title }}" width="1600" height="900" fetchpriority="high" decoding="async">
        @endif
        <div class="container" lang="{{ $newsItem->language }}">
            <a class="article-back" href="{{ route('content.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> All news</a><br>
            <a class="news-tag text-decoration-none" href="{{ route('content.index', ['category' => $newsItem->category->slug]) }}">{{ $newsItem->category->label }}</a>
            <h1 id="article-title">{{ $newsItem->title }}</h1>
            <div class="news-meta">
                <span><i class="far fa-calendar me-1" aria-hidden="true"></i><time datetime="{{ $newsItem->display_date->toDateString() }}">{{ $newsItem->display_date->format('F j, Y') }}</time></span>
                <span><i class="far fa-clock me-1" aria-hidden="true"></i>{{ $newsItem->reading_minutes }} min read</span>
            </div>
        </div>
        <svg class="news-banner__curve" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M0,56 L0,24 C240,52 480,60 720,44 C960,28 1200,8 1440,22 L1440,56 Z"></path>
        </svg>
    </section>

    <div class="container">
        <article class="article" aria-labelledby="article-title">
            @if ($newsItem->image_url)
                <figure class="article__figure">
                    <img src="{{ $newsItem->image_url }}" alt="{{ $newsItem->title }}" width="1200" height="800" decoding="async">
                </figure>
            @endif

            <div class="article__body" lang="{{ $newsItem->language }}">
                @if ($showLead)
                    <p class="article__lead">{{ $newsItem->excerpt }}</p>
                @endif
                {{-- Sanitised HTML (App\Support\NewsBody): only p, br, b/strong, i/em, h2-h4, lists, blockquote and safe links. --}}
                {!! $newsItem->body_html !!}
            </div>

            <div class="share" data-share>
                <span class="share__label">Share</span>
                <a href="https://t.me/share/url?url={{ $shareUrl }}&amp;text={{ $shareText }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Telegram (opens in a new tab)"><i class="fab fa-telegram-plane" aria-hidden="true"></i></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook (opens in a new tab)"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn (opens in a new tab)"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&amp;text={{ $shareText }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X (opens in a new tab)"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                <button type="button" data-copy-link="{{ $pageUrl }}" aria-label="Copy link to this article">
                    <i class="fas fa-link" aria-hidden="true"></i>
                    <span class="share__copied" aria-hidden="true"></span>
                </button>
                <span class="visually-hidden" role="status" aria-live="polite" data-share-status></span>
            </div>
        </article>
    </div>

    @if ($related->count())
        <section class="related" aria-labelledby="related-title">
            <div class="container">
                <h2 id="related-title">Related news</h2>
                <div class="row g-4">
                    @foreach ($related as $post)
                        <div class="col-12 col-md-6 col-lg-4">
                            @include('partials.news.card', ['post' => $post])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Copy-link button. The layout renders the scripts stack twice: run once.
    (function () {
        if (window.__skyNewsShare) return;
        window.__skyNewsShare = true;
        document.addEventListener('click', function (e) {
            var button = e.target.closest('[data-copy-link]');
            if (!button) return;
            var url = button.getAttribute('data-copy-link');
            var tip = button.querySelector('.share__copied');
            var status = document.querySelector('[data-share-status]');
            var done = function (ok) {
                tip.textContent = ok ? 'Copied' : 'Copy failed';
                if (status) status.textContent = ok ? 'Link copied' : 'Copy failed';
                clearTimeout(button.__t);
                button.__t = setTimeout(function () { tip.textContent = ''; }, 1600);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function () { done(true); }, function () { done(false); });
            } else {
                var area = document.createElement('textarea');
                area.value = url;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
                area.remove();
                done(ok);
            }
        });
    })();
</script>
@endpush
