<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

/** Every public page except the home page offers a way back, pointing to the page above it. */
class BackButtonTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private function back(string $path): ?array
    {
        $html = $this->get($path)->assertOk()->getContent();
        preg_match_all('/<a[^>]*data-back[^>]*>/', $html, $m);
        $this->assertLessThanOrEqual(1, count($m[0]), $path);
        if (! $m[0]) {
            return null;
        }
        preg_match('/href="([^"]*)"/', $m[0][0], $href);
        preg_match('/aria-label="([^"]*)"/', $m[0][0], $label);

        return [str_replace('http://localhost', '', $href[1]) ?: '/', html_entity_decode($label[1])];
    }

    public function test_each_page_points_back_to_the_page_above_it(): void
    {
        $category = $this->category(['slug' => 'back-cat', 'name' => 'Back Category']);
        $service = $this->service($category, ['slug' => 'back-svc']);
        $this->option($service);
        Product::create(['name' => 'Back Product', 'slug' => 'back-product', 'category' => 'business-tools', 'description' => 'D', 'image' => 'x.jpg', 'features' => ['A'], 'price' => 1]);

        $this->assertNull($this->back('/'));
        $expected = [
            '/about' => ['/', 'Back to Home'],
            '/services' => ['/', 'Back to Home'],
            '/products' => ['/', 'Back to Home'],
            '/portfolio' => ['/', 'Back to Home'],
            '/news' => ['/', 'Back to Home'],
            '/contact' => ['/', 'Back to Home'],
            '/cart' => ['/products', 'Back to Products'],
            '/services/category/back-cat' => ['/services', 'Back to Services'],
            '/services/detail/back-svc' => ['/services/category/back-cat', 'Back to Back Category'],
            '/products/business-tools/back-product' => ['/products', 'Back to Products'],
        ];
        foreach ($expected as $path => $want) {
            $this->assertSame($want, $this->back($path), $path);
        }
    }

    public function test_sign_in_has_a_way_back_to_the_site(): void
    {
        $this->assertSame(['/', 'Back to Home'], $this->back('/login'));
    }

    public function test_the_navbar_arrow_is_for_phones_and_tablets_not_laptops(): void
    {
        $this->assertMatchesRegularExpression('/@media \(min-width: 992px\) \{ \.sky-nav-start \.sky-back \{ display: none; \} \}/', file_get_contents(public_path('assets/css/sky-brand.css')));
    }
}
