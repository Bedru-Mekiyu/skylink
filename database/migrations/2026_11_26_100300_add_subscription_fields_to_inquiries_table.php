<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('item_type', 30)->nullable()->after('service');
            $table->unsignedBigInteger('item_id')->nullable()->after('item_type');
            $table->string('billing_term', 30)->nullable()->after('plan');
            $table->string('estimated_price', 60)->nullable()->after('billing_term');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'item_id', 'billing_term', 'estimated_price']);
        });
    }
};
