<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $productSubscriptions = [
            'real-estate-management' => 16500.00,
            'project-management'     => 10800.00,
            'e-tourism'              => 11200.00,
            'smart-e-ticketing'      => 13800.00,
            'document-management'    => 8500.00,
            'traffic-penalty'        => 18000.00,
            'appointment-booking'    => 6500.00,
            'qms'                    => 7900.00,
            'building-management'    => 14000.00,
            'event-management'       => 9200.00,
            'customer-support'       => 8000.00,
            'e-service'              => 12000.00,
            'entertainment-apps'     => 7500.00,
            'system-maintenance'     => 15000.00,
            'security-compliance'    => 22000.00,
            'e-commerce'             => 11500.00,
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($productSubscriptions as $slug => $monthly) {
            $product = DB::table('products')->where('slug', $slug)->first();
            if (!$product) continue;

            // STRICT INSERT-ONLY: Only insert if item has NO option. Never update or overwrite existing prices.
            $hasOption = DB::table('item_options')->where('item_type', 'product')->where('item_id', $product->id)->exists();
            if (!$hasOption) {
                $quarterly = round($monthly * 3 * 0.95, 2);      // 5% discount
                $semiAnnual = round($monthly * 6 * 0.90, 2);     // 10% discount
                $annual = round($monthly * 12 * 0.80, 2);        // 20% discount

                DB::table('item_options')->insert([
                    'item_type'          => 'product',
                    'item_id'            => $product->id,
                    'name'               => 'Standard Subscription',
                    'short_features'     => $product->features ? json_encode(array_slice(json_decode($product->features, true) ?: [], 0, 3)) : null,
                    'price_amount'       => $monthly,
                    'price_display_text' => number_format($monthly, 2) . ' Br',
                    'currency'           => 'ETB',
                    'billing_term'       => 'monthly',
                    'term_prices'        => json_encode([
                        'monthly'       => $monthly,
                        'quarterly'     => $quarterly,
                        'semi-annually' => $semiAnnual,
                        'annually'      => $annual,
                    ]),
                    'show_price'         => 1,
                    'show_billing_term'  => 1,
                    'is_ready'           => 1,
                    'needs_review'       => 1,
                    'is_active'          => 1,
                    'sort_order'         => 1,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }

        $serviceSubscriptions = [
            'tech-assistance'    => 12000.00,
            'system-maintenance' => 15000.00,
            'security-privacy'   => 18500.00,
            'network-security'   => 25000.00,
            'endpoint-security'  => 14000.00,
            'data-protection'    => 16000.00,
            'access-management'  => 13500.00,
            'fleet-management'   => 8500.00,
            'asset-tracking'     => 6800.00,
            'vehicle-tracking'   => 7200.00,
            'datacenter-infra'   => 35000.00,
            'server-rooms'       => 20000.00,
            'ups-power'          => 11000.00,
            'cooling-system'     => 12500.00,
        ];

        foreach ($serviceSubscriptions as $slug => $monthly) {
            $service = DB::table('services')->where('slug', $slug)->first();
            if (!$service) continue;

            // STRICT INSERT-ONLY: Only insert if item has NO option. Never update or overwrite existing prices.
            $hasOption = DB::table('item_options')->where('item_type', 'service')->where('item_id', $service->id)->exists();
            if (!$hasOption) {
                $quarterly = round($monthly * 3 * 0.95, 2);
                $semiAnnual = round($monthly * 6 * 0.90, 2);
                $annual = round($monthly * 12 * 0.80, 2);

                DB::table('item_options')->insert([
                    'item_type'          => 'service',
                    'item_id'            => $service->id,
                    'name'               => 'Standard Subscription',
                    'short_features'     => $service->features ? json_encode(array_slice(json_decode($service->features, true) ?: [], 0, 3)) : null,
                    'price_amount'       => $monthly,
                    'price_display_text' => number_format($monthly, 2) . ' Br',
                    'currency'           => 'ETB',
                    'billing_term'       => 'monthly',
                    'term_prices'        => json_encode([
                        'monthly'       => $monthly,
                        'quarterly'     => $quarterly,
                        'semi-annually' => $semiAnnual,
                        'annually'      => $annual,
                    ]),
                    'show_price'         => 1,
                    'show_billing_term'  => 1,
                    'is_ready'           => 1,
                    'needs_review'       => 1,
                    'is_active'          => 1,
                    'sort_order'         => 1,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }
    }

    /** Deliberately does nothing: rolling back must never delete prices (some may since have been edited). */
    public function down(): void
    {
    }
};
