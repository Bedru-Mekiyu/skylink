<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Admin > Testimonials. The six testimonials that were hard-coded on the homepage and the Services page are imported in their order. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role', 160)->nullable();
            $table->text('quote_en')->nullable();
            $table->text('quote_am')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('original_path')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visible')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['visible', 'sort_order']);
        });

        $now = now();
        foreach (require database_path('data/testimonials-initial.php') as $i => $t) {
            DB::table('testimonials')->insert($t + ['sort_order' => $i + 1, 'visible' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials'); // uploaded photos in storage/app/public/testimonials stay on disk
    }
};
