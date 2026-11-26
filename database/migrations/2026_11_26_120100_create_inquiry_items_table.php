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
        Schema::create('inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->string('item_type', 20); // 'service' or 'product'
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('option_id')->nullable();
            $table->string('item_name', 190);
            $table->string('option_name', 100)->nullable();
            $table->string('billing_term', 30)->nullable();
            $table->string('currency', 3)->default('ETB');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price_amount', 12, 2)->nullable();
            $table->string('unit_price_display', 100)->nullable();
            $table->decimal('line_total_amount', 12, 2)->nullable();
            $table->boolean('is_price_visible')->default(true);
            $table->timestamps();

            $table->index(['inquiry_id']);
            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_items');
    }
};
