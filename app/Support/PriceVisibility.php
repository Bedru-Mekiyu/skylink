<?php

namespace App\Support;

use App\Models\ItemOption;
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

    /** Get tax note setting (if configured) */
    public static function taxNote(): ?string
    {
        $note = SiteSetting::taxNote();
        return $note !== '' ? $note : null;
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

        // Quote-only categories (e.g. data center, power) never show a price.
        $owner = $item instanceof ItemOption ? $item->item : $item;
        if (($owner instanceof Product || $owner instanceof Service) && $owner->isQuoteOnly()) {
            return false;
        }

        // If checking an ItemOption directly
        if ($item instanceof ItemOption) {
            if (! $item->is_ready || ! $item->show_price) {
                return false;
            }
            $parent = $item->item;
            if ($parent && isset($parent->show_price) && ! (bool) $parent->show_price) {
                return false;
            }
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

    /** Check option-specific price visibility */
    public static function shouldShowOption(ItemOption $option): bool
    {
        return self::shouldShow($option);
    }

    /** Helper for service price visibility */
    public static function shouldShowServicePrice(mixed $service): bool
    {
        return self::shouldShow($service);
    }

    /** Helper for product price visibility */
    public static function shouldShowProductPrice(mixed $product): bool
    {
        return self::shouldShow($product);
    }

    /** Helper for formatting product price */
    public static function formatProductPrice(mixed $product): ?string
    {
        return self::render($product);
    }

    /** Extract raw price string from item */
    public static function rawPrice(mixed $item): ?string
    {
        if (is_object($item)) {
            if ($item instanceof ItemOption) {
                if (! empty($item->price_display_text)) {
                    return $item->price_display_text;
                }
                if ($item->price_amount !== null) {
                    $prefix = $item->currency === 'USD' ? '$' : 'ETB ';
                    return $prefix . number_format($item->price_amount, 2);
                }
                return null;
            }
            if ($item instanceof Product) {
                return $item->price_text ?: ($item->price !== null ? 'ETB ' . number_format($item->price) : null);
            }
            if ($item instanceof Service) {
                return $item->price_formatted ?: $item->price;
            }
            return (string) ($item->price_display_text ?? $item->price_text ?? $item->price_formatted ?? $item->price ?? '');
        }

        if (is_array($item)) {
            return (string) ($item['price_display_text'] ?? $item['price_text'] ?? $item['price_formatted'] ?? $item['price'] ?? '');
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
