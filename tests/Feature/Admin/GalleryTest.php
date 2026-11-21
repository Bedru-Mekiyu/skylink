<?php

namespace Tests\Feature\Admin;

use App\Models\GalleryImage;
use App\Models\User;
use App\Support\PublicCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Admin > Gallery and the About-page gallery section. */
class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    private function photo(string $name = 'photo.jpg', int $w = 1200, int $h = 800): UploadedFile
    {
        $this->requireGd();
        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, $w, $h, imagecolorallocate($im, 30, 90, 160));
        ob_start(); imagejpeg($im); $bytes = ob_get_clean();

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function addPhotos(int $count, array $extra = []): void
    {
        for ($i = 1; $i <= $count; $i++) {
            GalleryImage::create($extra + [
                'caption_en' => "Photo $i", 'sort_order' => $i, 'thumb_path' => "gallery/t$i.webp", 'display_path' => "gallery/d$i.webp",
                'thumb_w' => 480, 'thumb_h' => 320, 'display_w' => 1600, 'display_h' => 1066,
            ]);
        }
        PublicCache::bust();
    }

    public function test_only_admins_reach_the_gallery_routes(): void
    {
        $this->addPhotos(1);
        $g = GalleryImage::first();
        $routes = [['get', route('admin.gallery.index')], ['post', route('admin.gallery.store')], ['get', route('admin.gallery.edit', $g)],
            ['put', route('admin.gallery.update', $g)], ['put', route('admin.gallery.toggle', $g)], ['put', route('admin.gallery.reorder')]];
        foreach ($routes as [$verb, $url]) {
            $this->$verb($url)->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($routes as [$verb, $url]) {
            $this->$verb($url)->assertForbidden();
        }
        $this->delete(route('admin.gallery.update', $g))->assertStatus(405);
    }

    public function test_no_visible_photos_means_no_gallery_section_at_all(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();
        $this->assertStringNotContainsString('sk-gallery', $html);
        $this->addPhotos(2, ['visible' => false]);
        $this->assertStringNotContainsString('sk-gallery', $this->get(route('about'))->getContent());
    }

    public function test_empty_gallery_leaves_milestones_directly_above_the_footer(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();
        $this->assertStringNotContainsString('sk-gallery', $html);
        $this->assertStringNotContainsString('id="gallery"', $html);
        $between = substr($html, strpos($html, 'Milestones'));
        $between = substr($between, strpos($between, '</section>') + 10, strpos($between, '<footer class="footer"') - strpos($between, '</section>') - 10);
        $this->assertSame('</main>', trim(preg_replace('#<!--.*?-->|<script.*?</script>|<style.*?</style>#s', '', $between)), 'only the closing main tag between Milestones and the footer');
    }

    public function test_gallery_is_the_last_section_after_milestones_and_before_the_footer_for_1_3_12_and_60_photos(): void
    {
        foreach ([1, 3, 12, 60] as $count) {
            GalleryImage::query()->delete();
            $this->addPhotos($count);
            $html = $this->get(route('about'))->getContent();
            $team = strpos($html, 'Meet the People Behind the Vision');
            $mile = strpos($html, 'Milestones');
            $gal = strpos($html, 'id="gallery"');
            $footer = strpos($html, '<footer class="footer"');
            $this->assertTrue($team < $mile && $mile < $gal && $gal < $footer, "$count photos: order must be Milestones, Gallery, footer");
            $this->assertSame(1, substr_count($html, 'id="gallery"'), 'the old position is gone (one gallery only)');
            $this->assertSame(1, substr_count($html, 'id="sk-gallery-data"'));
            $this->assertSame($count, substr_count($html, 'class="sk-gallery__slide"'), "$count photos");
            $this->assertStringContainsString('data-count="'.$count.'"', $html);
        }
    }

    public function test_gallery_header_is_just_gallery_no_photo_n_text_and_a_plain_background(): void
    {
        $this->addPhotos(4);
        $html = $this->get(route('about'))->assertOk()->getContent();
        $sec = substr($html, strpos($html, '<section class="sk-gallery'));
        $sec = substr($sec, 0, strpos($sec, '</section>'));
        $this->assertSame(1, preg_match_all('#<h2[^>]*>(.*?)</h2>#s', $sec, $m));
        $this->assertSame('Gallery', trim($m[1][0]));
        $this->assertStringNotContainsString('sk-gallery__tag', $sec);
        $this->assertStringNotContainsString('<p>', $sec);
        // no auto-generated visible label: nothing renders "Photo N" as text (screen-reader-only text is allowed)
        $visible = preg_replace('#<script.*?</script>|<[^>]+>#s', ' ', $sec);
        $this->assertDoesNotMatchRegularExpression('/Photo\s*\d/i', $visible);
        // same plain background as the neighbours: no panel, gradient or glow on the section
        $this->assertStringContainsString('class="sk-gallery py-5"', $sec);
        $css = substr($html, strpos($html, '.sk-gallery {'), 200);
        $this->assertStringNotContainsString('gradient', $css);
        $this->assertStringNotContainsString('background', $css);
    }

    public function test_alt_text_falls_back_to_a_neutral_text_without_numbers(): void
    {
        $g = new GalleryImage();
        $this->assertSame('Sky Link gallery photo', $g->altText());
    }

    public function test_hidden_photos_do_not_show_and_order_is_respected(): void
    {
        $this->addPhotos(3);
        GalleryImage::where('caption_en', 'Photo 2')->update(['visible' => false]);
        GalleryImage::where('caption_en', 'Photo 3')->update(['sort_order' => 0]);
        PublicCache::bust();
        $html = $this->get(route('about'))->getContent();
        $this->assertStringNotContainsString('Photo 2', $html);
        $this->assertLessThan(strpos($html, 'Photo 1'), strpos($html, 'Photo 3'));
    }

    public function test_page_has_reduced_motion_and_accessibility_hooks(): void
    {
        $this->addPhotos(4);
        $html = $this->get(route('about'))->getContent();
        foreach (['prefers-reduced-motion', 'aria-roledescription="carousel"', 'aria-modal', 'saveData', 'IntersectionObserver', 'Escape'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        // The gallery turns by itself: no visible prev/next/pause buttons and no dots.
        foreach (['sk-gallery__bar', 'sk-gallery__btn', 'data-act=', 'aria-pressed'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, $gone);
        }
    }

    public function test_valid_upload_makes_both_sizes_and_archives_the_original(): void
    {
        $this->actingAs($this->admin());
        $this->postJson(route('admin.gallery.store'), ['photo' => $this->photo('Big Day.jpg', 3000, 2000), 'caption_en' => 'With a partner', 'caption_am' => 'ከአጋር ጋር'])->assertOk()->assertJson(['ok' => true]);
        $g = GalleryImage::firstOrFail();
        $this->assertMatchesRegularExpression('#^gallery/[A-Za-z0-9]{40}-thumb\.webp$#', $g->thumb_path);
        $this->assertSame(480, $g->thumb_w);
        $this->assertSame(1600, $g->display_w);
        Storage::disk('public')->assertExists([$g->thumb_path, $g->display_path, 'gallery/.htaccess']);
        Storage::disk('local')->assertExists($g->original_path);
        $this->assertStringContainsString('Require all denied', Storage::disk('public')->get('gallery/.htaccess'));
        $this->assertSame('ከአጋር ጋር', $g->caption_am);
        $this->assertGreaterThan(0, $g->sort_order);
        // small photos are not upscaled
        $this->postJson(route('admin.gallery.store'), ['photo' => $this->photo('small.jpg', 800, 600)])->assertOk();
        $this->assertSame(480, GalleryImage::latest('id')->first()->thumb_w);
        $this->assertSame(800, GalleryImage::latest('id')->first()->display_w);
    }

    public function test_invalid_uploads_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $bad = [
            'text renamed png' => UploadedFile::fake()->createWithContent('x.png', 'this is not an image'),
            'php as jpg' => UploadedFile::fake()->createWithContent('x.jpg', '<?php echo 1;'),
            'double extension' => UploadedFile::fake()->createWithContent('evil.php.jpg', "\xFF\xD8\xFF\xE0"),
            'svg' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            'gif' => UploadedFile::fake()->createWithContent('x.gif', 'GIF89a'.str_repeat("\0", 30)),
            'mp4' => UploadedFile::fake()->createWithContent('x.mp4', "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom"),
        ];
        foreach ($bad as $label => $file) {
            $this->postJson(route('admin.gallery.store'), ['photo' => $file])->assertStatus(422)->assertJsonValidationErrors('photo');
        }
        $big = UploadedFile::fake()->createWithContent('big.jpg', 'x')->size(8193);
        $this->postJson(route('admin.gallery.store'), ['photo' => $big])->assertStatus(422);
        $this->postJson(route('admin.gallery.store'), [])->assertStatus(422);
        $this->assertSame(0, GalleryImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_tiny_photos_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $this->postJson(route('admin.gallery.store'), ['photo' => $this->photo('tiny.jpg', 200, 150)])->assertStatus(422);
    }

    public function test_list_search_edit_toggle_and_reorder(): void
    {
        $this->actingAs($this->admin());
        $this->addPhotos(3);
        $this->get(route('admin.gallery.index'))->assertOk()->assertSee("Only upload photos you are allowed to show publicly (for example, with the partners' or clients' permission).", false)->assertSee('Photo 1');
        $this->get(route('admin.gallery.index', ['q' => 'Photo 2']))->assertOk()->assertSee('Photo 2')->assertDontSee('Photo 3');

        $g = GalleryImage::where('caption_en', 'Photo 1')->first();
        $this->put(route('admin.gallery.update', $g), ['caption_en' => 'Edited', 'caption_am' => 'አማርኛ', 'taken_on' => '2026-01-05', 'sort_order' => 9, 'visible' => 1])->assertRedirect(route('admin.gallery.index'));
        $g->refresh();
        $this->assertSame('Edited', $g->caption_en);
        $this->assertSame('2026-01-05', $g->taken_on->format('Y-m-d'));
        $this->put(route('admin.gallery.update', $g), ['taken_on' => '2999-01-01'])->assertSessionHasErrors('taken_on');

        $this->put(route('admin.gallery.toggle', $g))->assertRedirect();
        $this->assertFalse($g->fresh()->visible);
        $this->assertSame(3, GalleryImage::count(), 'nothing is deleted');

        $ids = GalleryImage::orderBy('sort_order')->pluck('id')->all();
        $this->putJson(route('admin.gallery.reorder'), ['ids' => array_reverse($ids)])->assertOk();
        $this->assertSame(array_reverse($ids), GalleryImage::orderBy('sort_order')->pluck('id')->all());
        $this->putJson(route('admin.gallery.reorder'), ['ids' => [999999]])->assertStatus(422);
    }

    public function test_uploads_are_rate_limited(): void
    {
        $this->actingAs($this->admin());
        for ($i = 0; $i < 120; $i++) {
            $this->postJson(route('admin.gallery.store'), [])->assertStatus(422);
        }
        $this->postJson(route('admin.gallery.store'), [])->assertStatus(429);
    }
}
