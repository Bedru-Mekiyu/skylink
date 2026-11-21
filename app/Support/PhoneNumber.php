<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * The one place that formats Ethiopian phone numbers (display, tel: and wa.me forms).
 *
 *   mobile   09XX XXX XXX  <->  +251 9XX XXX XXX      (07XX numbers are mobile too)
 *   Addis    011 XXX XXXX  <->  +251 11 XXX XXXX
 *   other    0AA XXX XXXX  <->  +251 AA XXX XXXX      (two-digit area code)
 *
 * Input may carry spaces, dashes, dots or brackets, a +251 / 251 / 00251 prefix, a leading 0 or none.
 * Digits are never changed. An invalid number is returned unchanged and a warning is logged: the page never breaks.
 * Display strings use non-breaking spaces so a number never wraps in the middle of a group.
 */
class PhoneNumber
{
    private const NBSP = "\u{00A0}";

    /** The 9 national digits (without country code and trunk 0), or null when the number is not a valid Ethiopian number. */
    public static function national(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '00251')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '251')) {
            $digits = substr($digits, 3);
        }
        if (strlen($digits) === 10 && $digits[0] === '0') {   // 09.. / 011.. (also +2510911.. with an extra zero)
            $digits = substr($digits, 1);
        }

        // 9 digits: mobile starts with 9 or 7, a landline with an area code 1-6 (11 = Addis Ababa)
        return strlen($digits) === 9 && preg_match('/^[1-79]/', $digits) ? $digits : null;
    }

    /** "+251 9XX XXX XXX" / "+251 11 XXX XXXX" (non-breaking spaces). Invalid: the input, unchanged. */
    public static function international(?string $raw): string
    {
        $n = self::national($raw);

        return $n === null ? self::invalid($raw) : '+251'.self::NBSP.self::groups($n, self::NBSP);
    }

    /** "09XX XXX XXX" / "011 XXX XXXX" (non-breaking spaces). Invalid: the input, unchanged. */
    public static function local(?string $raw): string
    {
        $n = self::national($raw);
        if ($n === null) {
            return self::invalid($raw);
        }
        $g = self::groups($n, self::NBSP);

        return '0'.$g;
    }

    /** "+251911234567" for tel: links. Invalid: the digits and plus of the input. */
    public static function e164(?string $raw): string
    {
        $n = self::national($raw);

        return $n === null ? preg_replace('/[^\d+]/', '', (string) $raw) : '+251'.$n;
    }

    public static function tel(?string $raw): string
    {
        return 'tel:'.self::e164($raw);
    }

    /** "https://wa.me/251911234567". */
    public static function whatsapp(?string $raw): string
    {
        $n = self::national($raw);

        return 'https://wa.me/'.($n === null ? preg_replace('/\D+/', '', (string) $raw) : '251'.$n);
    }

    private static function isMobile(string $n): bool
    {
        return $n[0] === '9' || $n[0] === '7';
    }

    /** Mobile 9XX XXX XXX; landline AA XXX XXXX. */
    private static function groups(string $n, string $sep): string
    {
        return self::isMobile($n)
            ? substr($n, 0, 3).$sep.substr($n, 3, 3).$sep.substr($n, 6, 3)
            : substr($n, 0, 2).$sep.substr($n, 2, 3).$sep.substr($n, 5, 4);
    }

    private static function invalid(?string $raw): string
    {
        Log::warning('Phone number is not a valid Ethiopian number; shown unchanged.', ['value' => (string) $raw]);

        return (string) $raw;
    }
}
