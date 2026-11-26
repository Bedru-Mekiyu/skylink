<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\PaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Show Cart and Checkout Request Form
     */
    public function index(CartService $cartService, PaymentProcessor $payments): View
    {
        // Back on the cart without passing the return page: ask Chapa about anything still waiting.
        foreach (Order::whereIn('order_number', $cartService->awaiting())->where('status', 'pending')->get() as $order) {
            if ($order->latestPayment?->status === 'pending') {
                $payments->confirm($order->latestPayment);
            }
        }

        $cart = $cartService->refresh();
        $totals = $cartService->totals();
        $cartCount = $cartService->count();

        return view('cart.index', compact('cart', 'totals', 'cartCount'));
    }

    /** The number on the navbar, for a page the browser restored from its history. */
    public function count(CartService $cartService): JsonResponse
    {
        return response()->json(['count' => $cartService->count()])->header('Cache-Control', 'no-store');
    }

    /**
     * Add item to cart
     */
    public function add(Request $request, CartService $cartService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|string|in:service,product',
            'item_id' => 'required|integer|min:1',
            'option_id' => 'nullable|integer|min:1',
            'billing_term' => 'nullable|string|max:30',
            'quantity' => 'nullable|integer|min:1|max:100',
            'then' => 'nullable|string|in:checkout',
        ]);

        try {
            $line = $cartService->add(
                $validated['item_type'],
                (int) $validated['item_id'],
                isset($validated['option_id']) ? (int) $validated['option_id'] : null,
                $validated['billing_term'] ?? null,
                isset($validated['quantity']) ? (int) $validated['quantity'] : null,
                ($validated['then'] ?? null) === 'checkout'
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Item added to cart',
                    'count' => $cartService->count(),
                    'line' => $line,
                ]);
            }

            return redirect()->route('cart.index')->with('success', 'Item added to your cart.');
        } catch (\DomainException|\OverflowException $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update item quantity in cart
     */
    public function update(Request $request, CartService $cartService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'line_key' => 'required|string',
            'quantity' => 'required|integer|min:0|max:100',
        ]);

        $updated = $cartService->update($validated['line_key'], (int) $validated['quantity']);
        $cart = $cartService->get();
        $line = $cart[$validated['line_key']] ?? null;

        $lineTotalAmount = $line && $line['unit_price_amount'] !== null
            ? $line['unit_price_amount'] * $line['quantity']
            : null;
        $lineTotalFormatted = $line && $lineTotalAmount !== null
            ? $line['currency'] . ' ' . number_format($lineTotalAmount, 2)
            : ($line['unit_price_display'] ?? __('Quote Request'));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => $updated,
                'count' => $cartService->count(),
                'line_key' => $validated['line_key'],
                'line_quantity' => $line['quantity'] ?? 0,
                'line_total_amount' => $lineTotalAmount,
                'line_total_formatted' => $lineTotalFormatted,
                'totals' => $cartService->totals(),
                'is_empty' => empty($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove item from cart
     */
    public function remove(Request $request, CartService $cartService): RedirectResponse|JsonResponse
    {
        $lineKey = $request->input('line_key');
        $removed = $cartService->remove($lineKey);
        $cart = $cartService->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => $removed,
                'line_key' => $lineKey,
                'count' => $cartService->count(),
                'totals' => $cartService->totals(),
                'is_empty' => empty($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed from cart.');
    }

    /**
     * Confirmation Page
     */
    public function success(): View|RedirectResponse
    {
        $submitted = session('inquiry_submitted');
        if (! $submitted) {
            return redirect()->route('cart.index');
        }

        return view('cart.success', compact('submitted'));
    }
}
