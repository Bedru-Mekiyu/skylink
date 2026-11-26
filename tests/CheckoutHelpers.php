<?php

namespace Tests;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

trait CheckoutHelpers
{
    protected function fakeChapa(array $verify = []): void
    {
        Http::fake([
            '*/transaction/initialize' => Http::response(['status' => 'success', 'data' => ['checkout_url' => 'https://checkout.chapa.test/pay/abc']]),
            '*/transaction/verify/*' => function ($request) use ($verify) {
                $txRef = basename($request->url());
                $payment = \App\Models\Payment::where('tx_ref', $txRef)->first();

                return Http::response(['status' => 'success', 'data' => $verify + [
                    'status' => 'success', 'tx_ref' => $txRef, 'amount' => (string) $payment?->amount,
                    'currency' => 'ETB', 'reference' => 'REF-'.$txRef,
                ]]);
            },
        ]);
    }

    /** Put one option in the cart and submit checkout with "pay now". */
    protected function checkout(int $serviceId, int $optionId, string $term = 'one-time', int $qty = 1, array $extra = [])
    {
        $this->post('/cart/add', ['item_type' => 'service', 'item_id' => $serviceId, 'option_id' => $optionId, 'billing_term' => $term, 'quantity' => $qty]);

        return $this->withoutMiddleware(\App\Http\Middleware\ProtectPublicForm::class)->post('/cart/checkout', $extra + [
            'name' => 'Test Buyer', 'email' => 'buyer@example.test', 'phone' => '+251911000000',
            'consent' => '1', 'payment_choice' => 'chapa',
        ]);
    }

    protected function paidOrder(int $serviceId, int $optionId, string $term = 'one-time'): Order
    {
        $this->checkout($serviceId, $optionId, $term);
        $order = Order::latest('id')->firstOrFail();
        $this->get('/webhooks/chapa/callback?trx_ref='.$order->payments()->latest('id')->value('tx_ref'))->assertOk();

        return $order->fresh();
    }
}
