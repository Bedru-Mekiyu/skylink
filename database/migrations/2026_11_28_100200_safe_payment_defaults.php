<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Online payment starts OFF: the admin switch, and every option until someone ticks it. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            $table->boolean('is_payable_online')->default(false)->change();
            if (! Schema::hasColumn('item_options', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // The old default was "on" for every row; nobody chose it.
        DB::table('item_options')->update(['is_payable_online' => false]);
        DB::table('site_settings')->updateOrInsert(['key' => 'chapa_enabled'], ['value' => '0', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Only the column default is restored. Payment is deliberately not switched back on.
        Schema::table('item_options', fn (Blueprint $table) => $table->boolean('is_payable_online')->default(true)->change());
    }
};
