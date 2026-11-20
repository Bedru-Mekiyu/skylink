<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Deploy-day fix: the "Learning Management System (LMS)" product (id 6) was saved with a Bing thumbnail address that
 * no longer works. This puts the site's own illustration (public/assets/images/products/) on it.
 * Idempotent: it only acts when the product's image is still that dead Bing address; otherwise it does nothing.
 */
class FixLmsProductImage extends Command
{
    protected $signature = 'products:fix-lms-image {--id=6 : Product id}';

    protected $description = 'Replace the dead Bing image of the LMS product with the site\'s own image (does nothing if already changed)';

    public const FILE = 'assets/images/products/lms-learning-platform.webp';

    public function handle(): int
    {
        $product = Product::find((int) $this->option('id'));
        if (! $product) {
            $this->warn('Product not found: nothing to do.');

            return self::SUCCESS;
        }
        if (! $this->isDeadBingUrl((string) $product->image)) {
            $this->info('Image is not the dead Bing address: left unchanged.');

            return self::SUCCESS;
        }
        if (! is_file(public_path(self::FILE))) {
            $this->error('public/'.self::FILE.' is missing: upload the release first.');

            return self::FAILURE;
        }
        // Absolute URL on this site: the admin form and the public pages both expect a full URL.
        $product->update(['image' => url(self::FILE)]);
        $this->info("Product {$product->id} ({$product->name}) now uses ".url(self::FILE));

        return self::SUCCESS;
    }

    private function isDeadBingUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'bing.net' || str_ends_with($host, '.bing.net') || $host === 'th.bing.com';
    }
}
