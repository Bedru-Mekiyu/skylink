<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControlTest extends TestCase
{
    use RefreshDatabase;

    public static function access(): array
    {
        // path => roles that may open it (super_admin always may)
        return [
            ['/admin/services', ['content_editor']],
            ['/admin/products', ['content_editor']],
            ['/admin/service-categories', ['content_editor']],
            ['/admin/news', ['content_editor']],
            ['/admin/partners', ['content_editor']],
            ['/admin/inbox', ['sales']],
            ['/admin/subscribers', ['sales']],
            ['/admin/prices-overview', ['finance']],
            ['/admin/orders', ['finance', 'sales']],
            ['/admin/billing', ['finance']],
            ['/admin/newsletter', []],
            ['/admin/settings', []],
            ['/admin/staff', []],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('access')]
    public function test_each_role_reaches_only_its_own_areas(string $path, array $allowed): void
    {
        $this->actingAs($this->staff('super_admin'))->get($path)->assertOk();

        foreach (['content_editor', 'sales', 'finance'] as $role) {
            $this->flushSession(); // a different person, a different browser
            $response = $this->actingAs($this->staff($role))->get($path);
            in_array($role, $allowed, true) ? $response->assertOk() : $response->assertForbidden();
        }
    }

    public function test_drafts_and_archived_items_are_not_public(): void
    {
        $category = $this->category(['slug' => 'network']);
        $draft = $this->service($category, ['slug' => 'draft-one', 'title' => 'Secret Draft', 'is_published' => false]);
        $this->service($category, ['slug' => 'old-one', 'title' => 'Old Archived', 'is_archived' => true]);
        $option = $this->option($draft);

        $this->get('/services/category/network')->assertOk()->assertDontSee('Secret Draft')->assertDontSee('Old Archived');
        $this->get('/services/detail/draft-one')->assertDontSee('Secret Draft');
        $this->get('/sitemap.xml')->assertDontSee('draft-one')->assertDontSee('old-one');
        $this->postJson('/cart/add', ['item_type' => 'service', 'item_id' => $draft->id, 'option_id' => $option->id])->assertStatus(422);
    }

    public function test_unticking_a_price_switch_really_hides_the_price(): void
    {
        $option = $this->option($this->service(), ['show_price' => true, 'is_payable_online' => true]);

        // An unticked checkbox is simply absent from the request.
        $this->actingAs($this->staff('finance'))->put('/admin/prices-overview/'.$option->id, [
            'name' => 'Plan A', 'price_amount' => 1000, 'currency' => 'ETB', 'billing_term' => 'monthly',
            'term_prices' => ['monthly' => 1000, 'annually' => 9000],
        ])->assertSessionHasNoErrors();

        $option->refresh();
        $this->assertFalse($option->show_price);
        $this->assertFalse($option->is_payable_online);
        $this->assertSame(9000.0, $option->getPriceForTerm('annually'));
    }

    public function test_admin_controls_quote_only_and_page_content(): void
    {
        $service = $this->service(null, ['slug' => 'cooling', 'price' => '5000']);
        $this->option($service, ['price_amount' => 5000]);

        $this->actingAs($this->staff('super_admin'))->put('/admin/services/'.$service->id, [
            'slug' => 'cooling', 'title' => 'Cooling', 'desc' => 'New description', 'features' => ['A'],
            'category_id' => $service->category_id, 'header_title' => 'Edited Hero Title', 'header_subtitle' => 'Edited subtitle',
            'is_published' => '1', 'quote_only' => '1',
        ])->assertSessionHasNoErrors();

        $this->get('/services/detail/cooling')->assertSee('Edited Hero Title')->assertSee('Edited subtitle')->assertDontSee('5,000');
    }

    public function test_a_tier_price_shows_without_the_old_item_price_field(): void
    {
        $service = $this->service(null, ['slug' => 'tiered']);
        $this->option($service, ['price_amount' => 750]);

        $this->get('/services/detail/tiered')->assertSee('750.00');
    }

    public function test_finance_can_edit_every_tier_price_without_content_access(): void
    {
        $service = $this->service(null, ['title' => 'Tiered Thing']);
        $this->option($service);
        $finance = $this->staff('finance');

        $this->actingAs($finance)->get("/admin/prices-overview/item/service/{$service->id}")
            ->assertOk()->assertSee('Tiered Thing')->assertSee('name="term_prices[annually]"', false);
        $this->actingAs($finance)->get('/admin/prices-overview')
            ->assertSee("/admin/prices-overview/item/service/{$service->id}", false);
        $this->flushSession();
        $this->actingAs($this->staff('sales'))->get("/admin/prices-overview/item/service/{$service->id}")->assertForbidden();
    }

    public function test_a_content_editor_cannot_change_prices_through_the_service_form(): void
    {
        $service = $this->service(null, ['price' => '100']);

        $this->actingAs($this->staff('content_editor'))->put('/admin/services/'.$service->id, [
            'slug' => $service->slug, 'title' => 'T', 'desc' => 'D', 'features' => ['A'],
            'category_id' => $service->category_id, 'price' => '1', 'is_published' => '1',
        ]);

        $this->assertSame('100', $service->fresh()->price);
    }

    public function test_a_price_change_is_what_the_cart_shows(): void
    {
        $service = $this->service();
        $option = $this->option($service, ['price_amount' => 1000]);
        $this->post('/cart/add', ['item_type' => 'service', 'item_id' => $service->id, 'option_id' => $option->id]);

        $option->update(['price_amount' => 2500]);

        $this->get('/cart')->assertSee('2,500.00')->assertDontSee('1,000.00');
    }
}
