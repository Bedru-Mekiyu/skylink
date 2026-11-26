<?php

namespace App\Support;

/** One way to write a price: "ETB 60,000.00", "$1,200.00" (as the cart and payment pages do). */
class Money
{
    public static function format(float|int|string $amount, ?string $currency = 'ETB'): string
    {
        $number = number_format((float) $amount, 2);

        return $currency === 'USD' ? '$'.$number : ($currency ?: 'ETB').' '.$number;
    }

    /** A hand-typed price text: a number (with or without "ETB"/"Br") gets the standard format, words stay as written. */
    public static function display(?string $text, ?string $currency = 'ETB'): ?string
    {
        if (preg_match('/^\s*(?:ETB|Br)?\s*(\d[\d,]*(?:\.\d+)?)\s*(?:ETB|Br)?\s*$/i', (string) $text, $m)) {
            return self::format(str_replace(',', '', $m[1]), $currency);
        }

        return $text;
    }
}
