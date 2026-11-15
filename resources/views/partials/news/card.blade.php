{{-- One article card. Expects $post (App\Models\News) and optional $headingLevel. --}}
@php($tag = $headingLevel ?? 'h3')
<article class="news-card" lang="{{ $post->language }}">
    <div class="news-card__media">
        @if ($post->thumb_url)
            <img src="{{ $post->thumb_url }}" alt="" width="640" height="360" loading="lazy" decoding="async">
        @endif
    </div>
    <div class="news-card__body">
        <span class="news-tag">{{ $post->category->label ?? 'News' }}</span>
        <div class="news-meta">
            <span><i class="far fa-calendar me-1" aria-hidden="true"></i><time datetime="{{ $post->display_date->toDateString() }}">{{ $post->display_date->format('M j, Y') }}</time></span>
            <span><i class="far fa-clock me-1" aria-hidden="true"></i>{{ $post->reading_minutes }} min read</span>
        </div>
        <{{ $tag }} class="news-card__title">
            <a href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a>
        </{{ $tag }}>
        @if ($post->excerpt)
            <p class="news-card__excerpt">{{ $post->excerpt }}</p>
        @endif
        <span class="news-card__more" aria-hidden="true">Read more <i class="fas fa-arrow-right ms-1"></i></span>
    </div>
</article>
