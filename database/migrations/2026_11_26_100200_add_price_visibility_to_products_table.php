<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive price visibility switch and freeform price text for products.
 * Seeds price_text with the existing 'ETB <formatted>' so day-one display is unchanged.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_26_100200_add_price_visibility_to_products_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_price')->default(true)->after('price');
            $table->string('price_text')->nullable()->after('show_price');
        });

        // Seed price_text from existing prices so existing products maintain current display
        foreach (DB::table('products')->get() as $p) {
            if ($p->price !== null) {
                DB::table('products')->where('id', $p->id)->update([
                    'price_text' => 'ETB ' . number_format($p->price),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['show_price', 'price_text']);
        });
    }
};
