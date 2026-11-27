<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

/** The owner's choice: the sidebar categories are the only filter. */
class SidebarOnlyFilterTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    public function test_the_products_page_is_filtered_by_the_sidebar_only(): void
    {
        Product::create(['name' => 'One', 'slug' => 'one', 'category' => 'business-tools', 'description' => 'D', 'image' => 'x.jpg', 'features' => ['A'], 'price' => 1]);

        $this->get('/products')->assertOk()
            ->assertSee('data-cat="business-tools"', false)
            ->assertDontSee('searchInput', false)->assertDontSee('sortSelect', false)
            ->assertDontSee('data-cat="all"', false)->assertDontSee('All Categories')
            ->assertSee('location.hash', false);
    }

    public function test_the_extra_navigation_buttons_are_gone(): void
    {
        $service = $this->service($this->category(['slug' => 'only-side']), ['slug' => 'only-svc']);
        $option = $this->option($service);

        $this->get('/services/category/only-side')->assertOk()->assertDontSee('View All Categories');
        $this->get('/cart')->assertOk()->assertDontSee('Continue Browsing');
        $this->post('/cart/add', ['item_type' => 'service', 'item_id' => $service->id, 'option_id' => $option->id]);
        $this->get('/cart')->assertOk()->assertSee('Request Cart')->assertDontSee('Continue Browsing');
    }
}
