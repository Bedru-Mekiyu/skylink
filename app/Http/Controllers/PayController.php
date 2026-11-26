<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\ChapaService;
use App\Services\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/** The customer's payment page: first purchases that need a retry, and every renewal. Reached by signed link only. */
class PayController extends Controller
{
    public function show(Order $order, ChapaService $chapa): View
    {
        return $this->page($order, $chapa);
    }

    public function start(Order $order, PaymentProcessor $payments): RedirectResponse
    {
        $result = $payments->start($order);

        return $result['ok']
            ? redirect()->away($result['checkout_url'])
            : redirect()->to(URL::signedRoute('pay.show', ['order' => $order->order_number]))->with('error', $result['error']);
    }

    /** Where Chapa sends the customer back. The result comes from our own verification, never from the address. */
    public function done(Order $order, ChapaService $chapa, PaymentProcessor $payments, CartService $cart): View
    {
        $outcome = null;
        if (! $order->isPaid() && ($payment = $order->latestPayment)) {
            $outcome = $payments->confirm($payment);
            $order->refresh();
        }
        $cart->settlePaid();

        return $this->page($order, $chapa, $outcome);
    }

    private function page(Order $order, ChapaService $chapa, ?string $outcome = null): View
    {
        return view('pay.show', [
            'order' => $order->load('items'),
            'outcome' => $outcome,
            'canPay' => $chapa->isEnabled() && $order->isPayable(),
            'testMode' => $chapa->isTestMode(),
            'payUrl' => URL::signedRoute('pay.start', ['order' => $order->order_number]),
        ]);
    }
}
