<?php

namespace Tests\Feature\Content;

use App\Console\Commands\PublishAnniversaryNews;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * news:publish-anniversary publishes the English + Amharic announcement once,
 * through the normal slug and image pipeline, and never touches other articles
 * unless --feature is given.
 */
class PublishAnniversaryNewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The news image pipeline needs the GD extension.');
        }
        Storage::fake('public');
        NewsCategory::firstOrCreate(['name' => 'News']);
    }

    private function older(array $attributes = []): News
    {
        $news = new News();
        $news->forceFill(array_merge([
            'title' => 'Older article', 'slug' => 'older-article', 'excerpt' => 'Older.', 'content' => '<p>Older.</p>',
            'news_category_id' => NewsCategory::where('name', 'News')->value('id'), 'language' => 'en',
            'status' => News::STATUS_PUBLISHED, 'published_at' => now()->subDay(),
        ], $attributes))->save();

        return $news;
    }

    public function test_publishes_english_and_amharic_with_the_english_one_newest(): void
    {
        $this->older();

        $this->artisan('news:publish-anniversary')->assertSuccessful();

        $latest = News::visible()->newestFirst()->take(2)->get();
        $this->assertSame('sky-link-ict-solutions-celebrates-five-years', $latest[0]->slug);
        $this->assertSame('en', $latest[0]->language);
        $this->assertSame(PublishAnniversaryNews::ARTICLES['en']['excerpt'], $latest[0]->excerpt);
        $this->assertSame('sky-link-ict-solutions-celebrates-five-years-amharic', $latest[1]->slug);
        $this->assertSame('am', $latest[1]->language);

        foreach ($latest as $news) {
            $this->assertSame(News::STATUS_PUBLISHED, $news->status);
            $this->assertFalse($news->is_featured);
            Storage::disk('public')->assertExists([$news->image, $news->image_thumb, $news->image_share]);
        }

        $this->get('/news/sky-link-ict-solutions-celebrates-five-years')->assertOk()
            ->assertSee('Sky Link ICT Solutions Celebrates Five Years')
            ->assertSee('Thank you for being part of the Sky Link story.');
    }

    public function test_running_twice_creates_no_duplicate(): void
    {
        $this->artisan('news:publish-anniversary')->assertSuccessful();
        $this->artisan('news:publish-anniversary')->assertSuccessful()
            ->expectsOutputToContain('0 article(s) published.');

        $this->assertSame(2, News::count());
    }

    public function test_other_articles_are_untouched_unless_feature_is_requested(): void
    {
        $featured = $this->older(['is_featured' => true]);

        $this->artisan('news:publish-anniversary')->assertSuccessful()
            ->expectsOutputToContain('Re-run with --feature');
        $this->assertTrue($featured->fresh()->is_featured);

        $this->artisan('news:publish-anniversary', ['--feature' => true])->assertSuccessful();
        $this->assertFalse($featured->fresh()->is_featured);
        $this->assertTrue(News::where('slug', 'sky-link-ict-solutions-celebrates-five-years')->first()->is_featured);
    }
}
