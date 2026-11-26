<?php

namespace App\Console\Commands;

use App\Models\BillingSubscription;
use App\Services\BillingService;
use Illuminate\Console\Command;

class RenewSubscriptions extends Command
{
    protected $signature = 'billing:renew {--days=7 : How many days before the period ends}';

    protected $description = 'Create the next order for recurring plans that are ending and email its payment link (once)';

    public function handle(BillingService $billing): int
    {
        if (! config('services.renewals.reminders_enabled')) {
            $this->info('Renewal emails are off (RENEWAL_REMINDERS_ENABLED=false). Nothing was created or sent.');

            return self::SUCCESS;
        }

        $due = BillingSubscription::where('status', 'active')
            ->whereDate('current_period_end', '<=', today()->addDays((int) $this->option('days')))
            ->get();

        $sent = 0;
        foreach ($due as $subscription) {
            $order = $billing->renewalOrder($subscription);
            if ($order->isPayable() && ! $order->reminder_sent_at) {
                $billing->sendPaymentLink($order);
                $sent++;
            }
        }

        $this->info("{$due->count()} plan(s) due, {$sent} payment link(s) sent.");

        return self::SUCCESS;
    }
}
