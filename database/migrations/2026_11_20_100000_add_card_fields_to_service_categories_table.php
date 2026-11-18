<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service categories become manageable in the admin: the /services cards and
 * filter buttons were hard-coded in services/index.blade.php. Additive only.
 * The current eight cards are written into the existing rows (empty fields
 * only), so the page looks exactly the same after migrating.
 */
return new class extends Migration
{
    /** The cards as they were hard-coded, in page order. */
    private const CARDS = [
        ['software-service', 'Software Service', 'Software Service', 'Software Service', 'Custom software solutions like web, mobile and enterprise platforms.', 'assets/images/services/software service.jpg'],
        ['smart-city', 'Smart City & Infrastructure', 'Smart City & Infrastructure', 'Infrastructure', 'Innovative infrastructure with EV charging, IoT and city automation.', 'assets/images/services/smart city.jpg'],
        ['safety-security', 'Safety & Security', 'Safety & Security', 'Security', 'Cybersecurity, surveillance systems, and access control solutions.', 'assets/images/services/security.jpg'],
        ['enterprise-network', 'Enterprise Network Solution', 'Enterprise Network', 'Networking', 'Secure, scalable LAN/WAN and cloud network infrastructure.', 'assets/images/services/enterprise networking.png'],
        ['consulting-training', 'Consulting & Training', 'Consulting & Training', 'Consulting', 'IT consulting, professional training, and strategy workshops.', 'assets/images/services/training-consulting.jpg'],
        ['ict-support', 'ICT Support', 'ICT Support', 'ICT Support', '24/7 technical support and remote system troubleshooting.', 'assets/images/services/ict support.jpg'],
        ['gps-tracking', 'GPS Tracking System', 'GPS Tracking', 'GPS Tracking', 'Fleet and asset tracking with real-time GPS and analytics.', 'assets/images/services/gps-tracking.png'],
        ['datacenter-facility', 'Datacenter Facility & Power', 'Data Center & Power', 'Data Center', 'Secure data centers with backup power and climate control.', 'assets/images/services/datacenter.png'],
    ];

    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('card_title')->nullable()->after('name');
            $table->string('filter_label', 60)->nullable()->after('card_title');
            $table->text('description')->nullable()->after('filter_label');
            $table->string('image')->nullable()->after('description');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('image');
            $table->boolean('is_published')->default(true)->after('sort_order');
        });

        foreach (self::CARDS as $i => [$slug, $name, $cardTitle, $filter, $description, $image]) {
            $row = DB::table('service_categories')->where('slug', $slug)->first();
            $values = ['card_title' => $cardTitle, 'filter_label' => $filter, 'description' => $description, 'image' => $image, 'sort_order' => ($i + 1) * 10];

            if ($row) {
                // Fill only what is still empty: never overwrite existing data.
                $fill = array_filter($values, fn ($v, $k) => $row->{$k} === null || $k === 'sort_order', ARRAY_FILTER_USE_BOTH);
                DB::table('service_categories')->where('id', $row->id)->update($fill + ['updated_at' => now()]);
            } else {
                DB::table('service_categories')->insert(['slug' => $slug, 'name' => $name, 'is_published' => true, 'created_at' => now(), 'updated_at' => now()] + $values);
            }
        }
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn(['card_title', 'filter_label', 'description', 'image', 'sort_order', 'is_published']);
        });
    }
};
