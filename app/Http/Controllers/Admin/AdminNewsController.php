<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsRequest;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsSlugRedirect;
use App\Support\FormSubmissionToken;
use App\Support\NewsBody;
use App\Support\NewsImageProcessor;
use App\Support\NewsSlugs;
use App\Support\NewsTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdminNewsController extends Controller
{
    private const FORM = 'admin-news';

    public function __construct(private readonly NewsImageProcessor $images)
    {
    }

    /** Filter value for articles hidden as duplicates (soft-deleted, with duplicate_of_id). */
    public const FILTER_HIDDEN_DUPLICATES = 'hidden-duplicates';

    /** List with search (title, address) and a status filter, incl. "Hidden duplicates". */
    public function index(Request $request)
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);
        $filters = [...News::STATUSES, self::FILTER_HIDDEN_DUPLICATES];
        $status = in_array($request->query('status'), $filters, true) ? $request->query('status') : null;
        $hiddenView = $status === self::FILTER_HIDDEN_DUPLICATES;

        $news = ($hiddenView ? News::onlyTrashed()->whereNotNull('duplicate_of_id') : News::query())
            ->with(['category', 'duplicateOf'])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('slug', 'like', $like));
            })
            ->when($status && ! $hiddenView, fn ($q) => $q->where('status', $status))
            ->orderByRaw('published_at is null desc') // drafts first
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.news.index', [
            'news' => $news,
            'search' => $search,
            'status' => $status,
            'hiddenView' => $hiddenView,
            'hiddenCount' => News::onlyTrashed()->whereNotNull('duplicate_of_id')->count(),
            'duplicateCheck' => $this->duplicateCheckSummary(),
        ]);
    }

    /**
     * Show a hidden duplicate again. Its address stops redirecting to the kept
     * article. (The duplicate check may list it again until the titles differ
     * or the pair is added to config news.duplicates.not_duplicates.)
     */
    public function restore(News $news): RedirectResponse
    {
        abort_unless($news->trashed() && $news->duplicate_of_id, 404);

        DB::transaction(function () use ($news) {
            $news->restore();
            $news->forceFill(['duplicate_of_id' => null])->save();
            NewsSlugRedirect::where('old_slug', $news->slug)->delete();
        });

        return redirect()->route('admin.news.index', ['status' => self::FILTER_HIDDEN_DUPLICATES])
            ->with('success', "Restored: \"{$news->title}\" is visible again at /news/{$news->slug}.");
    }

    /** The latest scheduled dry-run report of news:clean-duplicates (read only). */
    public function duplicatesReport()
    {
        $path = config('news.duplicates.report');
        $disk = Storage::disk('local');

        return view('admin.news.duplicates-report', [
            'report' => $disk->exists($path) ? $disk->get($path) : null,
        ]);
    }

    private function duplicateCheckSummary(): ?array
    {
        $path = config('news.duplicates.report').'.json';
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return null;
        }
        $data = json_decode((string) $disk->get($path), true);

        return is_array($data) ? $data : null;
    }

    public function create()
    {
        return view('admin.news.create', [
            'news' => new News(['language' => 'am']),
            'categories' => $this->categories(),
            'formToken' => FormSubmissionToken::issue(self::FORM),
        ]);
    }

    public function store(NewsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $token = $data['form_token'];

        if ($guard = $this->guardSubmission($token, $request, null)) {
            return $guard;
        }

        $imagePaths = $this->processImage($request, $token);
        if ($imagePaths instanceof RedirectResponse) {
            return $imagePaths;
        }

        $news = DB::transaction(function () use ($data, $imagePaths) {
            $news = new News();
            $this->fill($news, $data, $imagePaths);
            $news->slug = 'pending-'.bin2hex(random_bytes(12)); // replaced below, once the id is known
            $news->save();
            NewsSlugs::apply($news, NewsSlugs::desiredFor($news), false);
            $this->keepSingleFeatured($news);

            return $news;
        });

        FormSubmissionToken::remember(self::FORM, $token, $news->id);

        return redirect()->route('admin.news.index')->with('success', $this->savedMessage($news, 'created'));
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', [
            'news' => $news,
            'categories' => $this->categories(),
            'formToken' => FormSubmissionToken::issue(self::FORM),
        ]);
    }

    public function update(NewsRequest $request, News $news): RedirectResponse
    {
        $data = $request->validated();
        $token = $data['form_token'];

        if ($guard = $this->guardSubmission($token, $request, $news)) {
            return $guard;
        }

        $imagePaths = $this->processImage($request, $token);
        if ($imagePaths instanceof RedirectResponse) {
            return $imagePaths;
        }

        $wasPublic = $news->isVisible();
        $urlTitleChanged = NewsSlugs::fromUrlTitle($data['url_title'] ?? null) !== NewsSlugs::fromUrlTitle($news->url_title);

        DB::transaction(function () use ($news, $data, $imagePaths, $wasPublic, $urlTitleChanged) {
            $this->fill($news, $data, $imagePaths);
            $news->save();
            // The slug changes only when the English URL title changes.
            if ($urlTitleChanged) {
                NewsSlugs::apply($news, NewsSlugs::desiredFor($news), $wasPublic);
            }
            $this->keepSingleFeatured($news);
        });

        FormSubmissionToken::remember(self::FORM, $token, $news->id);

        return redirect()->route('admin.news.index')->with('success', $this->savedMessage($news, 'updated'));
    }

    /** Hides the article (soft delete); it stays in the database. */
    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return redirect()->route('admin.news.index')->with('success', 'Article deleted (hidden from the site; it is kept in the database).');
    }

    /** Live "similar title" hint while the admin types (JSON, admin only). */
    public function similar(Request $request): JsonResponse
    {
        $title = mb_substr(trim((string) $request->query('title')), 0, 255);
        $except = $request->integer('except') ?: null;

        return response()->json([
            'matches' => mb_strlen($title) < 4 ? [] : $this->describe(NewsTitle::findSimilar($title, $except)),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Double-submit and duplicate checks, in this order:
     * 1. the same form already saved -> go to what it saved;
     * 2. an identical title created in the last 10 minutes -> blocked;
     * 3. similar titles -> warning, the admin must tick "save anyway";
     * 4. claim the one-time token (atomic) -> a parallel second request is refused.
     */
    private function guardSubmission(string $token, NewsRequest $request, ?News $news): ?RedirectResponse
    {
        if (! FormSubmissionToken::wasIssued(self::FORM, $token)) {
            return back()->withInput()->withErrors(['form_token' => 'The form has expired. Please reload the page and try again.']);
        }

        if ($savedId = FormSubmissionToken::resultOf(self::FORM, $token)) {
            return redirect()->route('admin.news.edit', $savedId)
                ->with('error', 'This form was already saved once, so it was not saved again. Here is the saved article.');
        }

        $title = (string) $request->input('title');
        $titleChanged = ! $news || NewsTitle::normalize($title) !== NewsTitle::normalize($news->title);

        if (! $news) {
            $minutes = (int) config('news.duplicate_block_minutes');
            if ($dup = NewsTitle::recentIdentical($title, $minutes)) {
                return back()->withInput()
                    ->withErrors(['title' => "An article with this exact title was created {$dup->created_at->diffForHumans()}. It was not saved again. If this is a different article, change the title."])
                    ->with('similar', $this->describe(collect([$dup])));
            }
        }

        if ($titleChanged && ! $request->boolean('confirm_similar')) {
            $similar = NewsTitle::findSimilar($title, $news?->id);
            if ($similar->isNotEmpty()) {
                return back()->withInput()
                    ->withErrors(['title' => 'An article with a similar title already exists. Check the list below. If yours is a different article, tick "This is a different article" and save again.'])
                    ->with('similar', $this->describe($similar));
            }
        }

        if (! FormSubmissionToken::claim(self::FORM, $token)) {
            return redirect()->route('admin.news.index')
                ->with('error', 'This form is already being saved. It was not saved a second time.');
        }

        return null;
    }

    /** @return array<string, string>|RedirectResponse|array{} */
    private function processImage(NewsRequest $request, string $token): array|RedirectResponse
    {
        if (! $request->hasFile('image')) {
            return [];
        }
        try {
            return $this->images->storeUpload($request->file('image'));
        } catch (RuntimeException $e) {
            FormSubmissionToken::release(self::FORM, $token);
            Log::warning('News image could not be processed', ['reason' => $e->getMessage()]);

            return back()->withInput()->withErrors(['image' => $e->getMessage().' Please try a different image.']);
        }
    }

    private function fill(News $news, array $data, array $imagePaths): void
    {
        $body = NewsBody::sanitize($data['content']);

        $news->fill([
            'title' => trim($data['title']),
            'language' => $data['language'],
            'url_title' => isset($data['url_title']) && trim($data['url_title']) !== '' ? trim($data['url_title']) : null,
            'news_category_id' => (int) $data['news_category_id'],
            'content' => $body,
            'excerpt' => NewsBody::excerpt($body),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ]);
        $news->content_format = 'html';

        $news->status = $data['status'];
        $news->published_at = match ($data['status']) {
            News::STATUS_SCHEDULED => NewsRequest::parseAdminTime($data['published_at']),
            // Re-saving a public article keeps its date; publishing a draft or a
            // not-yet-due scheduled article uses "now".
            News::STATUS_PUBLISHED => $news->exists && $news->getOriginal('status') !== News::STATUS_DRAFT
                && $news->published_at && $news->published_at->lte(now())
                ? $news->published_at
                : now(),
            default => $news->exists ? $news->published_at : null,
        };

        foreach ($imagePaths as $column => $path) {
            $news->{$column} = $path;
        }
    }

    /**
     * "Feature on top" is a single slot: ticking it on a visible article clears
     * it on the others. A draft or a not-yet-due scheduled article keeps its tick
     * without removing the current featured story; when it goes live, the newest
     * featured article leads (see NewsDisplayController).
     */
    private function keepSingleFeatured(News $news): void
    {
        if ($news->is_featured && $news->isVisible()) {
            News::whereKeyNot($news->id)->where('is_featured', true)->update(['is_featured' => false]);
        }
    }

    private function savedMessage(News $news, string $verb): string
    {
        return match (true) {
            $news->status === News::STATUS_DRAFT => "Draft {$verb}. It is not visible on the site.",
            $news->isVisible() => "Article {$verb} and visible on the site.",
            default => "Article {$verb}. It will appear on the site on "
                .$news->published_at->copy()->setTimezone(config('news.admin_timezone'))->format('M j, Y \a\t H:i').' (Addis Ababa time).',
        };
    }

    private function categories()
    {
        $order = NewsCategory::DISPLAY_ORDER;

        return NewsCategory::whereIn('name', $order)->get()
            ->sortBy(fn ($c) => array_search($c->name, $order, true))
            ->values();
    }

    private function describe($items): array
    {
        return collect($items)->map(fn (News $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'status' => $n->status,
            'date' => optional($n->published_at ?? $n->created_at)->format('M j, Y'),
            'edit_url' => route('admin.news.edit', $n->id),
            'public_url' => $n->isVisible() ? route('news.show', $n->slug) : null,
        ])->values()->all();
    }
}
