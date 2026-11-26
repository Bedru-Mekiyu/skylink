<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Quote-only services never show a price: a whole category (pricing_mode) or one service (quote_only). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('pricing_mode', 20)->default('priced');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('quote_only')->default(false);
        });

        // Power, facility and data centre work is priced per project. Prices stay stored; they are only hidden.
        DB::table('service_categories')->where('slug', 'datacenter-facility')->update(['pricing_mode' => 'quote_only']);
        DB::table('services')->whereIn('slug', ['data-center', 'datacenter-infra', 'ups-power', 'server-rooms', 'cooling-system'])
            ->update(['quote_only' => true]);
    }

    public function down(): void
    {
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('quote_only'));
        Schema::table('service_categories', fn (Blueprint $table) => $table->dropColumn('pricing_mode'));
    }
};
