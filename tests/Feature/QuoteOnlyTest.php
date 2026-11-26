<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function powerService(): array
    {
        $category = $this->category(['slug' => 'datacenter-facility', 'pricing_mode' => 'quote_only']);
        $service = $this->service($category, ['slug' => 'ups-power', 'title' => 'UPS Power Backup', 'price' => '11000']);
        $option = $this->option($service, ['price_amount' => 11000, 'price_display_text' => '11,000.00 Br']);

        return [$category, $service, $option];
    }

    public function test_detail_page_shows_no_price_and_links_to_the_support_form(): void
    {
        [, $service] = $this->powerService();

        $this->get('/services/detail/'.$service->slug)->assertOk()
            ->assertDontSee('11,000')
            ->assertDontSee('Add to Cart')
            ->assertSee(e(route('contact', ['type' => 'support', 'service' => 'UPS Power Backup'])).'#contact-form', false);
    }

    public function test_category_page_shows_no_price_and_no_cart_button(): void
    {
        [$category] = $this->powerService();

        $this->get('/services/category/'.$category->slug)->assertOk()
            ->assertDontSee('11,000')->assertDontSee('Add to Cart')->assertSee('Request a Quote');
    }

    public function test_a_single_service_can_be_quote_only_inside_a_priced_category(): void
    {
        $service = $this->service(null, ['quote_only' => true]);
        $option = $this->option($service);

        $this->assertFalse($option->fresh()->isPriceVisible());
    }

    public function test_cart_refuses_quote_only_items(): void
    {
        [, $service, $option] = $this->powerService();

        $this->postJson('/cart/add', ['item_type' => 'service', 'item_id' => $service->id, 'option_id' => $option->id])
            ->assertStatus(422);
        $this->assertSame([], session('cart', []));
    }

    public function test_contact_page_preselects_support_and_the_service_name(): void
    {
        $this->get('/contact?type=support&service=UPS+Power+Backup')->assertOk()
            ->assertSee('<option value="support" selected', false)
            ->assertSee('value="UPS Power Backup"', false);
    }
}
