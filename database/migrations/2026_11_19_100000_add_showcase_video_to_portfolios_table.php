<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional showcase video for a portfolio project, and "Feature on top".
 *
 * video_mp4 / video_webm / video_poster hold either a clip from the site's own
 * library (public/assets/videos/portfolio/...) or an admin upload on the public
 * disk (portfolio_videos/...). All empty = the project shows its image only.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_19_100000_add_showcase_video_to_portfolios_table.php
 * drops only these four columns. The clip files themselves are not deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->string('video_mp4')->nullable();
            $table->string('video_webm')->nullable();
            $table->string('video_poster')->nullable();
            $table->boolean('is_featured')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['video_mp4', 'video_webm', 'video_poster', 'is_featured']);
        });
    }
};
