<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Migrate each service in current environment's database
        $services = DB::table('services')->get();
        foreach ($services as $service) {
            // Insert only: an item that already has an option is left exactly as it is.
            if (DB::table('item_options')->where('item_type', 'service')->where('item_id', $service->id)->exists()) {
                continue;
            }
            $rawPrice = $service->price;
            $currency = 'ETB';
            $priceAmount = null;
            $billingTerm = 'one-time';

            if (!empty($rawPrice)) {
                $rawLower = strtolower(trim($rawPrice));
                if (str_contains($rawPrice, '$')) {
                    $currency = 'USD';
                }

                // Check clean numeric extraction
                // e.g. "$199/mo" -> 199.00; "$49/hr" -> 49.00
                if (preg_match('/\$?\s*([0-9]+(?:\.[0-9]+)?)/', $rawPrice, $matches)) {
                    // Only assign if it's a direct rate, not "Starting at..." or "Based on..."
                    if (!str_contains($rawLower, 'starting') && !str_contains($rawLower, 'based') && !str_contains($rawLower, 'custom') && !str_contains($rawLower, 'quote')) {
                        $priceAmount = (float) $matches[1];
                    }
                }

                // Infer billing term
                if (str_contains($rawLower, '/mo') || str_contains($rawLower, 'month')) {
                    $billingTerm = 'monthly';
                } elseif (str_contains($rawLower, '/quarter') || str_contains($rawLower, 'quarter')) {
                    $billingTerm = 'quarterly';
                } elseif (str_contains($rawLower, '/yr') || str_contains($rawLower, 'annual')) {
                    $billingTerm = 'annually';
                } elseif (str_contains($rawLower, '/hr') || str_contains($rawLower, 'project') || str_contains($rawLower, 'engagement')) {
                    $billingTerm = 'project-based';
                } else {
                    $billingTerm = 'one-time';
                }
            }

            // Extract up to 3 features
            $shortFeatures = null;
            if (!empty($service->features)) {
                $features = json_decode($service->features, true);
                if (is_array($features)) {
                    $shortFeatures = json_encode(array_slice($features, 0, 3));
                }
            }

            DB::table('item_options')->insert([
                'item_type' => 'service',
                'item_id' => $service->id,
                'name' => 'Standard Plan',
                'short_features' => $shortFeatures,
                'price_amount' => $priceAmount,
                'price_display_text' => $rawPrice ?: 'Quote on Request',
                'currency' => $currency,
                'billing_term' => $billingTerm,
                'show_billing_term' => true,
                'show_price' => (bool) ($service->show_price ?? true),
                'is_active' => true,
                'is_ready' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Migrate each product in current environment's database
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            // Insert only: an item that already has an option is left exactly as it is.
            if (DB::table('item_options')->where('item_type', 'product')->where('item_id', $product->id)->exists()) {
                continue;
            }
            $rawPrice = $product->price;
            $priceText = $product->price_text;
            $priceAmount = is_numeric($rawPrice) ? (float) $rawPrice : null;
            $displayText = $priceText ?: ($priceAmount !== null ? 'ETB ' . number_format($priceAmount) : 'Quote on Request');

            $shortFeatures = null;
            if (!empty($product->features)) {
                $features = json_decode($product->features, true);
                if (is_array($features)) {
                    $shortFeatures = json_encode(array_slice($features, 0, 3));
                }
            }

            DB::table('item_options')->insert([
                'item_type' => 'product',
                'item_id' => $product->id,
                'name' => 'Standard License',
                'short_features' => $shortFeatures,
                'price_amount' => $priceAmount,
                'price_display_text' => $displayText,
                'currency' => 'ETB',
                'billing_term' => 'one-time',
                'show_billing_term' => true,
                'show_price' => (bool) ($product->show_price ?? true),
                'is_active' => true,
                'is_ready' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Deliberately does nothing: rolling back must never delete prices (some may since have been edited). */
    public function down(): void
    {
    }
};
