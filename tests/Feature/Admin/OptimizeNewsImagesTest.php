<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizeNewsImagesTest extends TestCase
{
    use RefreshDatabase;

    private function article(string $slug, string $image): News
    {
        $category = NewsCategory::firstOrCreate(['name' => 'News']);
        $news = new News(['title' => $slug, 'news_category_id' => $category->id, 'content' => 'x']);
        $news->forceFill(['slug' => $slug, 'image' => $image])->save();

        return $news;
    }

    public function test_oversized_images_get_a_web_copy_and_their_original_is_moved_to_the_private_archive(): void
    {
        $this->requireGd();
        Storage::fake('public');
        Storage::fake('local');
        // Keep the fake files referenced: their temp files are removed with the objects.
        $bigFile = UploadedFile::fake()->image('big.jpg', 2400, 1600);
        $smallFile = UploadedFile::fake()->image('small.jpg', 800, 500);
        $big = file_get_contents($bigFile->getRealPath());
        $small = file_get_contents($smallFile->getRealPath());
        Storage::disk('public')->put('news/big.jpg', $big);
        Storage::disk('public')->put('news/small.jpg', $small);
        $a = $this->article('a', 'news/big.jpg');
        $b = $this->article('b', 'news/big.jpg'); // shared file
        $c = $this->article('c', 'news/small.jpg');

        $this->artisan('news:optimize-images', ['--dry-run' => true])->assertSuccessful();
        Storage::disk('public')->assertExists('news/big.jpg');
        $this->assertNull($a->fresh()->image_thumb);

        $this->artisan('news:optimize-images')->assertSuccessful();

        // Oversized: moved (not deleted) to the archive, site uses the web copy.
        Storage::disk('public')->assertMissing('news/big.jpg');
        Storage::disk('local')->assertExists('archive/news-originals/big.jpg');
        $this->assertSame($big, Storage::disk('local')->get('archive/news-originals/big.jpg'));
        foreach ([$a, $b] as $n) {
            $n->refresh();
            $this->assertStringStartsWith('news/web/', $n->image);
            $this->assertSame('archive/news-originals/big.jpg', $n->image_original);
            [$w] = getimagesizefromstring(Storage::disk('public')->get($n->image));
            $this->assertSame(1600, $w);
        }
        $this->assertSame($a->fresh()->image, $b->fresh()->image);

        // Small: untouched, only a thumbnail added.
        $c->refresh();
        $this->assertSame('news/small.jpg', $c->image);
        Storage::disk('public')->assertExists('news/small.jpg');
        $this->assertStringStartsWith('news/thumbs/', $c->image_thumb);

        // Running again changes nothing.
        $this->artisan('news:optimize-images')->expectsOutputToContain('0 image file(s) processed')->assertSuccessful();
    }
}
