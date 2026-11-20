<?php

namespace Tests\Feature\Content;

use App\Models\NewsCategory;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Video players: the right player per link, the "Watch on ..." fallback, and the admin accepting only playable links. */
class VideoPlayerTest extends TestCase
{
    use RefreshDatabase;

    private function video(string $url, string $title = 'A video', ?string $thumb = null): Video
    {
        $cat = NewsCategory::firstOrCreate(['name' => 'News']);
        $v = new Video(['title' => $title, 'description' => 'About it', 'video_url' => $url, 'news_category_id' => $cat->id]);
        $v->forceFill(['slug' => str($title)->slug().'-'.uniqid(), 'thumbnail_image' => $thumb])->save();

        return $v;
    }

    public function test_a_tiktok_video_page_has_the_player_the_guard_and_a_watch_on_tiktok_link(): void
    {
        $v = $this->video('https://www.tiktok.com/@skylink_technology/video/7525050791875464453?is_from_webapp=1', 'Tik', 'videos/t.jpg');

        $html = $this->get('/videos/'.$v->slug)->assertOk()->getContent();

        $this->assertStringContainsString('https://www.tiktok.com/player/v1/7525050791875464453', $html);
        $this->assertStringContainsString('assets/js/sky-video-guard.js', $html);
        $this->assertStringContainsString('assets/css/sky-video-fallback.css', $html);
        $this->assertStringContainsString('data-sky-player', $html);
        $this->assertStringContainsString('data-thumb="'.asset('storage/videos/t.jpg').'"', $html);
        $this->assertMatchesRegularExpression('#href="https://www\.tiktok\.com/@skylink_technology/video/7525050791875464453[^"]*"[^>]*target="_blank"[^>]*>.*?Watch on TikTok#s', $html);
        $this->assertStringNotContainsString('Unable to load player', $html);
    }

    public function test_a_youtube_video_page_uses_the_privacy_player_and_links_to_youtube(): void
    {
        $v = $this->video('https://www.youtube.com/watch?v=8R4J33Dp2x8&list=PLabc', 'Tube');

        $html = $this->get('/videos/'.$v->slug)->assertOk()->getContent();

        $this->assertStringContainsString('https://www.youtube-nocookie.com/embed/8R4J33Dp2x8', $html);
        $this->assertStringContainsString('Watch on YouTube', $html);
    }

    public function test_a_link_the_site_cannot_embed_shows_a_fallback_button_not_an_error(): void
    {
        $v = $this->video('https://vm.tiktok.com/ZMabc123/', 'Short');

        $html = $this->get('/videos/'.$v->slug)->assertOk()->getContent();

        $this->assertStringContainsString('sky-video-fallback', $html);
        $this->assertStringContainsString('href="https://vm.tiktok.com/ZMabc123/"', $html);
        $this->assertStringNotContainsString('Could not display video', $html);
    }

    public function test_the_news_banner_player_carries_the_thumbnail_and_loads_the_guard(): void
    {
        $this->video('https://www.tiktok.com/@u/video/7699040813904448774', 'Banner video', 'videos/b.jpg');

        $html = $this->get('/news')->assertOk()->getContent();

        $this->assertStringContainsString('data-embed="https://www.tiktok.com/player/v1/7699040813904448774', $html);
        $this->assertStringContainsString('data-thumb="'.asset('storage/videos/b.jpg').'"', $html);
        $this->assertStringContainsString('assets/js/sky-video-guard.js', $html);
        $this->assertStringContainsString('SkyVideoGuard.watch', $html);
    }

    public function test_the_guard_script_listens_for_the_tiktok_player_errors(): void
    {
        $js = file_get_contents(public_path('assets/js/sky-video-guard.js'));

        $this->assertStringContainsString('onPlayerError', $js);
        $this->assertStringContainsString('onPlayerReady', $js);
        $this->assertStringContainsString('Watch on ', $js);
        $this->assertStringContainsString("target = '_blank'", $js);
        $this->assertStringContainsString('noopener', $js);
    }

    public static function badLinks(): array
    {
        return [
            'short tiktok link' => ['https://vm.tiktok.com/ZMabc123/'],
            'tiktok profile, not a video' => ['https://www.tiktok.com/@skylink_technology'],
            'youtube channel' => ['https://www.youtube.com/@skylink_technologie'],
            'another website' => ['https://vimeo.com/123456789'],
            'not a link' => ['hello world'],
        ];
    }

    #[DataProvider('badLinks')]
    public function test_admin_rejects_links_the_site_cannot_play(string $url): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $cat = NewsCategory::create(['name' => 'News']);

        $this->actingAs($admin)->post(route('admin.videos.store'), ['title' => 'Bad link', 'video_url' => $url, 'news_category_id' => $cat->id])
            ->assertSessionHasErrors('video_url');

        $this->assertSame(0, Video::count());
    }

    public static function goodLinks(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=8R4J33Dp2x8&list=PLx'],
            'youtu.be' => ['https://youtu.be/8R4J33Dp2x8'],
            'youtube shorts' => ['https://www.youtube.com/shorts/8R4J33Dp2x8'],
            'tiktok video' => ['https://www.tiktok.com/@skylink_technology/video/7525050791875464453?lang=en'],
        ];
    }

    #[DataProvider('goodLinks')]
    public function test_admin_accepts_playable_links(string $url): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $cat = NewsCategory::create(['name' => 'News']);

        $this->actingAs($admin)->post(route('admin.videos.store'), ['title' => 'Good link', 'video_url' => $url, 'news_category_id' => $cat->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Video::count());
    }

    public function test_the_admin_form_explains_which_links_work(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get(route('admin.videos.create'))->assertOk()
            ->assertSee('Video URL (YouTube or TikTok)')->assertSee('Short TikTok links')->assertDontSee('Vimeo');
    }
}
