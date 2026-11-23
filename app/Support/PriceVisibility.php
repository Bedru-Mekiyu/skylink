<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSetting;

class PriceVisibility
{
    /** Check whether prices are globally enabled */
    public static function globalEnabled(): bool
    {
        return SiteSetting::showPrices();
    }

    /**
     * Check whether price should be shown for a given item (or globally if $item is null).
     * Returns true ONLY if global switch is ON, item switch is ON, and price is not empty or N/A.
     */
    public static function shouldShow(mixed $item = null): bool
    {
        if (! self::globalEnabled()) {
            return false;
        }

        if ($item === null) {
            return true;
        }

        // Check per-item switch
        if (is_object($item)) {
            if (isset($item->show_price) && ! (bool) $item->show_price) {
                return false;
            }
        } elseif (is_array($item)) {
            if (isset($item['show_price']) && ! (bool) $item['show_price']) {
                return false;
            }
        }

        // Check price value itself: hide if missing, null, empty, or 'N/A'
        $price = self::rawPrice($item);
        if ($price === null || $price === '' || strtoupper(trim($price)) === 'N/A') {
            return false;
        }

        return true;
    }

    /** Extract raw price string from item */
    public static function rawPrice(mixed $item): ?string
    {
        if (is_object($item)) {
            if ($item instanceof Product) {
                return $item->price_text ?: ($item->price !== null ? 'ETB ' . number_format($item->price) : null);
            }
            if ($item instanceof Service) {
                return $item->price_formatted ?: $item->price;
            }
            return (string) ($item->price_text ?? $item->price_formatted ?? $item->price ?? '');
        }

        if (is_array($item)) {
            return (string) ($item['price_text'] ?? $item['price_formatted'] ?? $item['price'] ?? '');
        }

        if (is_string($item) || is_numeric($item)) {
            return (string) $item;
        }

        return null;
    }

    /** Format and render price text if visible, or null if hidden */
    public static function render(mixed $item): ?string
    {
        if (! self::shouldShow($item)) {
            return null;
        }

        return self::rawPrice($item);
    }
}
