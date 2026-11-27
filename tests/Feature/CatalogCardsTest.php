<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ItemOption;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

class CatalogCardsTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private function listing(array $option = []): array
    {
        $category = $this->category(['slug' => 'cards-test']);
        $service = $this->service($category, ['slug' => 'card-service']);
        $opt = $this->option($service, $option);

        return [$service, $opt, $this->get('/services/category/cards-test')->assertOk()];
    }

    public function test_prices_read_the_same_way_everywhere(): void
    {
        $this->assertSame('ETB 60,000.00', Money::format(60000, 'ETB'));
        $this->assertSame('ETB 15,757.90', Money::format(15757.9, 'ETB'));
        $this->assertSame('$1,200.00', Money::format(1200, 'USD'));

        // Hand-typed texts that only repeat a number are written the same way; real words stay as typed.
        $this->assertSame('ETB 175,000.00', Money::display('ETB 175,000', 'ETB'));
        $this->assertSame('ETB 12,500.00', Money::display('12,500 Br', 'ETB'));
        $this->assertSame('Custom Quote', Money::display('Custom Quote', 'ETB'));
        $this->assertSame('From 5,000 per site', Money::display('From 5,000 per site', 'ETB'));

        [, , $page] = $this->listing(['price_amount' => 60000, 'price_display_text' => '60000']);
        $page->assertSee('ETB 60,000.00')->assertDontSee('>60000<', false);
    }

    public function test_the_whole_card_opens_details_and_checkout_replaces_view_details(): void
    {
        [$service, , $page] = $this->listing();

        $page->assertSee('class="stretched-link', false)
            ->assertSee(route('services.detail', ['subcategory' => $service->slug]), false)
            ->assertSee('name="then" value="checkout"', false)
            ->assertSee('Checkout')
            ->assertDontSee('View Details');
    }

    public function test_an_item_without_a_visible_price_only_offers_a_quote(): void
    {
        [, , $page] = $this->listing(['show_price' => false]);

        $page->assertSee('Request a Quote')
            ->assertDontSee('name="then" value="checkout"', false)
            ->assertDontSee('class="add-to-cart-form', false);
    }

    public function test_the_product_list_uses_the_same_card(): void
    {
        $product = Product::create(['name' => 'Card Product', 'slug' => 'card-product', 'category' => 'business-tools', 'description' => 'D', 'image' => 'x.jpg', 'features' => ['A'], 'price' => 8500]);
        ItemOption::create(['item_type' => 'product', 'item_id' => $product->id, 'name' => 'Plan', 'price_amount' => 8500, 'currency' => 'ETB', 'billing_term' => 'monthly', 'show_price' => true, 'is_ready' => true, 'is_active' => true]);

        $this->get('/products')->assertOk()
            ->assertSee('Card Product')->assertSee('ETB 8,500.00')
            ->assertSee('class="stretched-link', false)
            ->assertSee('name="then" value="checkout"', false)
            ->assertDontSee('View Details');
    }

    public function test_checkout_goes_to_the_cart_and_pressing_it_again_does_not_add_more(): void
    {
        [$service, $opt] = $this->listing();
        $line = ['item_type' => 'service', 'item_id' => $service->id, 'option_id' => $opt->id, 'then' => 'checkout'];
        $quantity = fn () => array_values(session('cart'))[0]['quantity'];

        $this->post('/cart/add', $line)->assertRedirect(route('cart.index'));
        $this->post('/cart/add', $line)->assertRedirect(route('cart.index'));
        $this->assertSame(1, $quantity());

        // A quantity picked on the detail page is what the buyer gets.
        $this->post('/cart/add', $line + ['quantity' => 3]);
        $this->assertSame(3, $quantity());

        // A card does not send a quantity, so it keeps the one already chosen.
        $this->post('/cart/add', $line);
        $this->assertSame(3, $quantity());
    }

    public function test_each_plan_on_a_detail_page_can_go_straight_to_checkout(): void
    {
        [$service] = $this->listing();

        $this->get('/services/detail/'.$service->slug)->assertOk()->assertSee('name="then" value="checkout"', false);
    }

    public function test_catalog_pages_have_no_breadcrumb_trail_and_no_counts_on_category_buttons(): void
    {
        [$service] = $this->listing();
        $product = Product::create(['name' => 'Card Product', 'slug' => 'card-product', 'category' => 'business-tools', 'description' => 'D', 'image' => 'x.jpg', 'features' => ['A'], 'price' => 1]);

        foreach (['/services/category/cards-test', '/services/detail/'.$service->slug, '/products', '/products/business-tools/'.$product->slug] as $path) {
            $this->get($path)->assertOk()
                ->assertDontSee('aria-label="breadcrumb"', false)
                ->assertDontSee('badge bg-secondary-subtle text-secondary rounded-pill', false);
        }
    }

    public function test_the_services_page_has_no_search_box(): void
    {
        $this->category(['slug' => 'search-cat', 'name' => 'Searchable']);

        $this->get('/services')->assertOk()->assertSee('sky-card__title', false)->assertSee('class="filter-btn', false)
            ->assertDontSee('searchInput', false)->assertDontSee('searchBtn', false)->assertDontSee('search-container', false);
    }
}
