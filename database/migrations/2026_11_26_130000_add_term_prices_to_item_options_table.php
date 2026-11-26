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
        Schema::table('item_options', function (Blueprint $table) {
            if (!Schema::hasColumn('item_options', 'term_prices')) {
                $table->json('term_prices')->nullable()->after('price_display_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            if (Schema::hasColumn('item_options', 'term_prices')) {
                $table->dropColumn('term_prices');
            }
        });
    }
};
