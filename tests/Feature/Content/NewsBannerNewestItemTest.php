<?php

namespace Tests\Feature\Content;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The /news banner shows the single newest published item of either kind
 * (article or video), and that item is not repeated below it.
 */
class NewsBannerNewestItemTest extends TestCase
{
    use RefreshDatabase;

    private NewsCategory $news;

    protected function setUp(): void
    {
        parent::setUp();
        $this->news = NewsCategory::create(['name' => 'News']);
    }

    private function article(string $title, ?string $publishedAt, array $extra = []): News
    {
        $n = new News(['title' => $title, 'news_category_id' => $this->news->id, 'content' => 'Body of '.$title]);
        $n->forceFill(array_merge([
            'slug' => Str::slug($title), 'status' => 'published', 'published_at' => $publishedAt,
            'image' => 'news/web/'.Str::slug($title).'.webp',
        ], $extra))->save();

        return $n;
    }

    private function video(string $title, string $createdAt): Video
    {
        $v = Video::create(['title' => $title, 'slug' => Str::slug($title), 'video_url' => 'https://youtu.be/G_ci2p1-3N4', 'news_category_id' => $this->news->id]);
        $v->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $v;
    }

    private function hero(string $uri = '/news'): string
    {
        $html = $this->get($uri)->assertOk()->getContent();
        $start = strpos($html, '<section class="news-hero');
        $this->assertNotFalse($start, 'the banner is on the page');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    /** Titles listed below the banner on one page: featured story + grid. */
    private function listed($response): array
    {
        return collect($response->viewData('news')->items())->prepend($response->viewData('featured'))->filter()->pluck('title')->all();
    }

    public function test_a_newer_article_takes_the_banner_from_an_older_video(): void
    {
        $this->video('Older video', '2026-11-01 10:00:00');
        $this->article('Anniversary article', '2026-11-18 10:00:00');

        $hero = $this->hero();
        $this->assertStringContainsString('Anniversary article', $hero);
        $this->assertStringContainsString('Read article', $hero);
        $this->assertStringContainsString('href="'.route('news.show', 'anniversary-article').'"', $hero);
        $this->assertStringContainsString('storage/news/web/anniversary-article.webp', $hero);
        $this->assertStringNotContainsString('Older video', $hero);
    }

    public function test_a_newer_video_keeps_the_video_banner_as_before(): void
    {
        $this->article('Older article', '2026-11-01 10:00:00');
        $this->video('Newest video', '2026-11-18 10:00:00');

        $hero = $this->hero();
        $this->assertStringContainsString('Newest video', $hero);
        $this->assertStringNotContainsString('Latest video', $hero);
        $this->assertStringContainsString('data-platform="youtube"', $hero);
        $this->assertStringNotContainsString('Older article', $hero);
    }

    public function test_publishing_anything_newer_replaces_the_banner_by_itself(): void
    {
        $this->article('First article', '2026-11-01 10:00:00');
        $this->assertStringContainsString('First article', $this->hero());

        $this->video('Brand new video', '2026-11-05 10:00:00');
        $this->assertStringContainsString('Brand new video', $this->hero());

        $this->article('Even newer article', '2026-11-09 10:00:00');
        $hero = $this->hero();
        $this->assertStringContainsString('Even newer article', $hero);
        $this->assertStringNotContainsString('Brand new video', $hero);
    }

    public function test_drafts_future_scheduled_and_deleted_articles_never_take_the_banner(): void
    {
        $this->video('Current video', '2026-11-01 10:00:00');
        $this->article('Draft article', null, ['status' => News::STATUS_DRAFT]);
        $this->article('Future article', now()->addDays(3)->toDateTimeString(), ['status' => News::STATUS_SCHEDULED]);
        $this->article('Deleted article', now()->subMinute()->toDateTimeString())->delete();

        $hero = $this->hero();
        $this->assertStringContainsString('Current video', $hero);
        foreach (['Draft article', 'Future article', 'Deleted article'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $hero);
        }
    }

    public function test_equal_times_fall_back_to_created_at_then_id(): void
    {
        $older = $this->article('Created earlier', '2026-11-10 10:00:00', ['created_at' => '2026-11-01 08:00:00']);
        $newer = $this->article('Created later', '2026-11-10 10:00:00', ['created_at' => '2026-11-02 08:00:00']);

        $this->assertStringContainsString('Created later', $this->hero());
        $this->assertNotEquals($older->id, $newer->id);
    }

    public function test_the_banner_article_is_not_repeated_in_the_featured_slot_or_grid(): void
    {
        $this->video('A video', '2026-11-01 10:00:00');
        $this->article('Banner article', '2026-11-18 10:00:00');
        $this->article('Second article', '2026-11-10 10:00:00');
        $this->article('Third article', '2026-11-05 10:00:00');

        $response = $this->get('/news');
        $this->assertSame('Second article', $response->viewData('featured')->title);
        $this->assertSame(['Second article', 'Third article'], $this->listed($response));
        // The video is not the banner now, so it stays in the videos strip.
        $this->assertSame(['A video'], $response->viewData('videos')->pluck('title')->all());
    }

    public function test_search_filter_and_later_pages_keep_the_same_banner(): void
    {
        $this->video('A video', '2026-11-01 10:00:00');
        $this->article('Banner article', '2026-11-18 10:00:00');
        for ($i = 1; $i <= 14; $i++) {
            $this->article("Story {$i}", '2026-10-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).' 10:00:00');
        }

        foreach (['/news?q=Story', '/news?category=news', '/news?page=2'] as $uri) {
            $this->assertStringContainsString('Banner article', $this->hero($uri), $uri);
            $this->assertNotContains('Banner article', $this->listed($this->get($uri)), $uri);
        }
    }
}
