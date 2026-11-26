<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            $table->string('tagline', 160)->nullable();
            $table->boolean('is_recommended')->default(false);
        });
        Schema::table('products', fn (Blueprint $table) => $table->string('plans_heading', 150)->nullable());
        Schema::table('services', fn (Blueprint $table) => $table->string('plans_heading', 150)->nullable());
    }

    public function down(): void
    {
        Schema::table('item_options', fn (Blueprint $table) => $table->dropColumn(['tagline', 'is_recommended']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('plans_heading'));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('plans_heading'));
    }
};
