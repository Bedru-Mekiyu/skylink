<?php

namespace Tests\Feature;

use App\Mail\SubscriptionRenewalReminderMail;
use App\Models\BillingSubscription;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\CheckoutHelpers;
use Tests\TestCase;

class BillingSubscriptionTest extends TestCase
{
    use CheckoutHelpers, RefreshDatabase;

    private function monthly(): array
    {
        $this->fakeChapa();
        $this->enableChapa();
        $this->travelTo('2026-03-01 10:00:00');
        $service = $this->service();
        $option = $this->option($service, ['billing_term' => 'monthly', 'term_prices' => ['monthly' => 500, 'annually' => 5000]]);

        return [$service, $option];
    }

    public function test_paying_a_monthly_plan_starts_one_period(): void
    {
        [$s, $o] = $this->monthly();

        $this->paidOrder($s->id, $o->id, 'monthly');

        $sub = BillingSubscription::sole();
        $this->assertSame(['active', 'monthly', '500.00'], [$sub->status, $sub->billing_term, $sub->amount]);
        $this->assertSame('2026-04-01', $sub->current_period_end->toDateString());
    }

    public function test_one_time_purchases_create_no_subscription(): void
    {
        $this->fakeChapa();
        $this->enableChapa();
        $s = $this->service();

        $this->paidOrder($s->id, $this->option($s)->id);

        $this->assertSame(0, BillingSubscription::count());
    }

    public function test_renewal_is_invoiced_once_at_the_agreed_price_and_paying_extends(): void
    {
        [$s, $o] = $this->monthly();
        $this->paidOrder($s->id, $o->id, 'monthly');
        $o->update(['term_prices' => ['monthly' => 900]]);
        Mail::fake();
        config(['services.renewals.reminders_enabled' => true]);

        $this->travelTo('2026-03-27 08:00:00');
        $this->artisan('billing:renew')->assertSuccessful();
        $this->artisan('billing:renew')->assertSuccessful();

        $this->assertSame(2, Order::count());
        $renewal = Order::latest('id')->first();
        $this->assertSame(['pending', '500.00'], [$renewal->status, $renewal->total]);
        Mail::assertQueued(SubscriptionRenewalReminderMail::class, 1);
        Mail::assertQueued(SubscriptionRenewalReminderMail::class, fn ($m) => str_contains($m->render(), '/pay/'.$renewal->order_number.'?signature='));

        app(\App\Services\PaymentProcessor::class)->start($renewal);
        $this->get('/webhooks/chapa/callback?trx_ref='.$renewal->payments()->latest('id')->value('tx_ref'))->assertOk();

        $this->assertSame('2026-05-01', BillingSubscription::sole()->current_period_end->toDateString());
    }

    public function test_no_reminders_while_disabled(): void
    {
        [$s, $o] = $this->monthly();
        $this->paidOrder($s->id, $o->id, 'monthly');
        Mail::fake();

        $this->travelTo('2026-03-27 08:00:00');
        $this->artisan('billing:renew')->assertSuccessful();

        $this->assertSame(1, Order::count());
        Mail::assertNothingQueued();
    }

    public function test_cancelled_subscriptions_are_not_renewed(): void
    {
        [$s, $o] = $this->monthly();
        $this->paidOrder($s->id, $o->id, 'monthly');
        $sub = BillingSubscription::sole();
        config(['services.renewals.reminders_enabled' => true]);

        $this->actingAs($this->staff('finance'))->post("/admin/billing/{$sub->id}/cancel")->assertRedirect();
        $this->travelTo('2026-03-27 08:00:00');
        $this->artisan('billing:renew')->assertSuccessful();

        $this->assertSame('cancelled', $sub->fresh()->status);
        $this->assertSame(1, Order::count());
    }

    public function test_finance_can_send_a_payment_link_by_hand(): void
    {
        [$s, $o] = $this->monthly();
        $this->paidOrder($s->id, $o->id, 'monthly');
        Mail::fake();
        $sub = BillingSubscription::sole();
        $finance = $this->staff('finance');

        $this->actingAs($finance)->get('/admin/billing')->assertOk()->assertSee($sub->reference);
        $this->actingAs($finance)->post("/admin/billing/{$sub->id}/send-link")->assertRedirect();

        $this->assertSame(2, Order::count());
        Mail::assertQueued(SubscriptionRenewalReminderMail::class, 1);
    }
}
