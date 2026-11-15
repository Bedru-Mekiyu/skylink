<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JPEG copy of the article image for share previews (og:image, twitter:image).
 * The site itself keeps using the WebP web copy and thumbnail.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_17_100000_add_image_share_to_news_table.php
 * drops only this column; share tags then fall back to the web copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('image_share')->nullable()->after('image_thumb');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('image_share');
        });
    }
};
