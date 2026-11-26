<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

class HomeProductLinksTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private function links(): array
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match_all('/<a href="([^"]*)" class="stretched-link"([^>]*)>([^<]*)<\/a>/', $html, $m, PREG_SET_ORDER);

        return array_map(fn ($a) => [trim($a[3]), html_entity_decode(str_replace('http://localhost', '', $a[1])), str_contains($a[2], 'tabindex="-1"')], $m);
    }

    public function test_each_featured_product_card_opens_its_page_and_view_more_opens_the_shop(): void
    {
        Product::create(['name' => 'E-Commerce', 'slug' => 'e-commerce', 'category' => 'web-mobile-apps', 'description' => 'D', 'image' => 'x.jpg', 'features' => ['A'], 'price' => 1]);

        $this->service($this->category(['slug' => 'smart-city']), ['slug' => 'physical-security']);

        $links = collect($this->links())->filter(fn ($l) => ! $l[2]); // the first set; the repeat is for the animation only
        $to = $links->mapWithKeys(fn ($l) => [$l[0] => $l[1]]);

        $this->assertSame(5, $links->count());
        $this->assertSame('/products/web-mobile-apps/e-commerce', $to['Ecommerce Application']);
        // Camera hardware is sold on the shop (the owner's choice).
        $this->assertSame('https://kirbgebeya.com/category/Cameras--Optics-G2i6n', $to['Cctv Camera']);
        $this->assertSame('https://kirbgebeya.com/category/Cameras--Optics-G2i6n', $to['Security Camera']);
        $this->assertSame('/services/detail/physical-security', $to['Attendance Tracker']);
        $this->assertCount(5, array_filter($this->links(), fn ($l) => $l[2]));
        // The goods are sold on Kirb Gebeya (the owner's choice).
        $this->get('/')->assertSee('href="https://kirbgebeya.com/" class="btn-view-more"', false);
    }

    public function test_a_missing_page_never_gives_a_dead_link(): void
    {
        foreach ($this->links() as [$name, $url]) {
            $this->assertStringNotContainsString('e-commerce', $url, $name);
            $this->assertStringNotContainsString('physical-security', $url, $name);
        }
    }

    public function test_on_a_phone_the_item_comes_before_the_category_list(): void
    {
        $service = $this->service(null, ['slug' => 'first-on-phone']);
        $this->option($service);
        Product::create(['name' => 'First', 'slug' => 'first', 'category' => 'business-tools', 'description' => 'D', 'image' => 'x.jpg', 'features' => ['A'], 'price' => 1]);

        foreach (['/services/detail/first-on-phone', '/products/business-tools/first'] as $path) {
            $this->get($path)->assertOk()->assertSee('col-lg-3 order-2 order-lg-1', false)->assertSee('col-lg-9 order-1 order-lg-2', false);
        }
    }
}
