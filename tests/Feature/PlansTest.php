<?php

namespace Tests\Feature;

use App\Models\ItemOption;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

class PlansTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private function product(): Product
    {
        return Product::create(['name' => 'Suite', 'slug' => 'suite', 'category' => 'business-tools', 'description' => 'D', 'image' => 'https://example.test/x.jpg', 'features' => ['A'], 'price' => 1]);
    }

    private function plan(Product $product, string $name, float $monthly, int $order, array $extra = []): ItemOption
    {
        return ItemOption::create($extra + ['item_type' => 'product', 'item_id' => $product->id, 'name' => $name, 'price_amount' => $monthly,
            'term_prices' => ['monthly' => $monthly, 'annually' => $monthly * 12 * 0.8], 'currency' => 'ETB', 'billing_term' => 'monthly',
            'show_price' => true, 'is_ready' => true, 'is_active' => true, 'sort_order' => $order]);
    }

    public function test_the_admin_sets_a_plans_short_line_order_and_the_one_recommended_plan(): void
    {
        $product = $this->product();
        $first = $this->plan($product, 'Starter', 1000, 1, ['is_recommended' => true]);
        $second = $this->plan($product, 'Business', 2000, 2);

        $this->actingAs($this->staff('finance'))->put('/admin/prices-overview/'.$second->id, [
            'name' => 'Business', 'currency' => 'ETB', 'price_amount' => 2000, 'billing_term' => 'monthly',
            'tagline' => 'Up to 50 users', 'sort_order' => 5, 'is_recommended' => '1', 'show_price' => '1', 'is_ready' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Up to 50 users', 5, true], [$second->fresh()->tagline, $second->fresh()->sort_order, $second->fresh()->is_recommended]);
        $this->assertFalse($first->fresh()->is_recommended);
    }

    public function test_the_detail_page_shows_plans_side_by_side_with_one_billing_switch(): void
    {
        $product = $this->product();
        $product->update(['plans_heading' => 'Choose your Suite plan']);
        $this->plan($product, 'Starter', 1000, 1, ['tagline' => 'Up to 25 users']);
        $this->plan($product, 'Business', 2000, 2, ['is_recommended' => true]);

        $page = $this->get('/products/business-tools/suite')->assertOk()
            ->assertSee('Choose your Suite plan')->assertSee('Up to 25 users')->assertSee('Recommended')
            ->assertSee('ETB 1,000.00')->assertSee('Save 20%');

        $this->assertSame(1, substr_count($page->getContent(), '<fieldset class="sky-switch"'));
        $this->assertSame(2, substr_count($page->getContent(), 'name="then" value="checkout"'));
    }

    public function test_the_card_shows_the_cheapest_plan_whatever_the_order(): void
    {
        $product = $this->product();
        $this->plan($product, 'Enterprise', 3000, 1);
        $this->plan($product, 'Starter', 1000, 2);

        $this->get('/products')->assertSee('From')->assertSee('ETB 1,000.00')->assertDontSee('ETB 3,000.00');
    }

    public function test_the_product_editor_has_every_text_and_no_price_box_that_does_nothing(): void
    {
        $product = $this->product();
        $this->plan($product, 'Starter', 1000, 1);
        $this->actingAs($this->staff('super_admin'));

        $this->get('/admin/products/'.$product->id.'/edit')->assertOk()
            ->assertSee('name="additional_info"', false)->assertSee('name="plans_heading"', false)
            ->assertSee('name="tagline"', false)->assertSee('name="is_recommended"', false)->assertSee('name="sort_order"', false)
            ->assertDontSee('name="price_text"', false);

        $this->put('/admin/products/'.$product->id, ['name' => 'Suite Pro', 'plans_heading' => 'Plans', 'additional_info' => 'Specs here'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Suite Pro', 'Plans', 'Specs here'], [$product->fresh()->name, $product->fresh()->plans_heading, $product->fresh()->additional_info]);
    }
}
