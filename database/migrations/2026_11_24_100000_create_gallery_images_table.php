<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin > Gallery: photos of our work and our time with partner and client companies (About page).
 * One simple collection (no categories). Web copies (thumbnail + display size) are on the public disk;
 * the originals are archived privately. Additive; nothing else changes until photos are uploaded
 * (no photos = the gallery section is not rendered at all).
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_24_100000_create_gallery_images_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->string('caption_en', 200)->nullable();
            $table->string('caption_am', 200)->nullable();
            $table->date('taken_on')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visible')->default(true);
            $table->string('thumb_path', 255);          // ~480 px wide WebP
            $table->string('display_path', 255);        // ~1600 px wide WebP (lightbox)
            $table->unsignedSmallInteger('thumb_w');
            $table->unsignedSmallInteger('thumb_h');
            $table->unsignedSmallInteger('display_w');
            $table->unsignedSmallInteger('display_h');
            $table->string('original_path', 255)->nullable(); // private archive
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_images');
    }
};
