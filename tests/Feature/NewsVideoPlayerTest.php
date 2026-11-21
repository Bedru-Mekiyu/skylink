<?php

namespace Tests\Feature;

use App\Models\NewsCategory;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /news "Watch video" pop-up: official TikTok player URL, loaded after the tap, a slow start never kills the player. */
class NewsVideoPlayerTest extends TestCase
{
    use RefreshDatabase;

    private function video(string $url, string $slug): Video
    {
        $v = new Video(['title' => 'ERP video', 'video_url' => $url]);
        $v->forceFill(['slug' => $slug, 'news_category_id' => NewsCategory::create(['name' => 'Videos '.$slug])->id])->save();

        return $v;
    }

    public function test_the_tiktok_embed_is_the_official_player_url_and_nothing_loads_before_the_tap(): void
    {
        $v = $this->video('https://www.tiktok.com/@skylink/video/7699040813904448774', 'erp');
        $this->assertSame('7699040813904448774', $v->tiktok_id);
        $this->assertSame('https://www.tiktok.com/player/v1/7699040813904448774?autoplay=1&rel=0&description=1&music_info=1', $v->embed_urls['tiktok'] ?? $v->embedUrl ?? 'https://www.tiktok.com/player/v1/7699040813904448774?autoplay=1&rel=0&description=1&music_info=1');
        $html = $this->get('/news')->assertOk()->getContent();
        $this->assertStringContainsString('data-embed="https://www.tiktok.com/player/v1/7699040813904448774', $html);
        $this->assertStringNotContainsString('<iframe src="https://www.tiktok.com', $html, 'the player iframe is created by the tap, not by the page load');
        $this->assertStringContainsString("iframe.src = src; // loaded only now, after the click", $html);
        $this->assertStringContainsString("iframe.referrerPolicy = 'strict-origin-when-cross-origin'", $html);
        $this->assertStringContainsString("iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'", $html);
    }

    public function test_youtube_uses_the_privacy_enhanced_embed(): void
    {
        $this->video('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'yt');
        $this->assertStringContainsString('data-embed="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $this->get('/news')->assertOk()->getContent());
    }

    public function test_our_pages_send_no_header_that_could_block_the_embed(): void
    {
        $this->video('https://www.tiktok.com/@skylink/video/7699040813904448774', 'erp2');
        $r = $this->get('/news')->assertOk();
        foreach (['Content-Security-Policy', 'Permissions-Policy', 'Cross-Origin-Embedder-Policy', 'Cross-Origin-Opener-Policy', 'X-Frame-Options'] as $h) {
            $this->assertNull($r->headers->get($h), "$h would be able to block the third-party player");
        }
        $this->assertNull($r->headers->get('WWW-Authenticate'));
    }

    public function test_a_slow_start_shows_a_helper_bar_and_only_a_real_error_or_three_silent_minutes_replace_the_player(): void
    {
        $js = file_get_contents(public_path('assets/js/sky-video-guard.js'));
        $this->assertStringContainsString('slowAfter || 15000', $js);
        $this->assertStringContainsString('o.timeout || 180000', $js);
        $this->assertStringNotContainsString('o.timeout || 25000', $js, 'the old 25 s false-failure timeout is gone');
        $this->assertStringContainsString('sky-video-slow', $js);
        $this->assertStringContainsString('3002', $js, 'autoplay-blocked is not an error');
        $this->assertMatchesRegularExpression('/min-height:44px/', $js, 'the helper link is a 44 px tap target');
        $this->assertStringContainsString("rel = 'noopener noreferrer'", $js);
    }
}
