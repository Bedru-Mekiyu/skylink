<?php

namespace Tests\Feature\Content;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NewsPagesTest extends TestCase
{
    use RefreshDatabase;

    private NewsCategory $news;
    private NewsCategory $innovations;
    private NewsCategory $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->news = NewsCategory::create(['name' => 'News']);
        $this->innovations = NewsCategory::create(['name' => 'Innovations']);
        $this->events = NewsCategory::create(['name' => 'Events']);
    }

    private function article(string $title, NewsCategory $category, string $date, string $content = 'Body text.'): News
    {
        $item = new News([
            'title' => $title,
            'news_category_id' => $category->id,
            'content' => $content,
        ]);
        $item->forceFill([
            'slug' => \Illuminate\Support\Str::slug($title).'-'.Carbon::parse($date)->timestamp,
            'status' => News::STATUS_PUBLISHED,
            'published_at' => $date,
            'created_at' => $date,
            'updated_at' => $date,
        ])->save();

        return $item;
    }

    public function test_category_chips_come_from_the_database_and_filter_by_url(): void
    {
        $this->article('Opening event', $this->events, '2025-09-01');
        $this->article('Company update', $this->news, '2025-09-02');

        $response = $this->get('/news?category=events');

        $response->assertOk()
            ->assertSee('Opening event')
            ->assertSee('Innovation') // label shown for the "Innovations" DB row
            ->assertSee('href="'.url('/news?category=events').'"', false);

        // The filtered list holds only Events. (The top banner is page-wide and
        // may show the newest item of any category, so check the list itself.)
        $listed = collect($response->viewData('news')->items())->push($response->viewData('featured'))->filter()->pluck('title')->all();
        $this->assertContains('Opening event', $listed);
        $this->assertNotContains('Company update', $listed);

        // Exactly one chip is active, and it is the Events chip.
        $html = $response->getContent();
        $this->assertSame(1, preg_match_all('/<a [^>]*aria-current="page"/', $html));
        $this->assertMatchesRegularExpression('#category=events"\s+aria-current="page"#', $html);
        $this->assertSame($this->events->id, $response->viewData('activeCategory')->id);
    }

    public function test_unknown_category_falls_back_to_all_content(): void
    {
        $this->article('Company update', $this->news, '2025-09-02');
        $this->article('Opening event', $this->events, '2025-09-01');

        $this->get('/news?category=does-not-exist')
            ->assertOk()
            ->assertSee('Company update')
            ->assertSee('Opening event');
    }

    public function test_first_page_features_the_newest_article_and_grid_holds_nine(): void
    {
        // A video fills the /news banner (as on the real site), so the listing starts with the newest article.
        \App\Models\Video::create(['title' => 'Banner video', 'slug' => 'banner-video', 'video_url' => 'https://youtu.be/G_ci2p1-3N4', 'news_category_id' => $this->news->id]);
        for ($i = 1; $i <= 12; $i++) {
            $this->article("Story number {$i}", $this->news, '2025-01-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $page1 = $this->get('/news')->assertOk();
        $this->assertSame(1, $page1->viewData('featured') ? 1 : 0);
        $this->assertSame('Story number 12', $page1->viewData('featured')->title);
        $this->assertCount(9, $page1->viewData('news')->items());
        $this->assertNotContains('Story number 12', collect($page1->viewData('news')->items())->pluck('title'));

        $page2 = $this->get('/news?page=2')->assertOk();
        $this->assertNull($page2->viewData('featured'));
        $this->assertCount(2, $page2->viewData('news')->items());
    }

    public function test_pagination_links_keep_the_category(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->article("Event {$i}", $this->events, '2025-02-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $this->get('/news?category=events')
            ->assertOk()
            ->assertSee('category=events&amp;page=2', false);
    }

    public function test_search_matches_title_and_amharic_text_and_reports_empty_results(): void
    {
        $this->article('Dire Dawa digitisation project', $this->news, '2025-03-01');
        $this->article('ስካይ ሊንክ ቴክኖሎጂስ', $this->news, '2025-03-02', 'የአማርኛ ጽሑፍ');
        $this->article('Unrelated', $this->news, '2025-03-03');

        $response = $this->get('/news?q=Dire')->assertOk();
        $this->assertNull($response->viewData('featured'));
        $this->assertSame(['Dire Dawa digitisation project'], collect($response->viewData('news')->items())->pluck('title')->all());

        $this->get('/news?q='.urlencode('ቴክኖሎጂስ'))->assertOk()->assertSee('ስካይ ሊንክ ቴክኖሎጂስ');

        $empty = $this->get('/news?q=nothing-matches-this')->assertOk();
        $this->assertSame(0, $empty->viewData('resultCount'));
    }

    public function test_search_input_is_escaped_and_wildcards_are_literal(): void
    {
        $this->article('Plain title', $this->news, '2025-03-01');

        $this->get('/news?q='.urlencode('<script>alert(1)</script>'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);

        $response = $this->get('/news?q=%25')->assertOk();
        $this->assertSame(0, $response->viewData('resultCount'));
    }

    public function test_hidden_duplicate_is_not_listed_and_its_url_redirects_to_the_kept_post(): void
    {
        $kept = $this->article('Minber TV', $this->news, '2025-10-09');
        $duplicate = $this->article('Minber TV copy', $this->news, '2025-10-10');
        $duplicate->forceFill(['duplicate_of_id' => $kept->id])->save();
        $duplicate->delete();

        $this->assertNotNull(News::withTrashed()->find($duplicate->id), 'duplicate must be kept in the database');

        $this->get('/news')->assertOk()->assertDontSee('Minber TV copy');
        $this->get('/news/'.$duplicate->slug)->assertRedirect('/news/'.$kept->slug)->assertStatus(301);
    }

    public function test_deleted_article_without_duplicate_link_returns_404(): void
    {
        $gone = $this->article('Gone', $this->news, '2025-10-09');
        $gone->delete();

        $this->get('/news/'.$gone->slug)->assertNotFound();
        $this->get('/news/no-such-article')->assertNotFound();
    }

    public function test_article_page_has_one_heading_language_share_links_and_related(): void
    {
        $article = $this->article('ስካይ ሊንክ ዜና', $this->news, '2025-05-01', 'የአማርኛ ጽሑፍ');
        $this->article('Related one', $this->news, '2025-04-01');

        $response = $this->get('/news/'.$article->slug)->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $response->assertSee('lang="am"', false)
            ->assertSee('t.me/share', false)
            ->assertSee('facebook.com/sharer', false)
            ->assertSee('linkedin.com', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('Related one');
    }
}
