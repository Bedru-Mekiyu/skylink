<?php

namespace Tests\Feature\Admin;

use App\Models\GalleryImage;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsSlugRedirect;
use App\Models\Partner;
use App\Models\Portfolio;
use App\Models\User;
use App\Models\Video;
use App\Support\MediaCleanup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Every content type the admin manages can be deleted, with its files; guests and non-admins cannot. */
class DeleteEverythingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    public function test_guests_and_non_admins_cannot_delete_anything(): void
    {
        $partner = Partner::first() ?? Partner::create(['name' => 'P', 'alt' => 'P', 'logo_path' => 'partners/x.webp', 'sort_order' => 1, 'active' => true]);
        $photo = GalleryImage::create(['thumb_path' => 'gallery/t.webp', 'display_path' => 'gallery/d.webp', 'thumb_w' => 1, 'thumb_h' => 1, 'display_w' => 1, 'display_h' => 1, 'original_path' => 'archive/gallery/o.jpg']);
        $targets = [route('admin.partners.destroy', $partner), route('admin.gallery.destroy', $photo)];
        foreach ($targets as $url) {
            auth()->logout();
            $this->delete($url)->assertRedirect(route('login'));
            $this->actingAs(User::factory()->create())->delete($url)->assertStatus(403);
            $this->get($url)->assertStatus(405);
        }
        $this->assertModelExists($partner);
        $this->assertModelExists($photo);
    }

    public function test_partner_and_gallery_delete_remove_rows_and_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('partners/a.webp', 'x');
        Storage::disk('public')->put('partners/a.png', 'x');
        Storage::disk('local')->put('archive/partners/a.png', 'x');
        $partner = Partner::create(['name' => 'Gone Partner', 'alt' => 'g', 'logo_path' => 'partners/a.webp', 'logo_fallback' => 'partners/a.png', 'original_path' => 'archive/partners/a.png', 'sort_order' => 9, 'active' => true]);
        $this->actingAs($this->admin())->delete(route('admin.partners.destroy', $partner))->assertRedirect(route('admin.partners.index'));
        $this->assertModelMissing($partner);
        Storage::disk('public')->assertMissing(['partners/a.webp', 'partners/a.png']);
        Storage::disk('local')->assertMissing('archive/partners/a.png');

        foreach (['gallery/t.webp', 'gallery/d.webp'] as $f) {
            Storage::disk('public')->put($f, 'x');
        }
        Storage::disk('local')->put('archive/gallery/o.jpg', 'x');
        $photo = GalleryImage::create(['thumb_path' => 'gallery/t.webp', 'display_path' => 'gallery/d.webp', 'thumb_w' => 1, 'thumb_h' => 1, 'display_w' => 1, 'display_h' => 1, 'original_path' => 'archive/gallery/o.jpg']);
        $this->actingAs($this->admin())->get(route('admin.gallery.edit', $photo))->assertOk()->assertSee('Delete photo');
        $this->actingAs($this->admin())->delete(route('admin.gallery.destroy', $photo))->assertRedirect(route('admin.gallery.index'));
        $this->assertModelMissing($photo);
        Storage::disk('public')->assertMissing(['gallery/t.webp', 'gallery/d.webp']);
        Storage::disk('local')->assertMissing('archive/gallery/o.jpg');
    }

    public function test_portfolio_delete_removes_uploaded_files_but_never_site_files_and_blocks_the_pinned_banner(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('portfolio_images/i.webp', 'x');
        Storage::disk('public')->put('portfolio_videos/v.mp4', 'x');
        $p = new Portfolio(['title' => 'Delete me', 'description' => 'd', 'image' => 'portfolio_images/i.webp']);
        $p->forceFill(['video_mp4' => 'portfolio_videos/v.mp4', 'video_webm' => 'assets/videos/portfolio/elida.webm', 'video_poster' => 'assets/videos/portfolio/elida-poster.jpg'])->save();
        $pinned = new Portfolio(['title' => 'Pinned', 'description' => 'd', 'image' => 'portfolio_images/p.webp']);
        $pinned->forceFill(['is_featured' => true])->save();

        $this->actingAs($this->admin())->delete(route('admin.portfolio.destroy', $pinned))->assertSessionHas('error');
        $this->assertModelExists($pinned);

        $this->actingAs($this->admin())->delete(route('admin.portfolio.destroy', $p))->assertSessionHas('success');
        $this->assertModelMissing($p);
        Storage::disk('public')->assertMissing(['portfolio_images/i.webp', 'portfolio_videos/v.mp4']);
        $this->assertFileExists(public_path('assets/videos/portfolio/elida.mp4'), 'the site clip files stay');
        $this->assertFileExists(public_path('assets/videos/portfolio/elida.webm'));
    }

    public function test_video_delete_removes_the_thumbnail(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/t.jpg', 'x');
        $v = new Video(['title' => 'V', 'video_url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'thumbnail_image' => 'videos/t.jpg']);
        $v->forceFill(['slug' => 'v-slug', 'news_category_id' => NewsCategory::create(['name' => 'Vids'])->id])->save();
        $this->actingAs($this->admin())->delete(route('admin.videos.destroy', $v))->assertRedirect();
        $this->assertModelMissing($v);
        Storage::disk('public')->assertMissing('videos/t.jpg');
    }

    public function test_news_permanent_delete_removes_row_files_and_redirects_and_the_address_is_404(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $cat = NewsCategory::create(['name' => 'News']);
        Storage::disk('public')->put('news/web/a.webp', 'x');
        Storage::disk('public')->put('news/thumbs/a.webp', 'x');
        Storage::disk('local')->put('archive/news-originals/a.jpg', 'x');
        $n = new News(['title' => 'Permanent goodbye', 'news_category_id' => $cat->id, 'content' => '<p>Body</p>', 'language' => 'en']);
        $n->forceFill(['slug' => 'permanent-goodbye', 'status' => 'published', 'published_at' => now()->subDay(), 'image' => 'news/web/a.webp', 'image_thumb' => 'news/thumbs/a.webp', 'image_original' => 'archive/news-originals/a.jpg'])->save();
        NewsSlugRedirect::create(['news_id' => $n->id, 'old_slug' => 'old-address']);
        $url = route('news.show', $n->slug ?? $n->id);

        $this->actingAs(User::factory()->create())->delete(route('admin.news.force-destroy', $n))->assertStatus(403);
        $this->assertModelExists($n);
        $this->actingAs($this->admin())->get(route('admin.news.index'))->assertOk()->assertSee('Delete permanently', false);
        $this->actingAs($this->admin())->delete(route('admin.news.force-destroy', $n))->assertRedirect(route('admin.news.index'));
        $this->assertNull(News::withTrashed()->find($n->id));
        $this->assertSame(0, NewsSlugRedirect::where('news_id', $n->id)->count());
        Storage::disk('public')->assertMissing(['news/web/a.webp', 'news/thumbs/a.webp']);
        Storage::disk('local')->assertMissing('archive/news-originals/a.jpg');
        auth()->logout();
        $this->get($url)->assertNotFound();
    }

    public function test_the_hide_actions_still_exist(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.partners.index'))->assertOk()->assertSee('Hide');
        $this->actingAs($admin)->get(route('admin.testimonials.index'))->assertOk()->assertSee('Hide');
    }

    public function test_media_cleanup_never_touches_site_files_or_web_addresses(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('keep/x.jpg', 'x');
        MediaCleanup::remove(['assets/images/about/hanan.jpg', 'https://example.test/a.jpg', '../../.env', '', null]);
        $this->assertFileExists(public_path('assets/images/about/hanan.jpg'));
        Storage::disk('public')->assertExists('keep/x.jpg');
        MediaCleanup::remove(['keep/x.jpg']);
        Storage::disk('public')->assertMissing('keep/x.jpg');
    }
}
