<?php

namespace Tests\Feature\Admin;

use App\Models\HeroVideoSetting;
use App\Models\User;
use App\Support\HeroVideo;
use App\Support\PublicCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Admin > Homepage Video: access, validation, replace / restore / reset, and what the homepage plays. */
class HeroVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['services.ffmpeg.disabled' => true]); // raw-upload path; same result on every machine
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    /** Tiny file with a real MP4 signature (ftyp box). */
    private function mp4(string $name = 'clip.mp4', int $pad = 64): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\0", $pad));
    }

    private function poster(string $name = 'poster.png'): UploadedFile
    {
        // 1x1 PNG: a real image without needing the GD extension.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    private function upload(array $files)
    {
        return $this->actingAs($this->admin())->put(route('admin.hero-video.update'), $files);
    }

    public function test_only_admins_can_open_or_change_it(): void
    {
        $this->get(route('admin.hero-video.edit'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.hero-video.edit'))->assertForbidden();
        $this->put(route('admin.hero-video.update'), ['desktop_mp4' => $this->mp4(), 'poster' => $this->poster()])->assertForbidden();
        $this->put(route('admin.hero-video.reset'))->assertForbidden();
        $this->assertNull(HeroVideo::row());
    }

    public function test_changes_are_put_only(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.hero-video.reset'))->assertStatus(405);
        $this->post(route('admin.hero-video.update'))->assertStatus(405);
        $this->post(route('admin.hero-video.reset'))->assertStatus(405);
        $this->get(route('admin.hero-video.edit'))->assertOk()->assertSee('Homepage Video')->assertSee('built-in video');
    }

    public function test_valid_upload_is_stored_with_random_name_and_script_lock(): void
    {
        $this->upload(['desktop_mp4' => $this->mp4('My Holiday.mp4'), 'poster' => $this->poster()])->assertRedirect(route('admin.hero-video.edit'));

        $cur = HeroVideo::row()->current;
        $this->assertMatchesRegularExpression('#^hero-video/[A-Za-z0-9]{40}\.mp4$#', $cur['desktop_mp4']);
        $this->assertStringNotContainsString('Holiday', $cur['desktop_mp4']);
        Storage::disk('public')->assertExists([$cur['desktop_mp4'], $cur['poster'], 'hero-video/.htaccess']);
        $this->assertStringContainsString('Require all denied', Storage::disk('public')->get('hero-video/.htaccess'));
    }

    public function test_homepage_uses_the_upload_then_the_default_again(): void
    {
        $this->get(route('home'))->assertSee('assets/videos/hero/hero-desktop.mp4', false);

        $this->upload(['desktop_mp4' => $this->mp4(), 'mobile_mp4' => $this->mp4('m.mp4'), 'poster' => $this->poster()]);
        $cur = HeroVideo::row()->current;
        $page = $this->get(route('home'));
        $page->assertSee('storage/'.$cur['desktop_mp4'], false)->assertSee('storage/'.$cur['mobile_mp4'], false)->assertSee('storage/'.$cur['poster'], false);
        $page->assertDontSee('assets/videos/hero/hero-desktop.mp4', false);
        $page->assertSee('autoplay muted loop playsinline', false);

        $this->put(route('admin.hero-video.reset'))->assertRedirect();
        $this->get(route('home'))->assertSee('assets/videos/hero/hero-desktop.mp4', false)->assertDontSee('storage/'.$cur['desktop_mp4'], false);
    }

    public function test_replace_keeps_previous_and_restore_swaps(): void
    {
        $this->upload(['desktop_mp4' => $this->mp4('a.mp4'), 'poster' => $this->poster()]);
        $first = HeroVideo::row()->current;
        $this->put(route('admin.hero-video.update'), ['desktop_mp4' => $this->mp4('b.mp4', 80), 'poster' => $this->poster('b.png')])->assertRedirect();

        $row = HeroVideo::row();
        $second = $row->current;
        $this->assertSame($first, $row->previous);
        $this->assertNotSame($first['desktop_mp4'], $second['desktop_mp4']);
        Storage::disk('public')->assertExists([$first['desktop_mp4'], $second['desktop_mp4']]);

        $this->put(route('admin.hero-video.restore'))->assertRedirect();
        $row = HeroVideo::row();
        $this->assertSame($first, $row->current);
        $this->assertSame($second, $row->previous);

        // A third upload drops the oldest files from disk (only two generations are kept).
        $this->put(route('admin.hero-video.update'), ['desktop_mp4' => $this->mp4('c.mp4', 90), 'poster' => $this->poster('c.png')]);
        Storage::disk('public')->assertMissing($second['desktop_mp4']);
    }

    public function test_reset_keeps_custom_video_for_restore_and_never_touches_defaults(): void
    {
        $this->upload(['desktop_mp4' => $this->mp4(), 'poster' => $this->poster()]);
        $custom = HeroVideo::row()->current;
        $this->put(route('admin.hero-video.reset'));

        $row = HeroVideo::row();
        $this->assertEmpty($row->current);
        $this->assertSame($custom, $row->previous);
        $this->assertFileExists(public_path('assets/videos/hero/hero-desktop.mp4'));

        $this->put(route('admin.hero-video.restore'));
        $this->assertSame($custom, HeroVideo::row()->current);
    }

    public function test_default_still_is_the_ceo_portrait_and_reset_brings_it_back(): void
    {
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('alt="Muhammad Karim, CEO and founder of Sky Link Technologies"', $home);
        $this->assertStringContainsString('assets/videos/hero/poster.webp', $home);
        $this->assertStringContainsString('assets/videos/hero/poster-mobile.webp', $home);
        foreach (['poster.jpg' => 250, 'poster.webp' => 250, 'poster-mobile.jpg' => 150, 'poster-mobile.webp' => 150] as $f => $kb) {
            $this->assertLessThanOrEqual($kb * 1024, filesize(public_path('assets/videos/hero/'.$f)), $f);
        }

        $this->upload(['desktop_mp4' => $this->mp4(), 'poster' => $this->poster()]);
        $this->assertStringNotContainsString('poster-mobile.webp', $this->get('/')->getContent());
        $this->put(route('admin.hero-video.reset'));
        $this->assertStringContainsString('poster-mobile.webp', $this->get('/')->getContent());
    }

    public function test_changes_bust_the_public_cache(): void
    {
        $before = Cache::get('public-data-version');
        $this->upload(['desktop_mp4' => $this->mp4(), 'poster' => $this->poster()]);
        $this->assertNotSame($before, Cache::get('public-data-version'));
    }

    public function test_dangerous_or_fake_files_are_rejected(): void
    {
        $bad = [
            'double extension' => ['desktop_mp4' => $this->mp4('evil.php.mp4'), 'poster' => $this->poster()],
            'php renamed to mp4' => ['desktop_mp4' => UploadedFile::fake()->createWithContent('x.mp4', '<?php echo 1;'), 'poster' => $this->poster()],
            'html as mp4' => ['desktop_mp4' => UploadedFile::fake()->createWithContent('x.mp4', '<html><script>1</script></html>'), 'poster' => $this->poster()],
            'avi video' => ['desktop_mp4' => UploadedFile::fake()->createWithContent('x.avi', 'RIFF....AVI '), 'poster' => $this->poster()],
            'svg poster' => ['desktop_mp4' => $this->mp4(), 'poster' => UploadedFile::fake()->createWithContent('p.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')],
            'php poster' => ['desktop_mp4' => $this->mp4(), 'poster' => UploadedFile::fake()->createWithContent('p.php.png', '<?php echo 1;')],
            'text as jpg poster' => ['desktop_mp4' => $this->mp4(), 'poster' => UploadedFile::fake()->createWithContent('p.png', 'just text')],
            'webm with mp4 bytes' => ['desktop_mp4' => $this->mp4(), 'desktop_webm' => $this->mp4('x.webm'), 'poster' => $this->poster()],
        ];
        foreach ($bad as $label => $files) {
            $this->actingAs($this->admin())->putJson(route('admin.hero-video.update'), $files)->assertStatus(422);
            $this->assertNull(HeroVideo::row(), $label.' must not be saved');
        }
        $this->assertSame([], Storage::disk('public')->allFiles(), 'rejected uploads leave no files');
    }

    public function test_oversize_file_is_rejected(): void
    {
        $big = UploadedFile::fake()->createWithContent('big.mp4', "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom")->size(70 * 1024);
        $this->actingAs($this->admin())->putJson(route('admin.hero-video.update'), ['desktop_mp4' => $big, 'poster' => $this->poster()])->assertStatus(422)->assertJsonValidationErrors('desktop_mp4');
    }

    public function test_first_upload_needs_the_desktop_video_and_a_poster_without_ffmpeg(): void
    {
        $this->actingAs($this->admin());
        $this->putJson(route('admin.hero-video.update'), ['poster' => $this->poster()])->assertStatus(422)->assertJsonValidationErrors('desktop_mp4');
        $this->putJson(route('admin.hero-video.update'), ['desktop_mp4' => $this->mp4()])->assertStatus(422)->assertJsonValidationErrors('poster');
        $this->putJson(route('admin.hero-video.update'), [])->assertStatus(422);
        $this->assertNull(HeroVideo::row());
    }

    public function test_uploads_are_rate_limited(): void
    {
        $this->actingAs($this->admin());
        for ($i = 0; $i < 10; $i++) {
            $this->putJson(route('admin.hero-video.update'), [])->assertStatus(422);
        }
        $this->putJson(route('admin.hero-video.update'), [])->assertStatus(429);
    }

    public function test_missing_files_fall_back_to_the_default_homepage_video(): void
    {
        HeroVideoSetting::create(['current' => ['desktop_mp4' => 'hero-video/gone.mp4']]);
        $this->assertNull(HeroVideo::current());
        $this->get(route('home'))->assertOk()->assertSee('assets/videos/hero/hero-desktop.mp4', false);
    }
}
