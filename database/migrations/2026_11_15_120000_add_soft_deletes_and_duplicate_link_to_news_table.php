<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a duplicate article be hidden without deleting it:
 * - deleted_at: soft delete (the row stays, it just isn't shown);
 * - duplicate_of_id: which article it duplicates, so its old URL can
 *   redirect (301) to the one that is kept.
 * Plus an index for the list's newest-first sort.
 *
 * Additive only (two nullable columns and two indexes): safe on the live data,
 * and fully reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->softDeletes();
            $table->unsignedBigInteger('duplicate_of_id')->nullable()->after('image');
            $table->index('duplicate_of_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['duplicate_of_id']);
            $table->dropColumn('duplicate_of_id');
            $table->dropSoftDeletes();
        });
    }
};
