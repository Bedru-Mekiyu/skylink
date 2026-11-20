<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin > Homepage Video: which uploaded files the homepage hero plays.
 * One row at most. With no row (or an empty "current") the homepage keeps playing the built-in
 * files in public/assets/videos/hero/, so nothing changes until the owner uploads. Additive.
 *
 * current / previous: JSON {desktop_mp4, desktop_webm, mobile_mp4, poster}, paths on the public disk.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_22_100000_create_hero_video_settings_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_video_settings', function (Blueprint $table) {
            $table->id();
            $table->json('current')->nullable();
            $table->json('previous')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_video_settings');
    }
};
