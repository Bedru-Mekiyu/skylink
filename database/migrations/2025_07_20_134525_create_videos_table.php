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
        
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique(); // For clean URLs like /videos/our-new-product
            $table->text('description')->nullable();
            $table->string('video_url'); // To store the YouTube or Vimeo URL
            $table->string('thumbnail_image')->nullable(); // An optional custom thumbnail
            // Use the same category table as news, assuming 'News', 'Innovations', 'Events' are in there
            $table->foreignId('news_category_id')->constrained()->onDelete('cascade');
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
