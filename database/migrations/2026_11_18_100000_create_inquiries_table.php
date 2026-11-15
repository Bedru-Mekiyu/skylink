<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * One table for every message sent through the website (contact, support,
 * quote, service request, plan subscription), shown in the admin inbox.
 *
 * Existing rows of the old tables are COPIED in (the old tables and their rows
 * stay untouched), marked with legacy_table / legacy_id.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_18_100000_create_inquiries_table.php
 * drops only the new table; the old tables still hold their original rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('type', 20)->index();
            $table->string('status', 20)->default('new')->index();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 30)->nullable();
            $table->string('company', 190)->nullable();
            $table->string('service', 120)->nullable();
            $table->string('plan', 50)->nullable();
            $table->text('message')->nullable();
            $table->string('language', 5)->default('en');
            $table->string('source_page', 190)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('team_notified_at')->nullable();
            $table->string('team_notify_error', 255)->nullable();
            $table->timestamp('visitor_confirmed_at')->nullable();
            $table->string('visitor_confirm_error', 255)->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->string('legacy_table', 40)->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->unique(['legacy_table', 'legacy_id']);
        });

        $this->copyLegacy('contact_messages', 'contact', fn ($r) => [
            'name' => trim(($r->first_name ?? '').' '.($r->last_name ?? '')),
            'phone' => $r->phone, 'company' => $r->company, 'message' => $r->message,
        ]);
        $this->copyLegacy('messages', 'contact', fn ($r) => [
            'name' => $r->name, 'phone' => $r->phone, 'message' => $r->message,
        ]);
        $this->copyLegacy('service_requests', 'quote', fn ($r) => [
            'name' => $r->name, 'phone' => $r->phone, 'service' => $r->service, 'message' => $r->message,
        ]);
        $this->copyLegacy('subscriptions', 'subscription', fn ($r) => [
            'name' => $r->name, 'phone' => $r->phone, 'plan' => $r->plan, 'message' => $r->notes,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }

    /** Copy old rows; they are marked "done" because they predate the inbox. */
    private function copyLegacy(string $table, string $type, callable $map): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        DB::table($table)->orderBy('id')->chunk(200, function ($rows) use ($table, $type, $map) {
            foreach ($rows as $row) {
                $fields = $map($row);
                DB::table('inquiries')->insert(array_merge([
                    'reference' => 'SK-OLD-'.strtoupper(Str::random(6)),
                    'type' => $type,
                    'status' => 'done',
                    'email' => mb_substr((string) $row->email, 0, 190),
                    'language' => preg_match('/\p{Ethiopic}/u', (string) ($fields['message'] ?? '').($fields['name'] ?? '')) ? 'am' : 'en',
                    'legacy_table' => $table,
                    'legacy_id' => $row->id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ], $fields, [
                    'name' => mb_substr((string) ($fields['name'] ?: '(no name)'), 0, 120),
                    'phone' => isset($fields['phone']) ? mb_substr((string) $fields['phone'], 0, 30) : null,
                ]));
            }
        });
    }
};
