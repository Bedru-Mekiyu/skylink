<?php

namespace Tests\Feature\Content;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The /news top banner always shows the newest video (or the newest article). */
class NewsHeroTest extends TestCase
{
    use RefreshDatabase;

    private NewsCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = NewsCategory::create(['name' => 'Innovations']);
    }

    private function video(string $title, string $url, string $createdAt, ?string $thumb = null): Video
    {
        $v = Video::create([
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title).'-'.strtotime($createdAt),
            'video_url' => $url,
            'thumbnail_image' => $thumb,
            'news_category_id' => $this->category->id,
        ]);
        $v->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $v;
    }

    private function hero(): string
    {
        $html = $this->get('/news')->assertOk()->getContent();
        $start = strpos($html, '<section class="news-hero');
        $this->assertNotFalse($start, 'the banner is on the page');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    public function test_the_banner_shows_the_newest_video_by_date_not_by_id(): void
    {
        // Created in this order on purpose: the highest id is NOT the newest.
        $this->video('Newest video', 'https://www.tiktok.com/@skylink_technology/video/7699040813904448774?lang=en', '2026-11-01 15:02:40', 'videos/new.png');
        $this->video('Older video', 'https://www.youtube.com/watch?v=cSa2Z5YTbHs', '2025-08-02 02:23:52');

        $hero = $this->hero();
        $this->assertStringContainsString('Newest video', $hero);
        $this->assertStringNotContainsString('Older video', $hero);
        // No "Latest video" kind badge and no category chip above the title any more.
        $this->assertStringNotContainsString('Latest video', $hero);
        $this->assertStringNotContainsString('news-hero__chip', $hero);
        // Date in Addis Ababa time (15:02 UTC = 18:02 +03:00).
        $this->assertStringContainsString('datetime="2026-11-01T18:02:40+03:00"', $hero);
        $this->assertStringContainsString('data-platform="tiktok"', $hero);
        $this->assertStringContainsString('data-embed="https://www.tiktok.com/player/v1/7699040813904448774?', $hero);
        $this->assertStringContainsString('storage/videos/new.png', $hero);
    }

    public function test_the_banner_switches_by_itself_when_a_newer_video_is_added(): void
    {
        $this->video('First video', 'https://www.tiktok.com/@x/video/7544710443453943045', '2025-09-05 07:18:43');
        $this->assertStringContainsString('First video', $this->hero());

        $this->video('Brand new video', 'https://youtu.be/G_ci2p1-3N4', now()->toDateTimeString());

        $hero = $this->hero();
        $this->assertStringContainsString('Brand new video', $hero);
        $this->assertStringNotContainsString('First video', $hero);
        $this->assertStringContainsString('data-embed="https://www.youtube-nocookie.com/embed/G_ci2p1-3N4?', $hero);
    }

    public function test_no_player_or_third_party_script_is_loaded_with_the_page(): void
    {
        $this->video('A video', 'https://www.youtube.com/embed/cSa2Z5YTbHs', '2025-08-02 02:23:52');

        $html = $this->get('/news')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('#<iframe[^>]+(youtube|tiktok)#i', $html);
        $this->assertDoesNotMatchRegularExpression('#<script[^>]+src="https?://[^"]*(youtube|tiktok)#i', $html);
        $this->assertStringContainsString('<dialog class="news-player"', $html);
    }

    public function test_youtube_and_tiktok_links_are_recognised_and_others_are_not_embedded(): void
    {
        $cases = [
            'https://www.youtube.com/watch?v=cSa2Z5YTbHs&t=10' => ['youtube', 'cSa2Z5YTbHs'],
            'https://youtu.be/G_ci2p1-3N4' => ['youtube', 'G_ci2p1-3N4'],
            'https://www.youtube.com/embed/Sd2Fyt1zSnk' => ['youtube', 'Sd2Fyt1zSnk'],
            'https://www.youtube.com/shorts/4IO4YE1a4U0' => ['youtube', '4IO4YE1a4U0'],
            'https://www.tiktok.com/@skylink_technology/video/7546684932215540997?is_from_webapp=1' => ['tiktok', '7546684932215540997'],
            'https://evil.example/youtube.com/watch?v=cSa2Z5YTbHs' => [null, null],
            'https://vimeo.com/123456' => [null, null],
        ];
        foreach ($cases as $url => [$platform, $id]) {
            $v = new Video(['video_url' => $url]);
            $this->assertSame($platform, $v->platform, $url);
            $this->assertSame($id, $v->youtube_id ?? $v->tiktok_id, $url);
        }

        // An unknown platform: a normal link to the video, no pop-up player.
        $this->video('Vimeo clip', 'https://vimeo.com/123456', '2025-01-01');
        $hero = $this->hero();
        $this->assertStringContainsString('href="https://vimeo.com/123456"', $hero);
        $this->assertStringNotContainsString('data-news-player-open', $hero);
    }

    public function test_without_thumbnail_the_poster_style_is_used(): void
    {
        $this->video('No picture', 'https://www.tiktok.com/@x/video/7699040813904448774', '2025-01-01');

        $hero = $this->hero();
        $this->assertStringContainsString('news-hero--poster', $hero);
        $this->assertStringNotContainsString('news-hero__img', $hero);
        $this->assertStringContainsString('No picture', $hero);
    }

    public function test_without_videos_the_newest_article_is_used(): void
    {
        $older = new News(['title' => 'Older story', 'news_category_id' => $this->category->id, 'content' => 'x']);
        $older->forceFill(['slug' => 'older', 'status' => 'published', 'published_at' => '2025-01-01', 'image' => 'news/web/old.webp'])->save();
        $newer = new News(['title' => 'Newest story', 'news_category_id' => $this->category->id, 'content' => 'x']);
        $newer->forceFill(['slug' => 'newest', 'status' => 'published', 'published_at' => '2025-06-01', 'image' => 'news/web/new.webp'])->save();
        $draft = new News(['title' => 'Draft story', 'news_category_id' => $this->category->id, 'content' => 'x']);
        $draft->forceFill(['slug' => 'draft', 'status' => 'draft', 'published_at' => null])->save();

        $hero = $this->hero();
        $this->assertStringNotContainsString('Latest story', $hero);
        $this->assertStringContainsString('Newest story', $hero);
        $this->assertStringNotContainsString('Draft story', $hero);
        $this->assertStringContainsString('storage/news/web/new.webp', $hero);
        $this->assertStringContainsString('href="'.route('news.show', 'newest').'"', $hero);
        $this->assertStringContainsString('Read article', $hero);
    }

    public function test_with_nothing_at_all_the_banner_still_renders(): void
    {
        $hero = $this->hero();
        $this->assertStringContainsString('News, Innovations &amp; Events', $hero);
        $this->assertStringContainsString('news-hero--poster', $hero);
        $this->assertStringNotContainsString('Watch video', $hero);
    }

    public function test_the_page_keeps_a_single_h1(): void
    {
        $this->video('A video', 'https://youtu.be/G_ci2p1-3N4', '2025-01-01');
        $this->assertSame(1, substr_count($this->get('/news')->getContent(), '<h1'));
    }
}
