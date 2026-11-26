<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legal_policies', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->longText('content_am')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // Seed 3 standard empty policy pages (unpublished by default, no invented legal text)
        $now = now();
        DB::table('legal_policies')->insert([
            [
                'slug' => 'refund-cancellation-policy',
                'title' => 'Refund and Cancellation Policy',
                'content' => null,
                'content_am' => null,
                'is_published' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'content' => null,
                'content_am' => null,
                'is_published' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'payment-terms',
                'title' => 'Payment Terms',
                'content' => null,
                'content_am' => null,
                'is_published' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_policies');
    }
};
