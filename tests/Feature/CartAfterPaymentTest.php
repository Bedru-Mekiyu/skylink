<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\CheckoutHelpers;
use Tests\TestCase;

class CartAfterPaymentTest extends TestCase
{
    use CatalogFixtures, CheckoutHelpers, RefreshDatabase;

    private function startPayment(): array
    {
        $this->enableChapa();
        $service = $this->service();
        $option = $this->option($service);
        $this->checkout($service->id, $option->id);

        return [$service, $option, Order::latest('id')->firstOrFail()];
    }

    private function confirmByChapaOnly(Order $order): void
    {
        $this->get('/webhooks/chapa/callback?trx_ref='.$order->payments()->latest('id')->value('tx_ref'))->assertOk();
    }

    public function test_the_cart_empties_when_payment_is_confirmed_even_if_the_buyer_never_returns(): void
    {
        $this->fakeChapa();
        [, , $order] = $this->startPayment();
        $this->getJson('/cart/count')->assertExactJson(['count' => 1]);

        $this->confirmByChapaOnly($order);

        $this->getJson('/cart/count')->assertExactJson(['count' => 0]);
        $this->assertSame([], session('cart'));
    }

    public function test_opening_the_cart_asks_chapa_about_a_payment_still_waiting(): void
    {
        $this->fakeChapa();
        [, , $order] = $this->startPayment();

        $this->get('/cart')->assertOk()->assertSee('Your cart is empty');
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_only_what_was_paid_for_leaves_the_cart(): void
    {
        $this->fakeChapa();
        [$service, $option, $order] = $this->startPayment();
        $other = $this->service();
        $this->post('/cart/add', ['item_type' => 'service', 'item_id' => $other->id, 'option_id' => $this->option($other)->id]);
        $this->post('/cart/add', ['item_type' => 'service', 'item_id' => $service->id, 'option_id' => $option->id, 'billing_term' => 'one-time']);

        $this->confirmByChapaOnly($order);

        // The other item stays, and so does the one extra added after paying began.
        $this->getJson('/cart/count')->assertExactJson(['count' => 2]);
    }

    public function test_an_unpaid_or_failed_attempt_keeps_the_cart(): void
    {
        $this->fakeChapa(['status' => 'failed/cancelled']);
        $this->startPayment();

        $this->get('/cart')->assertOk();
        $this->getJson('/cart/count')->assertExactJson(['count' => 1]);
    }
}
