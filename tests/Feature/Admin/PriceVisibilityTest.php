<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PriceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    public function test_guests_and_non_admins_cannot_reach_settings_routes(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.update'), ['show_prices' => '1'])->assertRedirect(route('login'));

        $regular = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regular)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($regular)->put(route('admin.settings.update'), ['show_prices' => '1'])->assertForbidden();
    }

    public function test_admin_can_view_and_update_settings(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));
        $response->assertOk();
        $response->assertSee('Site Settings');
        $response->assertSee('Show prices on the public site');

        // Toggle off
        $res = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'show_prices' => '0',
        ]);
        $res->assertRedirect(route('admin.settings.edit'));
        $this->assertFalse(SiteSetting::showPrices());

        // Toggle on
        $res = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'show_prices' => '1',
        ]);
        $res->assertRedirect(route('admin.settings.edit'));
        $this->assertTrue(SiteSetting::showPrices());
    }

    public function test_service_price_visible_when_both_switches_are_on(): void
    {
        SiteSetting::set('show_prices', '1');

        $category = ServiceCategory::create(['name' => 'IT Infrastructure', 'slug' => 'it-infrastructure']);
        $service = Service::create([
            'category_id' => $category->id,
            'title' => 'CCTV Security',
            'slug' => 'cctv-security',
            'desc' => 'Complete security solution',
            'price' => 'From 15,000 ETB',
            'show_price' => true,
            'features' => ['HD cameras', '24/7 recording'],
            'image' => 'assets/images/placeholder.png',
        ]);

        $response = $this->get(route('services.detail', ['subcategory' => $service->slug]));
        $response->assertOk();
        $response->assertSee('From 15,000 ETB');

        $catResponse = $this->get(route('services.category', ['category' => $category->slug]));
        $catResponse->assertOk();
        $catResponse->assertSee('Price:');
        $catResponse->assertSee('From 15,000 ETB');
    }

    public function test_service_price_hidden_when_global_switch_is_off(): void
    {
        SiteSetting::set('show_prices', '0');

        $category = ServiceCategory::create(['name' => 'IT Infrastructure', 'slug' => 'it-infrastructure']);
        $service = Service::create([
            'category_id' => $category->id,
            'title' => 'CCTV Security',
            'slug' => 'cctv-security',
            'desc' => 'Complete security solution',
            'price' => 'From 15,000 ETB',
            'show_price' => true,
            'features' => ['HD cameras', '24/7 recording'],
            'image' => 'assets/images/placeholder.png',
        ]);

        $response = $this->get(route('services.detail', ['subcategory' => $service->slug]));
        $response->assertOk();
        $response->assertDontSee('From 15,000 ETB');

        $catResponse = $this->get(route('services.category', ['category' => $category->slug]));
        $catResponse->assertOk();
        $catResponse->assertDontSee('Price:');
        $catResponse->assertDontSee('From 15,000 ETB');
    }

    public function test_service_price_hidden_when_item_switch_is_off(): void
    {
        SiteSetting::set('show_prices', '1');

        $category = ServiceCategory::create(['name' => 'IT Infrastructure', 'slug' => 'it-infrastructure']);
        $service = Service::create([
            'category_id' => $category->id,
            'title' => 'CCTV Security',
            'slug' => 'cctv-security',
            'desc' => 'Complete security solution',
            'price' => 'From 15,000 ETB',
            'show_price' => false,
            'features' => ['HD cameras', '24/7 recording'],
            'image' => 'assets/images/placeholder.png',
        ]);

        $response = $this->get(route('services.detail', ['subcategory' => $service->slug]));
        $response->assertOk();
        $response->assertDontSee('From 15,000 ETB');

        $catResponse = $this->get(route('services.category', ['category' => $category->slug]));
        $catResponse->assertOk();
        $catResponse->assertDontSee('Price:');
        $catResponse->assertDontSee('From 15,000 ETB');
    }

    public function test_na_fallback_is_never_rendered(): void
    {
        SiteSetting::set('show_prices', '1');

        $category = ServiceCategory::create(['name' => 'IT Infrastructure', 'slug' => 'it-infrastructure']);
        $service = Service::create([
            'category_id' => $category->id,
            'title' => 'Consulting Service',
            'slug' => 'consulting-service',
            'desc' => 'Free consultation',
            'price' => null,
            'show_price' => true,
            'features' => ['Audit', 'Report'],
            'image' => 'assets/images/placeholder.png',
        ]);

        $catResponse = $this->get(route('services.category', ['category' => $category->slug]));
        $catResponse->assertOk();
        $catResponse->assertDontSee('Price: N/A');
    }

    public function test_product_price_visible_when_both_switches_are_on(): void
    {
        SiteSetting::set('show_prices', '1');

        $product = Product::create([
            'category' => 'business-tools',
            'slug' => 'point-of-sale-pos-system',
            'name' => 'Point of Sale (POS) System',
            'image' => 'https://example.com/pos.png',
            'description' => 'Robust POS system',
            'price' => 30000,
            'price_text' => 'From 30,000 ETB',
            'show_price' => true,
            'features' => ['Inventory tracking'],
        ]);

        $response = $this->get(route('products'));
        $response->assertOk();
        $response->assertSee('From 30,000 ETB');
    }

    public function test_product_price_hidden_when_global_switch_is_off(): void
    {
        SiteSetting::set('show_prices', '0');

        $product = Product::create([
            'category' => 'business-tools',
            'slug' => 'point-of-sale-pos-system',
            'name' => 'Point of Sale (POS) System',
            'image' => 'https://example.com/pos.png',
            'description' => 'Robust POS system',
            'price' => 30000,
            'price_text' => 'From 30,000 ETB',
            'show_price' => true,
            'features' => ['Inventory tracking'],
        ]);

        $response = $this->get(route('products'));
        $response->assertOk();
        $response->assertDontSee('From 30,000 ETB');
        $response->assertDontSee('ETB 30,000');
    }

    public function test_product_price_hidden_when_item_switch_is_off(): void
    {
        SiteSetting::set('show_prices', '1');

        $product = Product::create([
            'category' => 'business-tools',
            'slug' => 'point-of-sale-pos-system',
            'name' => 'Point of Sale (POS) System',
            'image' => 'https://example.com/pos.png',
            'description' => 'Robust POS system',
            'price' => 30000,
            'price_text' => 'From 30,000 ETB',
            'show_price' => false,
            'features' => ['Inventory tracking'],
        ]);

        $response = $this->get(route('products'));
        $response->assertOk();
        $response->assertDontSee('From 30,000 ETB');
        $response->assertDontSee('ETB 30,000');
    }

    public function test_no_price_in_html_or_jsonld_when_hidden(): void
    {
        SiteSetting::set('show_prices', '0');

        $product = Product::create([
            'category' => 'business-tools',
            'slug' => 'erp-system',
            'name' => 'ERP System',
            'image' => 'https://example.com/erp.png',
            'description' => 'Complete ERP system',
            'price' => 50000,
            'price_text' => 'ETB 50,000',
            'show_price' => true,
            'features' => ['Finance', 'HR'],
        ]);

        $response = $this->get(route('products'));
        $content = $response->getContent();
        $this->assertStringNotContainsString('50,000', $content);
        $this->assertStringNotContainsString('"price"', $content);
        $this->assertStringNotContainsString('"offers"', $content);
    }
}
