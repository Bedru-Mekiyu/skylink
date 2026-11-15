<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin "Add project" form saves a slug, but the original portfolios
 * migration never created that column, so adding a project failed with
 * "no column named slug" on databases built from the migrations.
 *
 * Adds a nullable `slug` only when it is missing (a live database may already
 * have it). Rollback is deliberately a no-op: it must never drop a slug column
 * that existed before this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('portfolios', 'slug')) {
            Schema::table('portfolios', function (Blueprint $table) {
                $table->string('slug')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty (see above).
    }
};
