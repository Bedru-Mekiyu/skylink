<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BillingSubscription;
use App\Services\BillingService;
use App\Services\ChapaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['pending', 'active', 'cancelled'], true) ? $request->query('status') : null;

        return view('admin.billing.index', [
            'subscriptions' => BillingSubscription::with('renewalOrders')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw('current_period_end is null, current_period_end')
                ->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function sendLink(BillingSubscription $subscription, BillingService $billing, ChapaService $chapa): RedirectResponse
    {
        if ($subscription->status !== 'active') {
            return back()->with('error', 'Only an active plan can be sent a payment link.');
        }
        if (! $chapa->isEnabled()) {
            return back()->with('error', 'Online payment is switched off, so the link could not be paid. Turn it on first.');
        }

        $order = $billing->renewalOrder($subscription);
        if (! $order->isPayable()) {
            return back()->with('error', 'The renewal for this period is already settled.');
        }
        $billing->sendPaymentLink($order);
        AdminAuditLog::record('billing.link_sent', 'BillingSubscription', $subscription->id, ['order' => $order->order_number]);

        return back()->with('success', "Payment link for {$subscription->reference} queued to the customer.");
    }

    public function cancel(BillingSubscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        // Unpaid renewal links stop working.
        $subscription->renewalOrders()->where('status', 'pending')->update(['status' => 'cancelled']);
        AdminAuditLog::record('billing.cancelled', 'BillingSubscription', $subscription->id, ['reference' => $subscription->reference]);

        return back()->with('success', "{$subscription->reference} cancelled. No further links will be sent.");
    }
}
