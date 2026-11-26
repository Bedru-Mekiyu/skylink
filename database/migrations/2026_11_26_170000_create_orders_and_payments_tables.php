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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique()->index();
            $table->string('customer_name');
            $table->string('customer_email')->index();
            $table->string('customer_phone');
            $table->string('company')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2);
            $table->string('currency', 10)->default('ETB');
            $table->string('status', 30)->default('pending')->index(); // pending, paid, failed, cancelled
            $table->string('payment_method', 30)->default('chapa'); // chapa, request_only
            $table->string('billing_term', 50)->nullable();
            $table->text('renewal_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('item_type', 30); // product, service
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('option_id')->nullable();
            $table->string('title');
            $table->string('option_name');
            $table->string('billing_term', 50)->default('one-time');
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('line_total', 12, 2)->nullable();
            $table->string('currency', 10)->default('ETB');
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('tx_ref')->unique()->index();
            $table->string('gateway', 30)->default('chapa');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('ETB');
            $table->string('status', 30)->default('pending')->index(); // pending, successful, failed
            $table->string('gateway_reference')->nullable()->index();
            $table->json('raw_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
