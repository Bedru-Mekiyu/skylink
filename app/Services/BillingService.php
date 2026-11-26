<?php

namespace App\Services;

use App\Mail\SubscriptionRenewalReminderMail;
use App\Models\BillingSubscription;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** Recurring plans: one subscription per billing term in an order, one new order per period. */
class BillingService
{
    public function createFor(Order $order): void
    {
        $recurring = $order->items()->get()
            ->filter(fn ($item) => isset(BillingSubscription::TERM_MONTHS[$item->billing_term]))
            ->groupBy('billing_term');

        foreach ($recurring as $term => $items) {
            $subscription = BillingSubscription::create([
                'reference' => BillingSubscription::newReference(),
                'first_order_id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'company' => $order->company,
                'billing_term' => $term,
                'amount' => $items->sum('line_total'),
                'currency' => $order->currency,
                'status' => 'pending',
            ]);

            $order->items()->whereKey($items->modelKeys())->update(['billing_subscription_id' => $subscription->id]);
        }
    }

    /** Called once, inside the transaction that marks the order paid. */
    public function orderPaid(Order $order): void
    {
        if ($order->billing_subscription_id) {
            $subscription = BillingSubscription::lockForUpdate()->findOrFail($order->billing_subscription_id);
            $end = $subscription->current_period_end;
            // Paid early: continue from the old end. Paid late: the new period starts today.
            $from = $end && $end->gte(today()) ? $end : today();
            $next = $from->copy()->addMonthsNoOverflow(BillingSubscription::TERM_MONTHS[$subscription->billing_term]);
            // A late payment never silently revives a cancelled plan.
            $subscription->update(['current_period_end' => $next]);
            $order->update(['renewal_due_date' => $next]);

            return;
        }

        $ids = $order->items()->whereNotNull('billing_subscription_id')->pluck('billing_subscription_id')->unique();

        foreach (BillingSubscription::whereKey($ids)->where('status', 'pending')->get() as $subscription) {
            $subscription->update([
                'status' => 'active',
                'current_period_end' => today()->addMonthsNoOverflow(BillingSubscription::TERM_MONTHS[$subscription->billing_term]),
            ]);
        }

        $next = BillingSubscription::whereKey($ids)->min('current_period_end');
        $order->update(['renewal_due_date' => $next]);
    }

    /** The unpaid order for the coming period, created once. The agreed price is kept. */
    public function renewalOrder(BillingSubscription $subscription): Order
    {
        return DB::transaction(function () use ($subscription) {
            $subscription = BillingSubscription::lockForUpdate()->findOrFail($subscription->id);
            $periodStart = $subscription->current_period_end;

            $existing = $subscription->renewalOrders()->whereDate('period_start', $periodStart)->latest('id')->first();
            if ($existing) {
                return $existing;
            }

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_name' => $subscription->customer_name,
                'customer_email' => $subscription->customer_email,
                'customer_phone' => $subscription->customer_phone,
                'company' => $subscription->company,
                'subtotal' => $subscription->amount,
                'tax' => 0,
                'total' => $subscription->amount,
                'currency' => $subscription->currency,
                'status' => 'pending',
                'payment_method' => 'chapa',
                'billing_term' => $subscription->billing_term,
                'billing_subscription_id' => $subscription->id,
                'period_start' => $periodStart,
                'renewal_note' => 'Renewal of '.$subscription->reference,
            ]);

            $lines = $subscription->firstOrder->items()->where('billing_subscription_id', $subscription->id)->get();
            foreach ($lines as $line) {
                $order->items()->create($line->only([
                    'item_type', 'item_id', 'option_id', 'title', 'option_name', 'billing_term',
                    'unit_price', 'quantity', 'line_total', 'currency',
                ]));
            }

            return $order;
        });
    }

    public function sendPaymentLink(Order $order): void
    {
        Mail::to($order->customer_email)->queue(new SubscriptionRenewalReminderMail($order));
        $order->update(['reminder_sent_at' => now()]);
    }
}
