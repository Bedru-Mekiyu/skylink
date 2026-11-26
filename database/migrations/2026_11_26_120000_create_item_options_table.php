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
        Schema::create('item_options', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 20); // 'service' or 'product'
            $table->unsignedBigInteger('item_id');
            $table->string('name', 100);
            $table->text('short_features')->nullable();
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->string('price_display_text', 190)->nullable();
            $table->string('currency', 3)->default('ETB'); // 'ETB' or 'USD'
            $table->string('billing_term', 30)->nullable();
            $table->boolean('show_billing_term')->default(true);
            $table->boolean('show_price')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_ready')->default(true);
            $table->boolean('needs_review')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
            $table->index(['item_type', 'item_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_options');
    }
};
