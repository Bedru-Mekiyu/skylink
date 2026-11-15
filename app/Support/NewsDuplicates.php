<?php

namespace App\Support;

use App\Models\News;
use Illuminate\Support\Collection;

/**
 * Finds groups of duplicate articles and decides which one stays visible.
 *
 * Rule: same normalised title (NewsTitle::normalize: case, spacing,
 * punctuation and emoji ignored) and the same language.
 *
 * Considered: every article that is not deleted, plus articles already hidden
 * as duplicates (so earlier decisions are reported, and can be reversed with
 * --keep=newer or "always_keep"). Articles an admin deleted are ignored.
 */
class NewsDuplicates
{
    public const KEEP = 'keep';          // stays (or becomes) visible
    public const HIDE = 'hide';          // will be hidden, its address redirects to the kept one
    public const RESTORE = 'restore';    // hidden today, but is the one to keep
    public const DONE = 'already-hidden';

    /**
     * @return Collection<int, array{key: string, ignored: bool, keeper: News, members: Collection<int, array{news: News, action: string}>}>
     */
    public static function plan(string $keep = 'older'): Collection
    {
        $config = config('news.duplicates');
        $alwaysKeep = array_flip($config['always_keep'] ?? []);
        $notDuplicates = array_map(fn ($slugs) => array_values((array) $slugs), $config['not_duplicates'] ?? []);

        return News::withTrashed()
            ->where(fn ($q) => $q->whereNull('deleted_at')->orWhereNotNull('duplicate_of_id'))
            ->with('category')
            ->get()
            ->groupBy(fn (News $n) => NewsTitle::normalize($n->title).'|'.$n->language)
            ->filter(fn (Collection $g, string $key) => $g->count() > 1 && ! str_starts_with($key, '|'))
            ->map(function (Collection $group, string $key) use ($keep, $alwaysKeep, $notDuplicates) {
                $slugs = $group->pluck('slug')->all();
                $ignored = collect($notDuplicates)->contains(fn ($set) => array_diff($slugs, $set) === []);

                $ordered = $group->sortBy(fn (News $n) => [($n->published_at ?? $n->created_at)?->getTimestamp(), $n->id])->values();
                if ($keep === 'newer') {
                    $ordered = $ordered->reverse()->values();
                }
                $keeper = $ordered->first(fn (News $n) => isset($alwaysKeep[$n->slug])) ?? $ordered->first();

                $members = $ordered->map(function (News $n) use ($keeper, $ignored) {
                    if ($ignored) {
                        return ['news' => $n, 'action' => $n->trashed() ? self::DONE : self::KEEP];
                    }
                    if ($n->is($keeper)) {
                        return ['news' => $n, 'action' => $n->trashed() ? self::RESTORE : self::KEEP];
                    }
                    $done = $n->trashed() && (int) $n->duplicate_of_id === $keeper->id;

                    return ['news' => $n, 'action' => $done ? self::DONE : self::HIDE];
                });

                return ['key' => $key, 'ignored' => $ignored, 'keeper' => $keeper, 'members' => $members];
            })
            ->sortBy(fn ($g) => $g['keeper']->id)
            ->values();
    }

    /** Number of changes --apply would make. */
    public static function pendingChanges(Collection $plan): int
    {
        return $plan->reject(fn ($g) => $g['ignored'])
            ->sum(fn ($g) => $g['members']->whereIn('action', [self::HIDE, self::RESTORE])->count());
    }
}
