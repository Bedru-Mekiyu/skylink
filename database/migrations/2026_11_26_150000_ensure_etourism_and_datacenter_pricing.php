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
        $now = date('Y-m-d H:i:s');

        $eTourism = DB::table('products')->where('slug', 'e-tourism')->first();
        if ($eTourism) {
            $hasOpt = DB::table('item_options')->where('item_type', 'product')->where('item_id', $eTourism->id)->exists();
            if (!$hasOpt) {
                // STRICT INSERT-ONLY: Only insert if item has NO option.
                $monthly = 11200.00;
                $quarterly = round($monthly * 3 * 0.95, 2);
                $semiAnnual = round($monthly * 6 * 0.90, 2);
                $annual = round($monthly * 12 * 0.80, 2);

                DB::table('item_options')->insert([
                    'item_type'          => 'product',
                    'item_id'            => $eTourism->id,
                    'name'               => 'Standard Cloud License',
                    'short_features'     => json_encode(['Multi-language Support', 'Integrated Payment Gateway', 'Mobile App Integration']),
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

        // 2. Ensure Data Center service subscription pricing is set for canonical slug 'data-center'
        $datacenter = DB::table('services')->where('slug', 'data-center')->first();
        if ($datacenter) {
            $hasOpt = DB::table('item_options')->where('item_type', 'service')->where('item_id', $datacenter->id)->exists();
            if (!$hasOpt) {
                // STRICT INSERT-ONLY: Only insert if item has NO option.
                $monthly = 35000.00;
                $quarterly = round($monthly * 3 * 0.95, 2);
                $semiAnnual = round($monthly * 6 * 0.90, 2);
                $annual = round($monthly * 12 * 0.80, 2);

                DB::table('item_options')->insert([
                    'item_type'          => 'service',
                    'item_id'            => $datacenter->id,
                    'name'               => 'Managed Infrastructure Plan',
                    'short_features'     => json_encode(['24/7 Monitoring', 'Redundant Cooling', 'Power Backup']),
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
