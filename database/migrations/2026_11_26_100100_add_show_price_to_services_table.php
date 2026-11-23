<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive per-item price visibility switch for services.
 * Default is true so all existing services remain visible on day one.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_26_100100_add_show_price_to_services_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('show_price')->default(true)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('show_price');
        });
    }
};
