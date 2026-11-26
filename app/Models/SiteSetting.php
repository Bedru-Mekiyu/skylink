<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public const CACHE_PREFIX = 'site_settings.';

    protected static array $runtimeCache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        try {
            $val = Cache::remember(self::CACHE_PREFIX . $key, 300, function () use ($key, $default) {
                if (! Schema::hasTable('site_settings')) {
                    return $default;
                }
                $setting = self::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
            static::$runtimeCache[$key] = $val;
            return $val;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        unset(static::$runtimeCache[$key]);
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = [];
    }

    public static function showPrices(): bool
    {
        $val = self::get('show_prices', '1');
        return $val === '1' || $val === 'true' || $val === true || $val === 1;
    }

    public static function taxNote(): string
    {
        return trim((string) self::get('tax_note', ''));
    }
}
