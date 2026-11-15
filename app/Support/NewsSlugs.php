<?php

namespace App\Support;

use App\Models\News;
use App\Models\NewsSlugRedirect;
use Illuminate\Support\Str;

/**
 * Article URLs.
 *  - From the English URL title when one is given, otherwise "news-<id>".
 *  - The title never changes the slug; only a change of the English URL title does.
 *  - When the slug of an article that has been public changes, the old slug is
 *    kept as a 301 redirect (news_slug_redirects).
 */
class NewsSlugs
{
    public const MAX_LENGTH = 80;

    /** Pattern reserved for automatic slugs; an English URL title may not produce it. */
    public const AUTO_PATTERN = '/^news-\d+$/';

    public static function fromUrlTitle(?string $urlTitle): ?string
    {
        $slug = Str::slug(Str::limit(trim((string) $urlTitle), self::MAX_LENGTH, ''));

        return $slug === '' ? null : $slug;
    }

    /** The slug the article should have, given its current English URL title. */
    public static function desiredFor(News $news): string
    {
        $base = self::fromUrlTitle($news->url_title) ?? 'news-'.$news->id;

        return self::unique($base, $news->id);
    }

    /**
     * Give the article its new slug. Records a redirect from the old slug when
     * the article had been visible to the public.
     */
    public static function apply(News $news, string $newSlug, bool $wasPublic): void
    {
        $old = $news->slug;
        if ($old === $newSlug) {
            return;
        }

        if ($old !== null && $wasPublic) {
            NewsSlugRedirect::firstOrCreate(['old_slug' => $old], ['news_id' => $news->id]);
        }
        // Switching back to an earlier slug: that redirect is no longer needed.
        NewsSlugRedirect::where('old_slug', $newSlug)->where('news_id', $news->id)->delete();

        $news->slug = $newSlug;
        $news->save();
    }

    private static function unique(string $base, ?int $exceptId): string
    {
        $candidate = $base;
        $n = 2;
        while (self::taken($candidate, $exceptId)) {
            $candidate = $base.'-'.$n++;
        }

        return $candidate;
    }

    private static function taken(string $slug, ?int $exceptId): bool
    {
        return News::withTrashed()->where('slug', $slug)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists()
            || NewsSlugRedirect::where('old_slug', $slug)->when($exceptId, fn ($q) => $q->where('news_id', '!=', $exceptId))->exists();
    }
}
