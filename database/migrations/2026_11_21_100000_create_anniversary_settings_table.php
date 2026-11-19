<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin control for the 5-year anniversary ceremony (Admin > Anniversary Ceremony).
 * One row at most. With no row the site follows config/anniversary.php, so nothing
 * changes until the owner saves the page. Additive: it touches no existing table.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_21_100000_create_anniversary_settings_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anniversary_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 10)->default('auto'); // auto | off | on
            $table->timestamp('starts_at')->nullable();   // stored in UTC; the admin form works in Africa/Addis_Ababa
            $table->timestamp('ends_at')->nullable();
            $table->boolean('admin_preview')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anniversary_settings');
    }
};
