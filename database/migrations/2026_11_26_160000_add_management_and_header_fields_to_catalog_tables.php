<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('show_price');
            }
            if (!Schema::hasColumn('products', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_published');
            }
            if (!Schema::hasColumn('products', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_archived');
            }
            if (!Schema::hasColumn('products', 'header_title')) {
                $table->string('header_title')->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('products', 'header_subtitle')) {
                $table->text('header_subtitle')->nullable()->after('header_title');
            }
            if (!Schema::hasColumn('products', 'header_image')) {
                $table->string('header_image')->nullable()->after('header_subtitle');
            }
            if (!Schema::hasColumn('products', 'header_cta_text')) {
                $table->string('header_cta_text')->nullable()->after('header_image');
            }
            if (!Schema::hasColumn('products', 'name_am')) {
                $table->string('name_am')->nullable()->after('header_cta_text');
            }
            if (!Schema::hasColumn('products', 'description_am')) {
                $table->text('description_am')->nullable()->after('name_am');
            }
            if (!Schema::hasColumn('products', 'features_am')) {
                $table->json('features_am')->nullable()->after('description_am');
            }
            if (!Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // 2. Services table
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('show_price');
            }
            if (!Schema::hasColumn('services', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_published');
            }
            if (!Schema::hasColumn('services', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_archived');
            }
            if (!Schema::hasColumn('services', 'header_title')) {
                $table->string('header_title')->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('services', 'header_subtitle')) {
                $table->text('header_subtitle')->nullable()->after('header_title');
            }
            if (!Schema::hasColumn('services', 'header_image')) {
                $table->string('header_image')->nullable()->after('header_subtitle');
            }
            if (!Schema::hasColumn('services', 'header_cta_text')) {
                $table->string('header_cta_text')->nullable()->after('header_image');
            }
            if (!Schema::hasColumn('services', 'title_am')) {
                $table->string('title_am')->nullable()->after('header_cta_text');
            }
            if (!Schema::hasColumn('services', 'desc_am')) {
                $table->text('desc_am')->nullable()->after('title_am');
            }
            if (!Schema::hasColumn('services', 'features_am')) {
                $table->json('features_am')->nullable()->after('desc_am');
            }
            if (!Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // 3. Service Categories table
        Schema::table('service_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('service_categories', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_published');
            }
            if (!Schema::hasColumn('service_categories', 'header_title')) {
                $table->string('header_title')->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('service_categories', 'header_subtitle')) {
                $table->text('header_subtitle')->nullable()->after('header_title');
            }
            if (!Schema::hasColumn('service_categories', 'header_image')) {
                $table->string('header_image')->nullable()->after('header_subtitle');
            }
            if (!Schema::hasColumn('service_categories', 'header_cta_text')) {
                $table->string('header_cta_text')->nullable()->after('header_image');
            }
            if (!Schema::hasColumn('service_categories', 'name_am')) {
                $table->string('name_am')->nullable()->after('header_cta_text');
            }
            if (!Schema::hasColumn('service_categories', 'description_am')) {
                $table->text('description_am')->nullable()->after('name_am');
            }
            if (!Schema::hasColumn('service_categories', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // 4. Item Options table
        Schema::table('item_options', function (Blueprint $table) {
            if (!Schema::hasColumn('item_options', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->after('is_ready');
            }
            if (!Schema::hasColumn('item_options', 'is_payable_online')) {
                $table->boolean('is_payable_online')->default(true)->after('needs_review');
            }
            if (!Schema::hasColumn('item_options', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            $cols = ['needs_review', 'is_payable_online', 'deleted_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('item_options', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $cols = ['is_archived', 'header_title', 'header_subtitle', 'header_image', 'header_cta_text', 'name_am', 'description_am', 'deleted_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('service_categories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('services', function (Blueprint $table) {
            $cols = ['is_published', 'is_archived', 'sort_order', 'header_title', 'header_subtitle', 'header_image', 'header_cta_text', 'title_am', 'desc_am', 'features_am', 'deleted_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $cols = ['is_published', 'is_archived', 'sort_order', 'header_title', 'header_subtitle', 'header_image', 'header_cta_text', 'name_am', 'description_am', 'features_am', 'deleted_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
