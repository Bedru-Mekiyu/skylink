<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Additive only: creates product_categories, links products to it, and backfills one
 * category per distinct legacy products.category string. products.category is kept.
 *
 * pricing_mode: subscription (tiers + billing terms), one_time (single price), quote_only (no price).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('pricing_mode', 20)->default('one_time')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable()->after('category')
                ->constrained('product_categories')->nullOnDelete();
        });

        $slugs = DB::table('products')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        foreach ($slugs as $i => $slug) {
            $isQuoteOnly = (bool) preg_match('/data-?center|power|energy|solar/i', $slug);

            $hasTerms = ! $isQuoteOnly && DB::table('item_options')
                ->where('item_type', 'product')
                ->whereIn('item_id', DB::table('products')->where('category', $slug)->select('id'))
                ->whereNotNull('term_prices')
                ->where('term_prices', '!=', '')
                ->where('term_prices', '!=', '[]')
                ->where('term_prices', '!=', 'null')
                ->exists();

            $id = DB::table('product_categories')->insertGetId([
                'slug' => $slug,
                'name' => Str::title(str_replace('-', ' ', $slug)),
                'pricing_mode' => $isQuoteOnly ? 'quote_only' : ($hasTerms ? 'subscription' : 'one_time'),
                'sort_order' => $i,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('category', $slug)->update(['product_category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_category_id');
        });
        Schema::dropIfExists('product_categories');
    }
};
