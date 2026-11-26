<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\CheckoutHelpers;
use Tests\TestCase;

class ChapaPaymentTest extends TestCase
{
    use CheckoutHelpers, RefreshDatabase;

    private function item(array $attrs = []): array
    {
        $service = $this->service();

        return [$service, $this->option($service, $attrs)];
    }

    private function webhook(string $txRef, ?string $signature = null)
    {
        $body = json_encode(['event' => 'charge.success', 'tx_ref' => $txRef, 'status' => 'success']);
        $signature ??= hash_hmac('sha256', $body, 'fake-webhook-secret');

        return $this->call('POST', '/webhooks/chapa', [], [], [], ['HTTP_X_CHAPA_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body);
    }

    private function pendingOrder(): Order
    {
        $this->enableChapa();
        [$s, $o] = $this->item();
        $this->checkout($s->id, $o->id);

        return Order::latest('id')->firstOrFail();
    }

    public function test_chapa_is_off_by_default_and_checkout_becomes_a_request(): void
    {
        Http::fake();
        [$s, $o] = $this->item();

        $this->checkout($s->id, $o->id)->assertRedirect(route('cart.success'));

        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_new_options_are_not_payable_online_unless_ticked(): void
    {
        $service = $this->service();
        $option = \App\Models\ItemOption::create(['item_type' => 'service', 'item_id' => $service->id, 'name' => 'X', 'price_amount' => 5]);

        $this->assertFalse($option->fresh()->is_payable_online);
    }

    public function test_the_fake_payment_page_no_longer_exists(): void
    {
        $order = $this->pendingOrderWithFake();
        $txRef = $order->payments()->value('tx_ref');

        $this->get('/checkout/payment/'.$txRef)->assertNotFound();
        $this->post('/checkout/payment/'.$txRef.'/pay', ['payment_method' => 'card'])->assertNotFound();
        $this->assertSame('pending', $order->fresh()->status);
    }

    private function pendingOrderWithFake(): Order
    {
        $this->fakeChapa();

        return $this->pendingOrder();
    }

    public function test_amount_comes_from_the_database_and_customer_goes_to_chapa(): void
    {
        $this->fakeChapa();
        $this->enableChapa();
        [$s, $o] = $this->item(['price_amount' => 1500]);

        $this->checkout($s->id, $o->id, 'one-time', 2, ['amount' => 1, 'total' => 1])
            ->assertRedirect('https://checkout.chapa.test/pay/abc');

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/transaction/initialize')
            && $r['amount'] === '3000.00' && $r['currency'] === 'ETB' && $r['phone_number'] === '0911000000'
            && str_ends_with($r['callback_url'], '/webhooks/chapa/callback'));
        $this->assertSame('pending', Order::first()->status);
    }

    public function test_an_unknown_billing_term_cannot_be_used(): void
    {
        [$s, $o] = $this->item();

        $this->postJson('/cart/add', ['item_type' => 'service', 'item_id' => $s->id, 'option_id' => $o->id, 'billing_term' => 'annually'])
            ->assertStatus(422);
    }

    public function test_a_non_payable_line_forces_a_request(): void
    {
        Http::fake();
        $this->enableChapa();
        [$s, $o] = $this->item(['is_payable_online' => false]);

        $this->checkout($s->id, $o->id)->assertRedirect(route('cart.success'));
        $this->assertSame(0, Order::count());
    }

    public function test_signed_webhook_marks_paid_once(): void
    {
        $order = $this->pendingOrderWithFake();
        $txRef = $order->payments()->value('tx_ref');

        $this->webhook($txRef)->assertOk();
        $paidAt = $order->fresh()->paid_at;
        $this->travel(5)->minutes();
        $this->webhook($txRef)->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertEquals($paidAt, $order->fresh()->paid_at);
        $this->assertSame(1, Payment::where('status', 'successful')->count());
    }

    public function test_bad_missing_and_constant_signatures_are_rejected(): void
    {
        $order = $this->pendingOrderWithFake();
        $txRef = $order->payments()->value('tx_ref');
        $constant = hash_hmac('sha256', 'fake-webhook-secret', 'fake-webhook-secret');

        $this->webhook($txRef, 'bad')->assertForbidden();
        $this->webhook($txRef, '')->assertForbidden();
        $this->webhook($txRef, $constant)->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public static function dishonestVerifications(): array
    {
        return [
            'amount' => [['amount' => '1']],
            'currency' => [['currency' => 'USD']],
            'tx_ref' => [['tx_ref' => 'someone-else']],
            'failed' => [['status' => 'failed']],
            'pending' => [['status' => 'pending']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dishonestVerifications')]
    public function test_callback_webhook_and_admin_reverify_all_refuse(array $verify): void
    {
        $this->fakeChapa($verify);
        $order = $this->pendingOrder();
        $txRef = $order->payments()->value('tx_ref');

        $this->get('/webhooks/chapa/callback?trx_ref='.$txRef);
        $this->webhook($txRef);
        $this->actingAs($this->staff('finance'))->post("/admin/orders/{$order->id}/reverify");

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_order_pages_need_a_signed_link(): void
    {
        $order = $this->pendingOrderWithFake();

        $this->get('/pay/'.$order->order_number)->assertForbidden();
        $this->get('/pay/'.$order->order_number.'/return')->assertForbidden();
        $this->get(URL::signedRoute('pay.show', ['order' => $order->order_number]))
            ->assertOk()->assertSee($order->order_number)->assertSee('Pay with Chapa')->assertDontSee('+251911000000');
    }

    /** Chapa's verify answers an ended, unpaid attempt with one combined word: "failed/cancelled". */
    public function test_a_failed_or_cancelled_attempt_says_so_and_can_be_retried(): void
    {
        $this->fakeChapa(['status' => 'failed/cancelled']);
        $order = $this->pendingOrder();

        $this->get(URL::signedRoute('pay.return', ['order' => $order->order_number]))
            ->assertOk()->assertSee('Payment was not completed')->assertDontSee('Reload this page')->assertSee('Pay with Chapa');

        $this->assertSame('failed', $order->payments()->value('status'));
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_return_page_verifies_and_shows_the_receipt(): void
    {
        $order = $this->pendingOrderWithFake();

        $this->get(URL::signedRoute('pay.return', ['order' => $order->order_number]))
            ->assertOk()->assertSee('Payment received');
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_keys_are_never_stored_or_rendered_by_the_admin_settings(): void
    {
        $this->enableChapa();
        $admin = $this->staff('super_admin');

        $this->actingAs($admin)->get('/admin/settings')->assertOk()
            ->assertDontSee('CHASECK_TEST-fake')->assertDontSee('fake-webhook-secret');
        $this->actingAs($admin)->put('/admin/settings', ['chapa_enabled' => '1', 'chapa_secret_key' => 'typed-in']);
        $this->assertDatabaseMissing('site_settings', ['value' => 'typed-in']);
    }

    public function test_admin_switch_off_disables_payment(): void
    {
        Http::fake();
        $this->enableChapa();
        SiteSetting::set('chapa_enabled', '0');
        [$s, $o] = $this->item();

        $this->checkout($s->id, $o->id)->assertRedirect(route('cart.success'));
        Http::assertNothingSent();
    }
}
