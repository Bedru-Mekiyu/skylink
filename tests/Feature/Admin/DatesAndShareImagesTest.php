<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Fixes before Phase 4: Addis Ababa dates on public pages, JPEG share images. */
class DatesAndShareImagesTest extends TestCase
{
    use RefreshDatabase;

    private NewsCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->category = NewsCategory::create(['name' => 'News']);
    }

    public function test_an_article_published_at_00_30_addis_time_shows_that_day_everywhere(): void
    {
        // 2025-03-02 00:30 in Addis Ababa = 2025-03-01 21:30 UTC.
        $n = new News(['title' => 'Midnight launch', 'news_category_id' => $this->category->id, 'content' => 'Body']);
        $n->forceFill(['slug' => 'midnight', 'status' => 'published', 'published_at' => '2025-03-01 21:30:00'])->save();
        $other = new News(['title' => 'Another story', 'news_category_id' => $this->category->id, 'content' => 'Body']);
        $other->forceFill(['slug' => 'other', 'status' => 'published', 'published_at' => '2025-03-03 10:00:00'])->save();

        // Article page: visible date and meta tag.
        $this->get('/news/midnight')->assertOk()
            ->assertSee('<time datetime="2025-03-02">March 2, 2025</time>', false)
            ->assertSee('content="2025-03-02T00:30:00+03:00"', false)
            ->assertDontSee('March 1, 2025');

        // Related articles on another article's page, and the list (card and featured).
        $this->get('/news/other')->assertSee('<time datetime="2025-03-02">Mar 2, 2025</time>', false);
        // The /news banner shows the newest article (no videos here), so the 00:30
        // article is the featured story, which uses the long date format.
        $this->get('/news?page=1')->assertSee('March 2, 2025');
        // Homepage "Latest News".
        $this->get('/')->assertSee('02 Mar 2025');
    }

    public function test_uploads_get_a_jpeg_share_copy_used_by_og_and_twitter_tags(): void
    {
        $this->requireGd();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get(route('admin.news.create'));
        $token = collect(session('form_tokens.admin-news'))->last();

        $this->actingAs($admin)->post(route('admin.news.store'), [
            'title' => 'Share test', 'language' => 'en', 'news_category_id' => $this->category->id,
            'content' => '<p>Body</p>', 'status' => 'published', 'form_token' => $token, 'url_title' => 'Share test',
            'image' => UploadedFile::fake()->image('photo.png', 2000, 1000),
        ])->assertSessionHasNoErrors();

        $news = News::first();
        $this->assertMatchesRegularExpression('#^news/share/[A-Za-z0-9]{40}\.jpg$#', $news->image_share);
        $bytes = Storage::disk('public')->get($news->image_share);
        $info = getimagesizefromstring($bytes);
        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(1200, $info[0]);

        $this->get('/news/share-test')->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('storage/'.$news->image_share).'">', false)
            ->assertSee('<meta property="og:image:type" content="image/jpeg">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset('storage/'.$news->image_share).'">', false)
            // The page itself still shows the WebP web copy.
            ->assertSee(asset('storage/'.$news->image), false);
    }

    public function test_existing_articles_get_a_share_copy_from_their_archived_original(): void
    {
        $this->requireGd();
        $originalFile = UploadedFile::fake()->image('orig.jpg', 3000, 2000);
        Storage::disk('local')->put('archive/news-originals/orig.jpg', file_get_contents($originalFile->getRealPath()));
        $webFile = UploadedFile::fake()->image('web.jpg', 1600, 1067);
        Storage::disk('public')->put('news/web/web.webp', file_get_contents($webFile->getRealPath()));

        $n = new News(['title' => 'Old', 'news_category_id' => $this->category->id, 'content' => 'x']);
        $n->forceFill(['slug' => 'old', 'image' => 'news/web/web.webp', 'image_thumb' => 'news/thumbs/t.webp',
            'image_original' => 'archive/news-originals/orig.jpg'])->save();

        $this->artisan('news:optimize-images', ['--dry-run' => true])
            ->expectsOutputToContain('would make share JPEG from storage/app/private/archive/news-originals/orig.jpg')
            ->assertSuccessful();
        $this->assertNull($n->fresh()->image_share);

        $this->artisan('news:optimize-images')->assertSuccessful();
        $share = $n->fresh()->image_share;
        $this->assertNotNull($share);
        $info = getimagesizefromstring(Storage::disk('public')->get($share));
        $this->assertSame([1200, 'image/jpeg'], [$info[0], $info['mime']]);
    }
}
