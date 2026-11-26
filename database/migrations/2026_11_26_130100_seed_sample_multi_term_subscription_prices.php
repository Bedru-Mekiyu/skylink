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
        $sampleProducts = [
            'erp' => [
                'name' => 'Full ERP Solution',
                'monthly' => 15757.90,
                'quarterly' => 44910.00,
                'semi-annually' => 85092.60,
                'annually' => 151275.70,
                'short_features' => ['Complete ERP Suite', 'Multi-currency & Invoicing', 'Role-based Access'],
            ],
            'lms' => [
                'name' => 'LMS Enterprise Plan',
                'monthly' => 12500.00,
                'quarterly' => 35625.00,
                'semi-annually' => 67500.00,
                'annually' => 120000.00,
                'short_features' => ['Student Portal', 'Course Builder', 'Certificate Generation'],
            ],
            'school-management' => [
                'name' => 'School Core Package',
                'monthly' => 9800.00,
                'quarterly' => 27930.00,
                'semi-annually' => 52920.00,
                'annually' => 94080.00,
                'short_features' => ['Attendance & Grading', 'Fee Management', 'Parent SMS Alerts'],
            ],
            'fleet-management' => [
                'name' => 'Fleet FleetGuard Plan',
                'monthly' => 14200.00,
                'quarterly' => 40470.00,
                'semi-annually' => 76680.00,
                'annually' => 136320.00,
                'short_features' => ['GPS Live Tracking', 'Fuel Consumption Logs', 'Maintenance Scheduler'],
            ],
        ];

        foreach ($sampleProducts as $slug => $data) {
            $product = DB::table('products')->where('slug', $slug)->first();
            if (!$product) continue;

            // STRICT INSERT-ONLY: Only insert if item has NO option. Never update or overwrite existing prices.
            $hasOption = DB::table('item_options')->where('item_type', 'product')->where('item_id', $product->id)->exists();
            if (!$hasOption) {
                DB::table('item_options')->insert([
                    'item_type' => 'product',
                    'item_id' => $product->id,
                    'name' => $data['name'],
                    'short_features' => json_encode($data['short_features']),
                    'price_amount' => $data['monthly'],
                    'price_display_text' => number_format($data['monthly'], 2) . ' Br',
                    'currency' => 'ETB',
                    'billing_term' => 'monthly',
                    'term_prices' => json_encode([
                        'monthly' => $data['monthly'],
                        'quarterly' => $data['quarterly'],
                        'semi-annually' => $data['semi-annually'],
                        'annually' => $data['annually'],
                    ]),
                    'show_price' => 1,
                    'show_billing_term' => 1,
                    'is_ready' => 1,
                    'needs_review' => 1,
                    'is_active' => 1,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /** Deliberately does nothing: rolling back must never delete prices (some may since have been edited). */
    public function down(): void
    {
    }
};
