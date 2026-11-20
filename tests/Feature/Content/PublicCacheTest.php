<?php

namespace Tests\Feature\Content;

use App\Models\Product;
use App\Models\User;
use App\Support\PublicCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** The public pages cache their data for a short time; every admin save must make changes visible at once. */
class PublicCacheTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name): Product
    {
        return Product::create(['name' => $name, 'category' => 'education', 'slug' => str($name)->slug(), 'description' => 'd', 'price' => 100, 'image' => 'https://example.com/a.jpg', 'features' => ['a'], 'additional_info' => 'x']);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    public function test_the_products_page_is_cached_and_a_model_save_shows_the_change_at_once(): void
    {
        $p = $this->product('Alpha Suite');
        $this->get('/products')->assertSee('Alpha Suite');

        // Changed behind the cache's back (no model event): the cached page still shows the old name ...
        \DB::table('products')->where('id', $p->id)->update(['name' => 'Hidden Rename']);
        $this->get('/products')->assertSee('Alpha Suite')->assertDontSee('Hidden Rename');

        // ... and a normal save (what the admin does) shows the new one immediately.
        $p->refresh()->update(['name' => 'Beta Suite']);
        $this->get('/products')->assertSee('Beta Suite')->assertDontSee('Alpha Suite');
    }

    public function test_a_successful_admin_save_clears_the_public_data_cache(): void
    {
        $p = $this->product('Gamma Suite');
        $this->get('/products')->assertSee('Gamma Suite');
        \DB::table('products')->where('id', $p->id)->update(['name' => 'Delta Suite']);
        $this->get('/products')->assertSee('Gamma Suite');           // still cached

        $this->actingAs($this->admin())->post(route('admin.inbox.read-all'))->assertRedirect();

        $this->get('/products')->assertSee('Delta Suite');            // an admin POST rebuilt it
    }

    public function test_admin_pages_and_failed_requests_do_not_clear_it_but_reads_never_cache_admin_data(): void
    {
        PublicCache::remember('probe', 600, fn () => 'one');
        $this->actingAs($this->admin())->get(route('admin.dashboard'));
        $this->assertSame('one', PublicCache::remember('probe', 600, fn () => 'two'));   // a GET does not bust

        PublicCache::bust();
        $this->assertSame('two', PublicCache::remember('probe', 600, fn () => 'two'));
    }

    public function test_it_can_be_switched_off_by_config(): void
    {
        config(['cache.public_data' => false]);
        $this->assertSame('a', PublicCache::remember('x', 600, fn () => 'a'));
        $this->assertSame('b', PublicCache::remember('x', 600, fn () => 'b'));
    }

    public function test_service_and_about_images_carry_size_srcset_and_alt(): void
    {
        $html = $this->get('/about')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#srcset="[^"]*480w#', $html);
        $this->assertMatchesRegularExpression('#<img[^>]+width="\d+"[^>]+height="\d+"#', $html);
        $this->assertStringContainsString('assets/js/sky-img.js', $html);      // placeholder fade-in and broken-image fallback
        $this->assertStringContainsString('assets/css/sky-img.css', $html);
        $this->assertStringContainsString('data-img-fallback=', $html);
    }
}
