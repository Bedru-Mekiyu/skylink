<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveOrphanMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_lists_unused_files_and_apply_moves_them_without_deleting(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $public = Storage::disk('public');
        foreach (['news/used.jpg', 'news/thumbs/used-thumb.webp', 'news/hidden-dup.jpg', 'portfolio_images/p.webp', 'news/unused.jpg', 'services/old.png', '.gitignore'] as $f) {
            $public->put($f, 'bytes-of-'.$f);
        }

        $cat = NewsCategory::create(['name' => 'News']);
        $n = new News(['title' => 'A', 'news_category_id' => $cat->id, 'content' => 'x']);
        $n->forceFill(['slug' => 'a', 'image' => 'news/used.jpg', 'image_thumb' => 'news/thumbs/used-thumb.webp'])->save();
        // A hidden (soft-deleted) article still counts as using its image.
        $h = new News(['title' => 'B', 'news_category_id' => $cat->id, 'content' => 'x']);
        $h->forceFill(['slug' => 'b', 'image' => 'news/hidden-dup.jpg'])->save();
        $h->delete();
        Portfolio::create(['title' => 'P', 'description' => 'd', 'image' => 'portfolio_images/p.webp']);

        $this->artisan('media:archive-orphans')
            ->expectsOutputToContain('news/unused.jpg')
            ->expectsOutputToContain('services/old.png')
            ->expectsOutputToContain('2 unused file(s)')
            ->expectsOutputToContain('DRY RUN: nothing was moved')
            ->assertSuccessful();
        $public->assertExists('news/unused.jpg');

        $this->artisan('media:archive-orphans', ['--apply' => true, '--force' => true])->assertSuccessful();

        $public->assertMissing('news/unused.jpg');
        $public->assertMissing('services/old.png');
        Storage::disk('local')->assertExists('archive/orphans/news/unused.jpg');
        $this->assertSame('bytes-of-news/unused.jpg', Storage::disk('local')->get('archive/orphans/news/unused.jpg'));
        foreach (['news/used.jpg', 'news/thumbs/used-thumb.webp', 'news/hidden-dup.jpg', 'portfolio_images/p.webp', '.gitignore'] as $kept) {
            $public->assertExists($kept);
        }
        $manifest = collect(Storage::disk('local')->files('archive/orphans'))->first(fn ($f) => str_contains($f, 'manifest-'));
        $this->assertStringContainsString('storage/app/public/news/unused.jpg', Storage::disk('local')->get($manifest));
    }
}
