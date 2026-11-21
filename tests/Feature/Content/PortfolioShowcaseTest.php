<?php

namespace Tests\Feature\Content;

use App\Models\Portfolio;
use App\Models\User;
use App\Support\ShowcaseVideos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** /portfolio banner video, card clips, and the admin video fields. */
class PortfolioShowcaseTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $title, string $created, array $extra = []): Portfolio
    {
        $p = new Portfolio(['title' => $title, 'description' => "About {$title}.", 'image' => 'portfolio_images/'.\Illuminate\Support\Str::slug($title).'.webp', 'project_url' => 'https://example.com/'.\Illuminate\Support\Str::slug($title)]);
        $p->forceFill(array_merge(['created_at' => $created, 'updated_at' => $created], $extra))->save();

        return $p;
    }

    private function withClip(): array
    {
        return ['video_mp4' => 'assets/videos/portfolio/elida.mp4', 'video_webm' => 'assets/videos/portfolio/elida.webm', 'video_poster' => 'assets/videos/portfolio/elida-poster.jpg'];
    }

    private function cardTitles($response): array
    {
        return collect($response->viewData('projects')->items())->pluck('title')->all();
    }

    private function png(): UploadedFile
    {
        $im = imagecreatetruecolor(800, 500);
        $tmp = tempnam(sys_get_temp_dir(), 'pi').'.png';
        imagepng($im, $tmp);

        return new UploadedFile($tmp, 'p.png', 'image/png', null, true);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    // ----- which project is in the banner --------------------------------------

    public function test_the_featured_project_is_the_banner_even_without_video(): void
    {
        $this->project('Old featured', '2025-01-01', ['is_featured' => true]);
        $this->project('Newest with video', '2025-06-01', $this->withClip());

        $response = $this->get('/portfolio')->assertOk();
        $this->assertSame('Old featured', $response->viewData('heroProject')->title);
        $response->assertDontSee('class="pf-hero__video"', false);
    }

    public function test_without_a_pinned_project_the_newest_project_is_the_banner_and_its_video_plays(): void
    {
        $this->project('Older without video', '2025-03-01');
        $this->project('Video project', '2025-06-01', $this->withClip());

        $response = $this->get('/portfolio')->assertOk();
        $this->assertSame('Video project', $response->viewData('heroProject')->title);
        $response->assertSee('class="pf-hero__video"', false)
            ->assertSee('preload="none"', false)
            ->assertSee('data-mp4="'.asset('assets/videos/portfolio/elida.mp4'), false)
            ->assertSee('data-webm="'.asset('assets/videos/portfolio/elida.webm'), false)
            ;
        $this->assertDoesNotMatchRegularExpression('#<video[^>]*\scontrols#', $response->getContent(), 'no visible controls');
        // No video is attached on page load (the script adds the sources).
        $this->assertDoesNotMatchRegularExpression('#<video[^>]+\ssrc=|<source#', $response->getContent());
    }

    public function test_without_any_video_the_newest_project_image_is_used(): void
    {
        $this->project('Older', '2025-01-01');
        $this->project('Newest', '2025-06-01');

        $response = $this->get('/portfolio')->assertOk();
        $this->assertSame('Newest', $response->viewData('heroProject')->title);
        $response->assertSee('class="pf-hero__poster" src="'.asset('storage/portfolio_images/newest.webp').'"', false)
            ->assertDontSee('class="pf-hero pf-hero--poster"', false);
    }

    public function test_without_video_or_image_the_poster_style_is_used_and_no_projects_still_renders(): void
    {
        $this->project('Bare', '2025-01-01', ['image' => null]);
        $this->get('/portfolio')->assertOk()->assertSee('pf-hero pf-hero--poster', false)->assertSee('Bare');

        Portfolio::query()->delete();
        $this->get('/portfolio')->assertOk()->assertSee('pf-hero--poster', false)->assertSee('No projects have been added yet.');
    }

    public function test_visit_project_opens_a_safe_link_in_a_new_tab(): void
    {
        $this->project('Linked', '2025-01-01');
        $this->get('/portfolio')->assertSee('class="pf-hero__cta" href="https://example.com/linked" target="_blank" rel="noopener noreferrer"', false);

        Portfolio::query()->update(['project_url' => 'javascript:alert(1)']);
        $this->get('/portfolio')->assertDontSee('class="pf-hero__cta"', false)->assertDontSee('javascript:alert(1)', false);
    }

    // ----- no duplicate, every project once ----------------------------------------

    public function test_the_banner_project_is_not_repeated_and_every_project_appears_once(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            $this->project("Project {$i}", '2025-01-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), $i === 4 ? $this->withClip() : []);
        }

        $first = $this->get('/portfolio')->assertOk();
        $this->assertSame('Project 9', $first->viewData('heroProject')->title, 'the newest project is the banner');
        $seen = $this->cardTitles($first);
        $this->assertNotSame('Project 9', $seen[0] ?? null, 'not the first card under the banner');
        $seen = array_merge($seen, $this->cardTitles($this->get('/portfolio?page=2')));

        $this->assertCount(8, $seen);
        $this->assertSame(array_unique($seen), $seen);
        $this->assertNotContains('Project 9', $seen);
        $this->assertSame(['Project 8', 'Project 7', 'Project 6', 'Project 5', 'Project 4', 'Project 3', 'Project 2', 'Project 1'], $seen);
    }

    public function test_a_project_added_in_the_admin_becomes_the_banner_with_or_without_video_and_a_pin_overrides_it(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->project('Old project with a video', '2025-01-01', $this->withClip());

        // new project with an image only -> it is the banner, with the image (no video element)
        $this->actingAs($admin)->post(route('admin.portfolio.store'), ['title' => 'New image project', 'description' => 'New.', 'image' => $this->png()])->assertSessionHasNoErrors();
        $page = $this->get('/portfolio')->assertOk();
        $this->assertSame('New image project', $page->viewData('heroProject')->title);
        $page->assertSee('pf-hero__poster', false)->assertDontSee('class="pf-hero__video"', false);

        // a newer project with a library clip -> it is the banner and its video plays
        $this->actingAs($admin)->post(route('admin.portfolio.store'), ['title' => 'New video project', 'description' => 'Newer.', 'image' => $this->png(), 'video_library' => 'elida'])->assertSessionHasNoErrors();
        $page = $this->get('/portfolio')->assertOk();
        $this->assertSame('New video project', $page->viewData('heroProject')->title);
        $page->assertSee('class="pf-hero__video"', false);

        // pin the old project: it overrides the newest; unpin: the newest returns
        $old = Portfolio::where('title', 'Old project with a video')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.portfolio.update', $old), ['title' => $old->title, 'description' => 'Old.', 'is_featured' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('Old project with a video', $this->get('/portfolio')->viewData('heroProject')->title);
        $this->assertSame(1, Portfolio::where('is_featured', true)->count());
        $this->actingAs($admin)->put(route('admin.portfolio.update', $old), ['title' => $old->title, 'description' => 'Old.', 'is_featured' => '0'])->assertSessionHasNoErrors();
        $this->assertSame('New video project', $this->get('/portfolio')->viewData('heroProject')->title);
        $this->actingAs($admin)->get(route('admin.portfolio.create'))->assertOk()->assertSee('Pin as banner')->assertSee('Overrides the newest project');
    }

    public function test_video_upload_limits_give_clear_messages_and_work_end_to_end(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $big = UploadedFile::fake()->create('big.mp4', ShowcaseVideos::MAX_KILOBYTES + 10, 'video/mp4');
        $res = $this->actingAs($admin)->post(route('admin.portfolio.store'), ['title' => 'Too big', 'description' => 'x', 'image' => $this->png(), 'video_file' => $big]);
        $res->assertSessionHasErrors('video_file');
        $this->assertStringContainsString('larger than 20 MB', session('errors')->first('video_file'));
        $this->assertSame(0, Portfolio::where('title', 'Too big')->count());
        foreach (['x.php.mp4', 'x.mp4.php'] as $name) {
            $this->actingAs($admin)->post(route('admin.portfolio.store'), ['title' => 'Bad', 'description' => 'x', 'image' => $this->png(), 'video_file' => UploadedFile::fake()->createWithContent($name, '<?php echo 1;')])->assertSessionHasErrors('video_file');
        }
        $this->assertSame(0, Portfolio::where('title', 'Bad')->count());
    }

    // ----- card clips ---------------------------------------------------------------

    public function test_cards_with_a_video_get_a_lazy_hover_clip_on_the_portfolio_page_only(): void
    {
        $this->project('Featured', '2025-06-01', ['is_featured' => true]);
        $this->project('Card with video', '2025-03-01', $this->withClip());
        $this->project('Card without video', '2025-02-01');

        $html = $this->get('/portfolio')->assertOk()->getContent();
        $this->assertSame(1, preg_match_all('#<article[^>]*\sdata-project-clip#', $html));
        $this->assertMatchesRegularExpression('#<video class="project-card__clip" muted loop playsinline preload="none"#', $html);
        $this->assertDoesNotMatchRegularExpression('#<source#', $html);

        // The homepage "Featured Portfolio" cards stay image-only.
        $this->assertDoesNotMatchRegularExpression('#<article[^>]*\sdata-project-clip#', $this->get('/')->getContent());
    }

    // ----- admin --------------------------------------------------------------------

    public function test_admin_can_pick_a_library_clip_and_feature_a_single_project(): void
    {
        $a = $this->project('Alpha', '2025-01-01', ['is_featured' => true]);
        $b = $this->project('Beta', '2025-02-01');
        $this->assertArrayHasKey('elida', ShowcaseVideos::library());

        $this->actingAs($this->admin())->get(route('admin.portfolio.edit', $b))->assertOk()
            ->assertSee('Choose a clip from the site')->assertSee('value="elida"', false);

        $this->actingAs($this->admin())->put(route('admin.portfolio.update', $b), [
            'title' => 'Beta', 'description' => 'About Beta.', 'project_url' => 'https://example.com/beta',
            'video_library' => 'elida', 'is_featured' => '1',
        ])->assertSessionHasNoErrors();

        $b->refresh();
        $this->assertSame('assets/videos/portfolio/elida.mp4', $b->video_mp4);
        $this->assertSame('assets/videos/portfolio/elida-poster.jpg', $b->video_poster);
        $this->assertTrue($b->is_featured);
        $this->assertFalse($a->fresh()->is_featured, 'one featured project only');

        // A path that is not a library clip is refused.
        $this->actingAs($this->admin())->put(route('admin.portfolio.update', $b), [
            'title' => 'Beta', 'description' => 'About Beta.', 'video_library' => '../../.env',
        ])->assertSessionHasErrors('video_library');

        // Remove.
        $this->actingAs($this->admin())->put(route('admin.portfolio.update', $b), [
            'title' => 'Beta', 'description' => 'About Beta.', 'remove_video' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertFalse($b->fresh()->has_video);
    }

    private function upload(string $source, string $clientName): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'vid');
        copy($source, $tmp);

        return new UploadedFile($tmp, $clientName, null, null, true);
    }

    public function test_video_uploads_are_checked_by_content(): void
    {
        Storage::fake('public');
        $p = $this->project('Gamma', '2025-01-01');
        $send = fn (UploadedFile $f) => $this->actingAs($this->admin())->put(route('admin.portfolio.update', $p), [
            'title' => 'Gamma', 'description' => 'About Gamma.', 'video_file' => $f,
        ]);

        // Real MP4 and WebM are accepted and stored under random names.
        $send($this->upload(public_path('assets/videos/portfolio/elida.mp4'), 'My Clip.mp4'))->assertSessionHasNoErrors();
        $this->assertMatchesRegularExpression('#^portfolio_videos/[A-Za-z0-9]{40}\.mp4$#', $p->fresh()->video_mp4);
        $send($this->upload(public_path('assets/videos/portfolio/elida.webm'), 'clip.webm'))->assertSessionHasNoErrors();
        $this->assertMatchesRegularExpression('#^portfolio_videos/[A-Za-z0-9]{40}\.webm$#', $p->fresh()->video_webm);
        $this->assertNull($p->fresh()->video_mp4, 'one upload replaces both formats');

        // A script renamed to .mp4, an image renamed to .mp4, and a real video with a wrong extension are refused.
        $script = tempnam(sys_get_temp_dir(), 'vid');
        file_put_contents($script, '<?php echo "x"; ?>');
        $send(new UploadedFile($script, 'evil.mp4', null, null, true))->assertSessionHasErrors('video_file');
        $send($this->upload(public_path('assets/videos/portfolio/elida-poster.jpg'), 'poster.mp4'))->assertSessionHasErrors('video_file');
        $send($this->upload(public_path('assets/videos/portfolio/elida.mp4'), 'clip.php'))->assertSessionHasErrors('video_file');

        // Too large.
        $send(UploadedFile::fake()->create('big.mp4', ShowcaseVideos::MAX_KILOBYTES + 1, 'video/mp4'))->assertSessionHasErrors('video_file');
    }

    public function test_adding_a_project_works_now_that_the_slug_column_exists(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $file = UploadedFile::fake()->image('shot.jpg', 800, 500);

        $this->actingAs($this->admin())->post(route('admin.portfolio.store'), [
            'title' => 'New Client Site', 'description' => 'A new site.', 'project_url' => 'https://example.com/new',
            'image' => $file, 'video_library' => 'elida',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.portfolio.index'));

        $p = Portfolio::where('title', 'New Client Site')->sole();
        $this->assertStringStartsWith('new-client-site-', $p->slug);
        $this->assertTrue($p->has_video);
    }
}
