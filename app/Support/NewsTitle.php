<?php

namespace App\Support;

use App\Models\News;
use Illuminate\Support\Collection;
use Normalizer;

/**
 * Title comparison for duplicate protection. Works for Amharic and English:
 * Unicode-normalised, case-folded, punctuation and emoji removed.
 */
class NewsTitle
{
    public static function normalize(string $title): string
    {
        $title = class_exists(Normalizer::class) ? (Normalizer::normalize($title, Normalizer::FORM_KC) ?: $title) : $title;
        $title = mb_strtolower($title, 'UTF-8');
        // Keep letters (incl. Ethiopic) and digits; everything else becomes a space.
        $title = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $title);

        return trim(preg_replace('/\s+/u', ' ', $title));
    }

    /** Same title, or nearly the same wording (70% of words shared). */
    public static function isSimilar(string $a, string $b): bool
    {
        $na = self::normalize($a);
        $nb = self::normalize($b);
        if ($na === '' || $nb === '') {
            return false;
        }
        if ($na === $nb) {
            return true;
        }
        // One title contains most of the other (e.g. "Minber TV" and "5 Minber TV").
        $shorter = mb_strlen($na) <= mb_strlen($nb) ? $na : $nb;
        $longer = $shorter === $na ? $nb : $na;
        if (mb_strlen($shorter) >= 6 && mb_strlen($shorter) * 2 >= mb_strlen($longer) && str_contains($longer, $shorter)) {
            return true;
        }
        $wa = array_unique(explode(' ', $na));
        $wb = array_unique(explode(' ', $nb));
        if (count($wa) < 3 || count($wb) < 3) {
            return false;
        }
        $shared = count(array_intersect($wa, $wb));

        // Most of the words in common, or a shorter headline (4+ words) whose
        // words nearly all appear in the longer one.
        return $shared / count(array_unique(array_merge($wa, $wb))) >= 0.7
            || (min(count($wa), count($wb)) >= 4 && $shared / min(count($wa), count($wb)) >= 0.8);
    }

    /**
     * Existing articles (any status, not deleted) with a similar title.
     *
     * @return Collection<int, News>
     */
    public static function findSimilar(string $title, ?int $exceptId = null, int $limit = 5): Collection
    {
        return News::query()
            ->select(['id', 'title', 'slug', 'status', 'published_at', 'created_at'])
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderByDesc('id')
            ->limit(2000)
            ->get()
            ->filter(fn (News $n) => self::isSimilar($title, $n->title))
            ->take($limit)
            ->values();
    }

    /** An identical (normalised) title created in the last N minutes, if any. */
    public static function recentIdentical(string $title, int $minutes, ?int $exceptId = null): ?News
    {
        $normalized = self::normalize($title);

        return News::query()
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->get(['id', 'title', 'slug', 'status', 'published_at', 'created_at'])
            ->first(fn (News $n) => self::normalize($n->title) === $normalized);
    }
}
