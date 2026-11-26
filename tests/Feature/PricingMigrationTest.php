<?php

namespace Tests\Feature;

use App\Models\ItemOption;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The data migrations will run on live later: they must never overwrite, duplicate or delete anything. */
class PricingMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const FILES = [
        '2026_11_26_120200_migrate_existing_catalog_prices_to_item_options',
        '2026_11_26_130100_seed_sample_multi_term_subscription_prices',
        '2026_11_26_140000_populate_full_catalog_subscription_pricing',
        '2026_11_26_150000_ensure_etourism_and_datacenter_pricing',
    ];

    public function test_up_and_down_keep_entered_prices_and_add_no_catalog_items(): void
    {
        $service = $this->service(null, ['slug' => 'tech-assistance', 'price' => '490/hr']);
        $mine = $this->option($service, ['name' => 'Standard Subscription', 'price_amount' => 123, 'needs_review' => true]);
        Product::create(['category' => 'business-tools', 'slug' => 'erp', 'name' => 'ERP', 'image' => 'x', 'description' => 'd', 'price' => 350000, 'features' => []]);

        foreach (self::FILES as $file) {
            $migration = require database_path("migrations/{$file}.php");
            $migration->up();
            $migration->up();
            $migration->down();
        }

        $this->assertSame('123.00', $mine->fresh()?->price_amount);
        $this->assertSame(1, ItemOption::where('item_type', 'service')->where('item_id', $service->id)->count());
        $this->assertSame(1, ItemOption::where('item_type', 'product')->count());
        $this->assertSame(1, Product::count());
    }
}
