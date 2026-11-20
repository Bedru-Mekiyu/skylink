<?php

namespace Tests\Feature\Content;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixLmsProductImageTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $image): Product
    {
        return Product::create([
            'name' => 'Learning Management System (LMS)', 'category' => 'education', 'slug' => 'lms',
            'description' => 'x', 'price' => 170000, 'image' => $image, 'features' => ['a'], 'additional_info' => 'x',
        ]);
    }

    public function test_it_replaces_the_dead_bing_address_and_the_file_exists(): void
    {
        $p = $this->product('https://tse2.mm.bing.net/th/id/OIP.Q4QyoME7s8jZNPOAmPsbhAHaEv?r=0&rs=1');

        $this->artisan('products:fix-lms-image', ['--id' => $p->id])->assertSuccessful();

        $this->assertStringEndsWith('assets/images/products/lms-learning-platform.webp', $p->fresh()->image);
        $this->assertFileExists(public_path('assets/images/products/lms-learning-platform.webp'));
    }

    public function test_it_does_nothing_when_the_image_is_already_valid(): void
    {
        $p = $this->product('https://example.com/own-image.jpg');

        $this->artisan('products:fix-lms-image', ['--id' => $p->id])->assertSuccessful();
        $this->artisan('products:fix-lms-image', ['--id' => $p->id])->assertSuccessful();

        $this->assertSame('https://example.com/own-image.jpg', $p->fresh()->image);
    }

    public function test_second_run_after_the_fix_changes_nothing(): void
    {
        $p = $this->product('https://th.bing.com/th/id/R.abc');
        $this->artisan('products:fix-lms-image', ['--id' => $p->id]);
        $after = $p->fresh()->image;
        $this->artisan('products:fix-lms-image', ['--id' => $p->id])->assertSuccessful();

        $this->assertSame($after, $p->fresh()->image);
    }

    public function test_missing_product_is_not_an_error(): void
    {
        $this->artisan('products:fix-lms-image', ['--id' => 9999])->assertSuccessful();
    }
}
