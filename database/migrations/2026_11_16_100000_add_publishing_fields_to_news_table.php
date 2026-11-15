<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: publishing workflow for news.
 *
 * Existing articles stay exactly as they are on the site: they become
 * "published", their publish date is their created_at, and their body is
 * marked as plain text so it is rendered the same way as before.
 *
 * Rollback (php artisan migrate:rollback --step=1) drops the new columns
 * and the redirect table. It does not touch titles, bodies or images.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->after('image');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->string('language', 5)->nullable()->after('published_at');
            $table->string('url_title')->nullable()->after('language');
            $table->boolean('is_featured')->default(false)->after('url_title');
            $table->string('content_format', 10)->default('text')->after('is_featured');
            $table->string('image_thumb')->nullable()->after('content_format');
            $table->string('image_original')->nullable()->after('image_thumb');

            $table->index(['status', 'published_at']);
        });

        DB::table('news')->whereNull('published_at')->update(['published_at' => DB::raw('created_at')]);

        // An old slug of an article whose English URL title was changed.
        // The public URL /news/<old_slug> answers with a 301 to the current one.
        Schema::create('news_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->string('old_slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_slug_redirects');

        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
            $table->dropColumn([
                'status', 'published_at', 'language', 'url_title',
                'is_featured', 'content_format', 'image_thumb', 'image_original',
            ]);
        });
    }
};
