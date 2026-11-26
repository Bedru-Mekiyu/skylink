<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring billing. Chapa has no documented automatic charging, so each period is its own order that the
 * customer pays from an emailed link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('first_order_id')->constrained('orders');
            $table->string('customer_name');
            $table->string('customer_email')->index();
            $table->string('customer_phone', 30);
            $table->string('company')->nullable();
            $table->string('billing_term', 30);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('ETB');
            $table->string('status', 20)->default('pending')->index(); // pending, active, cancelled
            $table->date('current_period_end')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            // Set on renewal orders only.
            $table->foreignId('billing_subscription_id')->nullable()->constrained('billing_subscriptions');
            $table->date('period_start')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('billing_subscription_id')->nullable()->constrained('billing_subscriptions');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('billing_subscription_id'));
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_subscription_id');
            $table->dropColumn(['period_start', 'reminder_sent_at']);
        });
        Schema::dropIfExists('billing_subscriptions');
    }
};
