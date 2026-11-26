<?php

namespace App\Services;

use App\Models\ItemOption;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Support\PriceVisibility;
use Illuminate\Support\Facades\Session;

class CartService
{
    public const SESSION_KEY = 'cart';
    /** Orders sent to online payment from this cart: [order number => [line key => quantity ordered]]. */
    public const AWAITING_KEY = 'cart_awaiting_payment';
    public const MAX_ITEMS = 20;
    public const MAX_QTY_PER_ITEM = 100;

    /**
     * Get all cart items
     */
    public function get(): array
    {
        // Payment can be confirmed while the buyer is elsewhere (Chapa tells the server), so every read checks first.
        $this->settlePaid();

        return Session::get(self::SESSION_KEY, []);
    }

    /** Remember what an order took from the cart. The lines stay until it is paid, so a failed payment can be retried. */
    public function awaitPayment(Order $order): void
    {
        $lines = array_map(fn ($line) => (int) $line['quantity'], Session::get(self::SESSION_KEY, []));
        Session::put(self::AWAITING_KEY.'.'.$order->order_number, $lines);
    }

    /** @return array<int, string> order numbers still waiting for payment */
    public function awaiting(): array
    {
        return array_keys(Session::get(self::AWAITING_KEY, []));
    }

    /** Take out of the cart whatever has been paid for since. Anything added later stays. */
    public function settlePaid(): void
    {
        $awaiting = Session::get(self::AWAITING_KEY, []);
        if (! $awaiting) {
            return;
        }

        $paid = Order::whereIn('order_number', array_keys($awaiting))->get()->filter->isPaid();
        if ($paid->isEmpty()) {
            return;
        }

        $cart = Session::get(self::SESSION_KEY, []);
        foreach ($paid as $order) {
            foreach ($awaiting[$order->order_number] as $key => $ordered) {
                if (! isset($cart[$key])) {
                    continue;
                }
                $left = $cart[$key]['quantity'] - $ordered;
                if ($left <= 0) {
                    unset($cart[$key]);

                    continue;
                }
                $unit = $cart[$key]['unit_price_amount'] ?? null;
                $cart[$key]['quantity'] = $left;
                $cart[$key]['line_total_amount'] = $unit === null ? null : $unit * $left;
            }
            unset($awaiting[$order->order_number]);
        }

        Session::put(self::SESSION_KEY, $cart);
        Session::put(self::AWAITING_KEY, $awaiting);
    }

    /** The navbar number: how many different items are in the cart, whatever the quantity of each. */
    public function count(): int
    {
        return count($this->get());
    }

    /**
     * Add item option to cart
     */
    public function add(string $itemType, int $itemId, ?int $optionId = null, ?string $billingTerm = null, ?int $quantity = null, bool $forCheckout = false): array
    {
        $cart = $this->get();
        $chosen = $quantity !== null;
        $quantity = max(1, min(self::MAX_QTY_PER_ITEM, $quantity ?? 1));

        $item = $this->sellableItem($itemType, $itemId);
        if (! $item) {
            throw new \DomainException(__('This item is not available.'));
        }
        if ($item->isQuoteOnly()) {
            throw new \DomainException(__('This item is priced per project. Please contact us for a quote.'));
        }

        $title = $itemType === 'service' ? $item->title : $item->name;
        $category = $itemType === 'service'
            ? ($item->category?->name ?? 'Service')
            : ucfirst(str_replace('-', ' ', $item->category ?? 'Product'));
        $image = $item->image ?: 'assets/images/placeholder.png';

        // Resolve Option
        if ($optionId) {
            $option = ItemOption::where('item_type', $itemType)
                ->where('item_id', $itemId)
                ->where('is_active', true)
                ->findOrFail($optionId);
        } else {
            $option = $item->options()->where('is_active', true)->orderBy('sort_order')->first();
        }

        // Only a term the option really offers: otherwise one price could be sold for another period.
        $terms = $option ? array_keys($option->getAvailableTerms()) : ['one-time'];
        if ($billingTerm && ! in_array($billingTerm, $terms, true)) {
            throw new \DomainException(__('That billing term is not available for this plan.'));
        }

        $optionName = $option ? $option->name : 'Standard';
        $term = $billingTerm ?: (in_array($option?->billing_term, $terms, true) ? $option->billing_term : $terms[0]);
        $currency = $option ? $option->currency : 'ETB';
        $isPriceVisible = $option ? $option->isPriceVisible() : false;

        // Resolve unit price based on selected billing term (supports multi-term pricing)
        $unitPriceAmount = null;
        if ($isPriceVisible && $option) {
            $unitPriceAmount = $option->getPriceForTerm($term);
        }

        if ($unitPriceAmount !== null) {
            $unitPriceDisplay = \App\Support\Money::format($unitPriceAmount, $currency);
        } else {
            $unitPriceDisplay = $option ? $option->formatted_price : 'Quote Request';
        }

        // Unique line key
        $optKey = $option ? $option->id : 0;
        $lineKey = md5("{$itemType}_{$itemId}_{$optKey}_{$term}");

        if (isset($cart[$lineKey])) {
            // Going to checkout does not stack: a chosen quantity replaces the line, otherwise it stays as it is.
            $newQty = $forCheckout
                ? ($chosen ? $quantity : $cart[$lineKey]['quantity'])
                : min(self::MAX_QTY_PER_ITEM, $cart[$lineKey]['quantity'] + $quantity);
            $cart[$lineKey]['quantity'] = $newQty;
            $cart[$lineKey]['line_total_amount'] = $unitPriceAmount !== null ? ($unitPriceAmount * $newQty) : null;
        } else {
            if (count($cart) >= self::MAX_ITEMS) {
                throw new \OverflowException("Cart limit of " . self::MAX_ITEMS . " items reached.");
            }

            $cart[$lineKey] = [
                'line_key' => $lineKey,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'option_id' => $option?->id,
                'title' => $title,
                'option_name' => $optionName,
                'category' => $category,
                'image' => $image,
                'billing_term' => $term,
                'currency' => $currency,
                'quantity' => $quantity,
                'unit_price_amount' => $unitPriceAmount,
                'unit_price_display' => $unitPriceDisplay,
                'line_total_amount' => $unitPriceAmount !== null ? ($unitPriceAmount * $quantity) : null,
                'is_price_visible' => $isPriceVisible,
            ];
        }

        Session::put(self::SESSION_KEY, $cart);
        return $cart[$lineKey];
    }

    private function sellableItem(string $itemType, int $itemId): Service|Product|null
    {
        return match ($itemType) {
            'service' => Service::visible()->with('category', 'options')->find($itemId),
            'product' => Product::visible()->with('productCategory', 'options')->find($itemId),
            default => throw new \InvalidArgumentException("Invalid item type: {$itemType}"),
        };
    }

    /**
     * Bring stored lines up to date with the database (price changed in admin, item hidden or made quote-only).
     * The session is written only when something really changed.
     */
    public function refresh(): array
    {
        $cart = $this->get();
        $fresh = [];

        foreach ($cart as $key => $line) {
            $item = $this->sellableItem($line['item_type'], (int) $line['item_id']);
            $option = $line['option_id'] ? $item?->options->where('is_active', true)->firstWhere('id', $line['option_id']) : null;

            if (! $item || $item->isQuoteOnly() || ($line['option_id'] && ! $option)
                || ($option && ! array_key_exists($line['billing_term'], $option->getAvailableTerms()))) {
                continue;
            }

            $visible = $option ? $option->isPriceVisible($item) : false;
            $amount = $visible ? $option->getPriceForTerm($line['billing_term']) : null;
            $currency = $option?->currency ?? 'ETB';

            $fresh[$key] = array_merge($line, [
                'currency' => $currency,
                'is_price_visible' => $visible,
                'unit_price_amount' => $amount,
                'unit_price_display' => $amount === null
                    ? ($option ? $option->formatted_price : 'Quote Request')
                    : \App\Support\Money::format($amount, $currency),
                'line_total_amount' => $amount === null ? null : $amount * $line['quantity'],
            ]);
        }

        if ($fresh != $cart) {
            Session::put(self::SESSION_KEY, $fresh);
        }

        return $fresh;
    }

    /**
     * Update line quantity
     */
    public function update(string $lineKey, int $quantity): bool
    {
        $cart = $this->get();
        if (! isset($cart[$lineKey])) {
            return false;
        }

        if ($quantity <= 0) {
            return $this->remove($lineKey);
        }

        $quantity = min(self::MAX_QTY_PER_ITEM, $quantity);
        if ($cart[$lineKey]['quantity'] === $quantity) {
            return true;
        }
        $cart[$lineKey]['quantity'] = $quantity;
        if ($cart[$lineKey]['unit_price_amount'] !== null) {
            $cart[$lineKey]['line_total_amount'] = $cart[$lineKey]['unit_price_amount'] * $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
        return true;
    }

    /**
     * Remove item from cart
     */
    public function remove(string $lineKey): bool
    {
        $cart = $this->get();
        if (isset($cart[$lineKey])) {
            unset($cart[$lineKey]);
            Session::put(self::SESSION_KEY, $cart);
            return true;
        }
        return false;
    }

    /**
     * Empty entire cart
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Calculate cart totals and determine whether total is allowed to be shown.
     *
     * Rule: Cart total is shown ONLY when:
     * 1. Cart is not empty.
     * 2. 100% of line items have visible numeric prices.
     * 3. 100% of line items share the exact same currency (e.g. all ETB or all USD).
     */
    public function totals(): array
    {
        $cart = $this->get();
        if (empty($cart)) {
            return [
                'has_total' => false,
                'subtotal' => 0.00,
                'currency' => null,
                'tax_note' => PriceVisibility::taxNote(),
                'reason' => 'Cart is empty',
            ];
        }

        $totalAmount = 0.00;
        $commonCurrency = null;

        foreach ($cart as $line) {
            // Check visibility & numeric amount
            if (! ($line['is_price_visible'] ?? false) || ! isset($line['unit_price_amount']) || $line['unit_price_amount'] === null) {
                return [
                    'has_total' => false,
                    'subtotal' => null,
                    'currency' => null,
                    'tax_note' => PriceVisibility::taxNote(),
                    'reason' => 'One or more items require a custom quotation.',
                ];
            }

            $lineCurrency = $line['currency'] ?? 'ETB';
            if ($commonCurrency === null) {
                $commonCurrency = $lineCurrency;
            } elseif ($commonCurrency !== $lineCurrency) {
                return [
                    'has_total' => false,
                    'subtotal' => null,
                    'currency' => null,
                    'tax_note' => PriceVisibility::taxNote(),
                    'reason' => 'Cart contains items with different currencies.',
                ];
            }

            $lineTotal = $line['line_total_amount'] ?? ($line['unit_price_amount'] * ($line['quantity'] ?? 1));
            $totalAmount += (float) $lineTotal;
        }

        return [
            'has_total' => true,
            'subtotal' => $totalAmount,
            'currency' => $commonCurrency,
            'tax_note' => PriceVisibility::taxNote(),
            'reason' => null,
        ];
    }
}
