<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\ItemOption;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Support\InquiryNotifier;
use App\Services\CartService;
use App\Services\BillingService;
use App\Services\ChapaService;
use App\Services\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Unified checkout handler: Supports both online payment via Chapa and Quote Request
     */
    public function process(
        Request $request,
        CartService $cartService,
        ChapaService $chapaService,
        InquiryNotifier $notifier,
        BillingService $billing,
        PaymentProcessor $payments
    ): RedirectResponse {
        $cart = $cartService->refresh();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'max:190', 'email:rfc'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]{6,30}$/'],
            'company' => ['nullable', 'string', 'max:190'],
            'message' => ['nullable', 'string', 'max:5000'],
            'payment_choice' => ['nullable', 'string', 'in:chapa,quote'],
            'consent' => ['accepted'],
        ], [
            'phone.regex' => 'Please enter a valid phone number (e.g. +251 9xx xxx xxx).',
            'consent.accepted' => 'Please agree to allow us to contact you regarding this order.',
        ]);

        $paymentChoice = $validated['payment_choice'] ?? 'quote';

        // -------------------------------------------------------------
        // STRICT SERVER-SIDE RE-EVALUATION OF CART ITEMS FROM DATABASE
        // -------------------------------------------------------------
        $isEligibleForOnlinePay = true;
        $orderSubtotal = 0.0;
        $orderCurrency = 'ETB';
        $verifiedLines = [];
        $hasRecurring = false;
        $primaryBillingTerm = null;

        foreach ($cart as $lineKey => $line) {
            $itemType = $line['item_type'] ?? null;
            $itemId = (int) ($line['item_id'] ?? 0);
            $optionId = isset($line['option_id']) ? (int) $line['option_id'] : null;
            $term = $line['billing_term'] ?? 'one-time';
            $qty = max(1, (int) ($line['quantity'] ?? 1));

            // Fetch live database option
            $dbOption = null;
            if ($optionId) {
                $dbOption = ItemOption::where('id', $optionId)
                    ->where('item_type', $itemType)
                    ->where('item_id', $itemId)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$dbOption) {
                $isEligibleForOnlinePay = false;
                $verifiedLines[] = [
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'option_id' => null,
                    'title' => $line['title'] ?? 'Custom Request',
                    'option_name' => $line['option_name'] ?? 'Custom',
                    'billing_term' => $term,
                    'unit_price' => null,
                    'quantity' => $qty,
                    'line_total' => null,
                    'currency' => 'ETB',
                ];
                continue;
            }

            $priceVisible = $dbOption->isPriceVisible();
            $unitPrice = $dbOption->getPriceForTerm($term);

            if (!$priceVisible || $unitPrice === null || $dbOption->currency !== 'ETB' || !$dbOption->is_payable_online) {
                $isEligibleForOnlinePay = false;
            }

            $lineTotal = $unitPrice !== null ? round($unitPrice * $qty, 2) : null;
            if ($lineTotal !== null) {
                $orderSubtotal += $lineTotal;
            }

            if (in_array($term, ['monthly', 'quarterly', 'semi-annually', 'annually'], true)) {
                $hasRecurring = true;
                $primaryBillingTerm = $term;
            }

            $verifiedLines[] = [
                'item_type' => $itemType,
                'item_id' => $itemId,
                'option_id' => $dbOption->id,
                'title' => $line['title'] ?? ($dbOption->item_type === 'product' ? $dbOption->product?->name : $dbOption->service?->title),
                'option_name' => $dbOption->name,
                'billing_term' => $term,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'currency' => $dbOption->currency,
            ];
        }

        // -------------------------------------------------------------
        // BRANCH 1: ONLINE PAYMENT VIA CHAPA
        // -------------------------------------------------------------
        if ($paymentChoice === 'chapa' && $isEligibleForOnlinePay && $chapaService->isEnabled() && $orderSubtotal > 0) {
            $order = DB::transaction(function () use ($validated, $orderSubtotal, $orderCurrency, $verifiedLines, $billing) {
                $terms = array_unique(array_column($verifiedLines, 'billing_term'));
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'customer_name' => trim($validated['name']),
                    'customer_email' => trim($validated['email']),
                    'customer_phone' => trim($validated['phone']),
                    'company' => !empty($validated['company']) ? trim($validated['company']) : null,
                    'notes' => !empty($validated['message']) ? trim($validated['message']) : null,
                    'subtotal' => $orderSubtotal,
                    'tax' => 0.00,
                    'total' => $orderSubtotal,
                    'currency' => $orderCurrency,
                    'status' => 'pending',
                    'payment_method' => 'chapa',
                    'billing_term' => count($terms) === 1 ? reset($terms) : 'mixed',
                ]);

                foreach ($verifiedLines as $line) {
                    $order->items()->create($line);
                }
                $billing->createFor($order);

                return $order;
            });

            $cartService->awaitPayment($order);
            $result = $payments->start($order);

            // The cart is kept until payment is confirmed, so a cancelled or failed payment can be retried.
            if ($result['ok']) {
                return redirect()->away($result['checkout_url']);
            }

            return redirect()->to(URL::signedRoute('pay.show', ['order' => $order->order_number]))
                ->with('error', $result['error']);
        }

        // -------------------------------------------------------------
        // BRANCH 2: QUOTE / INQUIRY REQUEST FLOW
        // -------------------------------------------------------------
        $totals = $cartService->totals();
        $itemsCount = count($cart);
        $firstItem = reset($cart);

        $inquiry = Inquiry::create([
            'type' => 'cart_request',
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => trim($validated['phone']),
            'company' => !empty($validated['company']) ? trim($validated['company']) : null,
            'service' => $itemsCount === 1 ? ($firstItem['title'] ?? 'Cart Item') : "Cart Request ({$itemsCount} items)",
            'item_type' => $itemsCount === 1 ? ($firstItem['item_type'] ?? null) : null,
            'item_id' => $itemsCount === 1 ? ($firstItem['item_id'] ?? null) : null,
            'plan' => $itemsCount === 1 ? ($firstItem['option_name'] ?? null) : null,
            'billing_term' => $itemsCount === 1 ? ($firstItem['billing_term'] ?? null) : null,
            'estimated_price' => ($isEligibleForOnlinePay && $orderSubtotal > 0)
                ? ($orderCurrency . ' ' . number_format($orderSubtotal, 2))
                : 'Quote Request',
            'message' => !empty($validated['message']) ? trim($validated['message']) : null,
            'language' => preg_match('/\p{Ethiopic}/u', ($validated['message'] ?? '') . $validated['name']) ? 'am' : 'en',
            'source_page' => '/cart',
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        foreach ($verifiedLines as $line) {
            InquiryItem::create([
                'inquiry_id' => $inquiry->id,
                'item_type' => $line['item_type'] ?? 'service',
                'item_id' => $line['item_id'] ?? 0,
                'option_id' => $line['option_id'],
                'item_name' => $line['title'],
                'option_name' => $line['option_name'],
                'billing_term' => $line['billing_term'],
                'currency' => $line['currency'],
                'quantity' => $line['quantity'],
                'unit_price_amount' => $line['unit_price'],
                'unit_price_display' => $line['unit_price'] !== null ? ($line['currency'] . ' ' . number_format($line['unit_price'], 2)) : null,
                'line_total_amount' => $line['line_total'],
                'is_price_visible' => $line['unit_price'] !== null,
            ]);
        }

        $cartService->clear();
        $notifier->notify($inquiry);

        $refCode = 'SK-' . date('ymd') . '-' . strtoupper(substr(md5((string) $inquiry->id), 0, 5));
        return redirect()->route('cart.success')->with([
            'submitted' => true,
            'ref_code' => $refCode,
            'customer_name' => $inquiry->name,
            'customer_email' => $inquiry->email,
        ]);
    }
}
