<?php

namespace Tests\Feature\Content;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Whatever the /news banner shows must not appear again anywhere else on /news. */
class NewsBannerDedupeTest extends TestCase
{
    use RefreshDatabase;

    private NewsCategory $news;
    private NewsCategory $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->news = NewsCategory::create(['name' => 'News']);
        NewsCategory::create(['name' => 'Innovations']);
        $this->events = NewsCategory::create(['name' => 'Events']);
    }

    private function article(string $title, NewsCategory $cat, string $date, array $extra = []): News
    {
        $n = new News(['title' => $title, 'news_category_id' => $cat->id, 'content' => 'Body of '.$title]);
        $n->forceFill(array_merge(['slug' => \Illuminate\Support\Str::slug($title), 'status' => 'published', 'published_at' => $date], $extra))->save();

        return $n;
    }

    private function video(string $title, string $date): Video
    {
        $v = Video::create(['title' => $title, 'slug' => \Illuminate\Support\Str::slug($title), 'video_url' => 'https://youtu.be/G_ci2p1-3N4', 'news_category_id' => $this->news->id]);
        $v->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

        return $v;
    }

    /** Titles listed below the banner on one page: featured story + grid. */
    private function listed($response): array
    {
        return collect($response->viewData('news')->items())
            ->prepend($response->viewData('featured'))
            ->filter()
            ->pluck('title')
            ->all();
    }

    // ----- banner shows a video ---------------------------------------------------

    public function test_the_banner_video_is_left_out_of_the_videos_strip_and_the_others_stay(): void
    {
        foreach (range(1, 10) as $i) {
            $this->video("Video {$i}", "2025-01-".str_pad((string) $i, 2, '0', STR_PAD_LEFT).' 10:00:00');
        }

        $response = $this->get('/news')->assertOk();
        $this->assertSame('Video 10', $response->viewData('heroVideo')->title);

        $strip = $response->viewData('videos')->pluck('title')->all();
        $this->assertNotContains('Video 10', $strip);
        $this->assertSame(['Video 9', 'Video 8', 'Video 7', 'Video 6', 'Video 5', 'Video 4', 'Video 3', 'Video 2'], $strip);

        // On the page, the banner video's title appears once (the banner + its pop-up title only).
        $html = $response->getContent();
        $this->assertSame(0, substr_count($html, 'class="news-video__title"><a href="'.route('videos.show', 'video-10').'"'));
        $this->assertStringContainsString('Video 9', $html);
    }

    public function test_with_only_the_banner_video_the_strip_is_hidden_without_an_empty_heading(): void
    {
        $this->video('Only video', '2025-01-01 10:00:00');

        $response = $this->get('/news')->assertOk();
        $this->assertCount(0, $response->viewData('videos'));
        $response->assertDontSee('id="news-videos-title"', false)->assertDontSee('class="news-videos"', false);
    }

    public function test_a_video_banner_does_not_change_the_article_listing(): void
    {
        // The banner is the newest item of either kind, so the video is the newest here.
        $this->video('A video', '2025-04-01 10:00:00');
        $a = $this->article('Newest article', $this->news, '2025-03-01');
        $this->article('Older article', $this->news, '2025-02-01');

        $response = $this->get('/news');
        $this->assertSame($a->id, $response->viewData('featured')->id);
        $this->assertSame(['Newest article', 'Older article'], $this->listed($response));
    }

    // ----- banner shows an article (no videos) ----------------------------------------

    public function test_the_featured_story_is_never_the_banner_article(): void
    {
        $this->article('Banner story', $this->news, '2025-05-01');
        $this->article('Second story', $this->news, '2025-04-01');
        $this->article('An event', $this->events, '2025-03-01');

        foreach (['/news', '/news?category=news'] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertSame('Banner story', $response->viewData('heroArticle')->title, $url);
            $this->assertSame('Second story', $response->viewData('featured')->title, $url);
            $this->assertNotContains('Banner story', $this->listed($response), $url);
        }

        // Another category: its own newest story leads.
        $this->assertSame('An event', $this->get('/news?category=events')->viewData('featured')->title);
    }

    public function test_a_feature_on_top_flag_on_the_banner_article_passes_to_the_next_story(): void
    {
        $this->article('Banner story', $this->news, '2025-05-01', ['is_featured' => true]);
        $this->article('Second story', $this->news, '2025-04-01');

        $this->assertSame('Second story', $this->get('/news')->viewData('featured')->title);
    }

    public function test_search_never_lists_the_banner_article_but_still_counts_it(): void
    {
        $this->article('Dire Dawa banner story', $this->news, '2025-05-01');
        $this->article('Dire Dawa second story', $this->news, '2025-04-01');
        $this->article('Unrelated', $this->news, '2025-03-01');

        $response = $this->get('/news?q=Dire')->assertOk();
        $this->assertSame(['Dire Dawa second story'], $this->listed($response));
        $this->assertSame(2, $response->viewData('resultCount'));
        $response->assertSee('2 results for')->assertSee('One of them is the story at the top of this page.');

        // The banner article as the only match: an honest message, not "no results".
        $only = $this->get('/news?q=banner')->assertOk();
        $this->assertSame([], $this->listed($only));
        $this->assertSame(1, $only->viewData('resultCount'));
        $only->assertSee('The only matching story is the one at the top of this page.')->assertDontSee('No articles match');
    }

    public function test_every_article_appears_exactly_once_across_all_pages_and_counts_are_unchanged(): void
    {
        // 21 articles: banner (1) + featured (1) + 9 on page 1 + 9 on page 2 + 1 on page 3.
        for ($i = 1; $i <= 21; $i++) {
            $this->article("Story {$i}", $i % 3 === 0 ? $this->events : $this->news, '2025-01-01 00:00:00', ['published_at' => now()->subDays(100 - $i)]);
        }

        $first = $this->get('/news')->assertOk();
        $this->assertSame('Story 21', $first->viewData('heroArticle')->title);
        // The category links show only their names, no article counts.
        $first->assertDontSee('news-chip__count', false);
        $chips = preg_match_all('/<a class="news-chip"[^>]*>(.*?)<\/a>/s', $first->getContent(), $m) ? array_map('trim', $m[1]) : [];
        $this->assertNotEmpty($chips);
        $this->assertSame('All Content', $chips[0]);
        foreach ($chips as $chip) {
            $this->assertDoesNotMatchRegularExpression('/\d/', $chip, "no count in category link \"{$chip}\"");
        }

        $seen = [];
        $lastPage = $first->viewData('news')->lastPage();
        for ($page = 1; $page <= $lastPage; $page++) {
            $response = $this->get('/news?page='.$page);
            $this->assertSame('Story 21', $response->viewData('heroArticle')->title, "banner on page {$page}");
            $seen = array_merge($seen, $this->listed($response));
        }

        $this->assertSame(3, $lastPage);
        $this->assertCount(20, $seen, 'all 20 non-banner articles are listed');
        $this->assertSame(array_unique($seen), $seen, 'no article is listed twice');
        $this->assertNotContains('Story 21', $seen, 'the banner article is not listed again');
        // Newest-first order without gaps: Story 20 (featured) down to Story 1.
        $this->assertSame(array_map(fn ($i) => "Story {$i}", range(20, 1)), $seen);
    }

    public function test_category_filter_pages_are_complete_too(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->article("News {$i}", $this->news, '2025-01-01', ['published_at' => now()->subDays(50 - $i)]);
        }

        $seen = [];
        foreach ([1, 2] as $page) {
            $seen = array_merge($seen, $this->listed($this->get("/news?category=news&page={$page}")));
        }
        $this->assertSame(array_map(fn ($i) => "News {$i}", range(11, 1)), $seen);
    }
}
