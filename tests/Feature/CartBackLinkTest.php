<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

/** The cart offers one small way back, to the page the customer was last looking at. */
class CartBackLinkTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private function link(): array
    {
        $html = $this->get('/cart')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'class="sky-return"'));
        preg_match('/<a href="([^"]*)"[^>]*data-return-link[^>]*>.*?<span>([^<]*)<\/span>/s', $html, $m);

        return [html_entity_decode($m[2]), str_replace('http://localhost', '', html_entity_decode($m[1])) ?: '/']; // "http://host" is the home page
    }

    private function product(): Product
    {
        return Product::create(['name' => 'Fleet Tool', 'slug' => 'fleet-tool', 'category' => 'business-tools', 'description' => 'D', 'image' => 'x.jpg', 'features' => ['A'], 'price' => 1]);
    }

    public function test_it_returns_to_products_when_nothing_was_viewed(): void
    {
        $this->assertSame(['Back to Products', '/products'], $this->link());
    }

    public function test_it_names_and_opens_the_page_last_viewed(): void
    {
        $product = $this->product();
        $service = $this->service($this->category(['slug' => 'smart', 'name' => 'Smart City']), ['slug' => 'wifi', 'title' => 'City Wifi']);
        $this->option($service);

        $this->get('/products/business-tools/fleet-tool');
        $this->assertSame(['Back to Fleet Tool', '/products/business-tools/fleet-tool'], $this->link());

        $this->get('/services/category/smart');
        $this->assertSame(['Back to Smart City', '/services/category/smart'], $this->link());

        $this->get('/services/detail/wifi');
        $this->assertSame(['Back to City Wifi', '/services/detail/wifi'], $this->link());

        $this->get('/');
        $this->assertSame(['Back to Home', '/'], $this->link());
    }

    public function test_adding_and_reloading_keep_it_and_a_hidden_item_never_leaves_a_dead_link(): void
    {
        $product = $this->product();
        $service = $this->service();
        $this->get('/products/business-tools/fleet-tool');
        $this->post('/cart/add', ['item_type' => 'service', 'item_id' => $service->id, 'option_id' => $this->option($service)->id]);
        $this->getJson('/cart/count');
        $this->link();
        $this->assertSame('Back to Fleet Tool', $this->link()[0]);

        $product->forceFill(['is_published' => false])->save();
        $this->assertSame(['Back to Products', '/products'], $this->link());
    }
}
