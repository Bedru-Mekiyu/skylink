<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Seed default Chapa payment gateway settings in site_settings.
 *
 * INSERT-ONLY: will not overwrite any existing setting rows.
 * Default: enabled=1, mode=sandbox (no API keys needed in sandbox mode).
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'chapa_enabled'        => '1',
            'chapa_mode'           => 'sandbox',
            'chapa_public_key'     => '',
            'chapa_secret_key'     => '',
            'chapa_webhook_secret' => '',
        ];

        foreach ($defaults as $key => $value) {
            // Only insert if the row doesn't already exist — never update existing settings
            $exists = DB::table('site_settings')->where('key', $key)->exists();
            if (!$exists) {
                DB::table('site_settings')->insert([
                    'key'        => $key,
                    'value'      => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove only the rows this migration inserted (empty value = placeholder seeded by us).
        // We identify them by empty value since real values would have been entered by admin.
        $seedKeys = ['chapa_public_key', 'chapa_secret_key', 'chapa_webhook_secret'];
        DB::table('site_settings')
            ->whereIn('key', $seedKeys)
            ->where('value', '')
            ->delete();

        // Remove enabled/mode defaults ONLY if they still hold the original seeded values
        DB::table('site_settings')->where('key', 'chapa_enabled')->where('value', '1')->delete();
        DB::table('site_settings')->where('key', 'chapa_mode')->where('value', 'sandbox')->delete();
    }
};
