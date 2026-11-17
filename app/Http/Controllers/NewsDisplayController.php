<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsSlugRedirect;
use App\Models\Video;
use Illuminate\Http\Request;

class NewsDisplayController extends Controller
{
    public const PER_PAGE = 9;

    /**
     * News list. Everything is driven by the URL so it can be shared, and it
     * survives pagination and the back button:
     *   ?category=<news|innovation|events>  ?q=<search>  ?page=<n>
     * Page 1 without a search shows the newest matching article as the
     * featured story; it is then left out of the grid.
     */
    public function index(Request $request)
    {
        $categories = NewsCategory::get()
            ->sortBy(fn ($c) => array_search($c->name, NewsCategory::DISPLAY_ORDER, true) === false
                ? 99
                : array_search($c->name, NewsCategory::DISPLAY_ORDER, true))
            ->values();

        $activeCategory = $categories->firstWhere('slug', (string) $request->query('category'));
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);

        // Top banner: always the newest video (switches by itself when one is
        // added in the admin); with no videos at all, the newest article.
        // Whatever the banner shows is shown nowhere else on this page.
        $heroVideo = Video::with('category')->newestFirst()->first();
        $heroArticle = $heroVideo ? null : News::visible()->with('category')->newestFirst()->first();

        // Every visible article that matches the filter/search (for the result count).
        $matching = News::query()
            ->visible()
            ->when($activeCategory, fn ($q) => $q->where('news_category_id', $activeCategory->id))
            ->search($search);

        // The listing (featured story + grid) leaves out the banner article on
        // EVERY page, with or without filter/search. The set of listed items is
        // the same for every page, so offsets don't shift and page 2+ miss
        // nothing; the banner article itself is in the banner on every page.
        $base = (clone $matching)
            ->with('category')
            ->when($heroArticle, fn ($q) => $q->whereKeyNot($heroArticle->id))
            ->newestFirst();

        // The featured story is left out of the grid on every page (not only
        // page 1), so page offsets stay stable and no card repeats on page 2.
        // "Feature on top" wins; otherwise the newest article leads.
        $lead = $search === ''
            ? ((clone $base)->where('is_featured', true)->first() ?? (clone $base)->first())
            : null;

        $news = (clone $base)
            ->when($lead, fn ($q) => $q->whereKeyNot($lead->id))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $featured = $news->onFirstPage() ? $lead : null;

        // Videos strip: every video except the one in the banner.
        $videos = Video::with('category')
            ->when($heroVideo, fn ($q) => $q->whereKeyNot($heroVideo->id))
            ->latest()->orderByDesc('id')->take(8)->get();

        // A search hit that is in the banner still counts as a result.
        $resultInBanner = $heroArticle && (clone $matching)->whereKey($heroArticle->id)->exists();

        return view('pages.news-events', [
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'search' => $search,
            'featured' => $featured,
            'news' => $news,
            'resultCount' => $news->total() + ($lead ? 1 : 0) + ($resultInBanner ? 1 : 0),
            'resultInBanner' => $resultInBanner,
            'videos' => $videos,
            'heroVideo' => $heroVideo,
            'heroArticle' => $heroArticle,
        ]);
    }

    /**
     * One article.
     * - Drafts and not-yet-due scheduled articles: 404 for visitors; a logged-in
     *   admin sees a "preview" (not indexed by search engines).
     * - An old address (English URL title changed) or a hidden duplicate: 301
     *   to the current address.
     */
    public function show(Request $request, $slug)
    {
        $newsItem = News::with('category')->where('slug', $slug)->first();
        $isAdmin = (bool) $request->user()?->is_admin;

        if (! $newsItem) {
            $target = $this->redirectTarget((string) $slug);
            abort_unless($target, 404);

            return redirect()->route('news.show', $target->slug, 301);
        }

        $isPreview = ! $newsItem->isVisible();
        abort_if($isPreview && ! $isAdmin, 404);

        $related = News::visible()->with('category')
            ->where('news_category_id', $newsItem->news_category_id)
            ->whereKeyNot($newsItem->id)
            ->newestFirst()
            ->take(3)
            ->get();

        if ($related->count() < 3) {
            $related = $related->concat(
                News::visible()->with('category')
                    ->whereKeyNot($newsItem->id)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->newestFirst()
                    ->take(3 - $related->count())
                    ->get()
            );
        }

        return view('pages.news-detail', compact('newsItem', 'related', 'isPreview'));
    }

    /** The visible article an old slug should send visitors to, if any. */
    private function redirectTarget(string $slug): ?News
    {
        $redirect = NewsSlugRedirect::where('old_slug', $slug)->first();
        if ($redirect) {
            return News::visible()->find($redirect->news_id);
        }

        $hidden = News::onlyTrashed()->where('slug', $slug)->whereNotNull('duplicate_of_id')->first();

        return $hidden ? News::visible()->find($hidden->duplicate_of_id) : null;
    }

    // Shows a single video detail page
    public function showVideo($slug)
    {
        $videoItem = Video::where('slug', $slug)->with('category')->firstOrFail();

        return view('pages.video-detail', compact('videoItem'));
    }

    // Not routed (routes/api.php isn't loaded); kept as it was.
    public function apiLatest()
    {
        $news = News::visible()->with('category')->newestFirst()->take(3)->get();
        $videos = Video::with('category')->latest()->take(2)->get();
        $combined = $news->concat($videos)->sortByDesc('created_at');

        return response()->json($combined);
    }
}
