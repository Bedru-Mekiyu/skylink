<?php

namespace Tests\Feature;

use App\Models\ItemOption;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

class SaleModeTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private function product(): Product
    {
        return Product::create(['name' => 'Suite', 'slug' => 'suite', 'category' => 'business-tools', 'description' => 'D', 'image' => 'https://example.test/x.jpg', 'features' => ['A'], 'price' => 1]);
    }

    private function sell(string $type, int $id, array $data, string $role = 'finance')
    {
        $this->flushSession(); // each call signs in a new member of staff

        return $this->actingAs($this->staff($role))->put("/admin/prices-overview/item/{$type}/{$id}/sale", $data);
    }

    private function plans(Product $product)
    {
        return ItemOption::where('item_type', 'product')->where('item_id', $product->id)->orderBy('id')->get();
    }

    public function test_a_fixed_price_is_one_amount_with_checkout(): void
    {
        $product = $this->product();

        $this->sell('product', $product->id, ['mode' => 'fixed', 'price' => 5000, 'payable' => '1'])->assertSessionHasNoErrors();

        $plan = $this->plans($product)->sole();
        $this->assertSame(['one-time', '5000.00', true, true], [$plan->billing_term, $plan->price_amount, $plan->show_price, $plan->is_payable_online]);
        $this->get('/products/business-tools/suite')->assertSee('ETB 5,000.00')->assertSee('value="checkout"', false)->assertDontSee('<fieldset class="sky-switch"', false);
    }

    public function test_a_subscription_fills_empty_longer_periods_with_the_discounts(): void
    {
        $product = $this->product();

        $this->sell('product', $product->id, ['mode' => 'subscription', 'monthly' => 1000, 'annually' => 9000])->assertSessionHasNoErrors();

        $plan = $this->plans($product)->sole();
        $this->assertSame('monthly', $plan->billing_term);
        $this->assertEquals(['monthly' => 1000, 'quarterly' => 2850, 'semi-annually' => 5400, 'annually' => 9000], $plan->term_prices);
        $this->get('/products/business-tools/suite')->assertSee('<fieldset class="sky-switch"', false);
    }

    public function test_request_a_quote_hides_the_price_and_keeps_it_for_later(): void
    {
        $product = $this->product();
        $this->sell('product', $product->id, ['mode' => 'subscription', 'monthly' => 1000]);

        $this->sell('product', $product->id, ['mode' => 'quote'])->assertSessionHasNoErrors();

        $this->assertTrue($product->fresh()->isQuoteOnly());
        $this->get('/products/business-tools/suite')->assertSee('Request a Quote')->assertDontSee('ETB 1,000.00')->assertDontSee('value="checkout"', false);
        $this->assertEquals(1000, $this->plans($product)->sole()->term_prices['monthly']);

        $this->sell('product', $product->id, ['mode' => 'subscription', 'monthly' => 1000]);
        $this->assertFalse($product->fresh()->isQuoteOnly());
        $this->get('/products/business-tools/suite')->assertSee('ETB 1,000.00');
    }

    public function test_switching_several_plans_to_fixed_and_back_loses_no_period_price(): void
    {
        $product = $this->product();
        foreach ([['A', 1000], ['B', 2000]] as [$name, $monthly]) {
            ItemOption::create(['item_type' => 'product', 'item_id' => $product->id, 'name' => $name, 'price_amount' => $monthly, 'currency' => 'ETB', 'billing_term' => 'monthly',
                'term_prices' => ['monthly' => $monthly, 'annually' => $monthly * 10], 'show_price' => true, 'is_ready' => true, 'is_active' => true]);
        }

        $this->sell('product', $product->id, ['mode' => 'fixed'])->assertSessionHasNoErrors();
        $this->assertSame([['one-time'], ['one-time']], $this->plans($product)->map(fn ($p) => array_keys($p->getAvailableTerms()))->all());

        $this->sell('product', $product->id, ['mode' => 'subscription']);
        $this->assertEquals(20000, $this->plans($product)->last()->getPriceForTerm('annually'));
    }

    public function test_only_price_roles_may_change_it_and_a_price_is_required(): void
    {
        $product = $this->product();

        $this->sell('product', $product->id, ['mode' => 'fixed', 'price' => 10], 'content_editor')->assertForbidden();
        $this->sell('product', $product->id, ['mode' => 'fixed'])->assertSessionHasErrors('price');
        $this->sell('product', $product->id, ['mode' => 'subscription'])->assertSessionHasErrors('monthly');
        $this->sell('product', $product->id, ['mode' => 'free'])->assertSessionHasErrors('mode');
        $this->assertCount(0, $this->plans($product));
    }

    public function test_the_edit_pages_show_the_box_and_saving_the_item_keeps_the_choice(): void
    {
        $service = $this->service(null, ['slug' => 'cool']);
        $this->option($service);
        $this->sell('service', $service->id, ['mode' => 'quote']);

        $this->flushSession();
        $this->actingAs($this->staff('super_admin'))->get("/admin/services/{$service->id}/edit")->assertOk()
            ->assertSee('How is this sold?')->assertSee('value="quote" checked', false)->assertDontSee('name="quote_only"', false);
        $this->put("/admin/services/{$service->id}", ['slug' => 'cool', 'title' => 'Cool', 'desc' => 'D', 'features' => ['A'], 'category_id' => $service->category_id])
            ->assertSessionHasNoErrors();
        $this->assertTrue($service->fresh()->quote_only);

        $this->get('/admin/products/'.$this->product()->id.'/edit')->assertSee('How is this sold?');
    }
}
