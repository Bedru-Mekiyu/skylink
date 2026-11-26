<?php

namespace App\Services;

use App\Mail\PaymentReceivedMail;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/** The only place an order is started with the gateway or marked paid. */
class PaymentProcessor
{
    public function __construct(private ChapaService $chapa, private BillingService $billing) {}

    /** @return array{ok: bool, checkout_url?: string, error?: string} */
    public function start(Order $order): array
    {
        if (! $this->chapa->isEnabled()) {
            return ['ok' => false, 'error' => __('Online payment is not available at the moment.')];
        }
        if (! $order->isPayable()) {
            return ['ok' => false, 'error' => __('This order cannot be paid online.')];
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'tx_ref' => 'SKY-'.Str::ulid(),
            'gateway' => 'chapa',
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);

        $result = $this->chapa->initialize(
            $payment,
            $order,
            URL::signedRoute('pay.return', ['order' => $order->order_number]),
            route('webhooks.chapa.callback'),
        );

        if (! $result['ok']) {
            $payment->update(['status' => 'failed']);
        }

        return $result;
    }

    /**
     * Check one payment with Chapa and, only if status, reference, amount and currency all match, mark it paid.
     * Safe to call any number of times (return page, callback, webhook, admin button).
     *
     * @return string paid | pending | failed | mismatch
     */
    public function confirm(Payment $payment): string
    {
        if ($payment->isSuccessful()) {
            return 'paid';
        }

        $result = $this->chapa->verify($payment->tx_ref);

        if (! $result['ok']) {
            return 'pending';
        }
        if ($result['status'] !== 'success') {
            // Chapa reports an ended, unpaid attempt as "failed/cancelled"; an open one stays "pending".
            if (in_array($result['status'], ['failed', 'cancelled', 'failed/cancelled'], true)) {
                $payment->update(['status' => 'failed']);

                return 'failed';
            }

            return 'pending';
        }

        if ($result['tx_ref'] !== $payment->tx_ref
            || $this->cents($result['amount']) !== $this->cents($payment->amount)
            || $this->cents($result['amount']) !== $this->cents($payment->order->total)
            || strtoupper((string) $result['currency']) !== strtoupper($payment->currency)) {
            Log::alert('Chapa verification does not match the order; not marked paid', [
                'order' => $payment->order->order_number, 'tx_ref' => $payment->tx_ref,
                'expected' => $payment->amount.' '.$payment->currency,
                'reported' => $result['amount'].' '.$result['currency'],
            ]);

            return 'mismatch';
        }

        $firstTime = DB::transaction(function () use ($payment, $result) {
            // Row lock: the callback and the webhook often arrive together.
            $order = Order::lockForUpdate()->findOrFail($payment->order_id);
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($locked->isSuccessful()) {
                return false;
            }

            // Only non-personal fields are kept.
            $locked->markAsSuccessful((string) ($result['reference'] ?? $locked->tx_ref), array_intersect_key(
                $result, array_flip(['status', 'amount', 'currency', 'reference', 'method', 'mode']),
            ));

            if ($order->isPaid()) {
                Log::alert('Second successful payment for one order: refund needed', ['order' => $order->order_number, 'tx_ref' => $locked->tx_ref]);

                return false;
            }

            $order->markAsPaid();
            $this->billing->orderPaid($order);

            return true;
        });

        $payment->refresh()->load('order');

        if ($firstTime) {
            $this->sendReceipts($payment->order);
        }

        return 'paid';
    }

    /** A mail problem must never undo or hide a payment. */
    private function sendReceipts(Order $order): void
    {
        foreach (array_filter([$order->customer_email, config('contact.inbox')]) as $address) {
            try {
                Mail::to($address)->queue(new PaymentReceivedMail($order));
            } catch (\Throwable $e) {
                Log::error('Payment receipt email failed', ['order' => $order->order_number, 'error' => $e::class]);
            }
        }
    }

    private function cents(mixed $amount): ?int
    {
        return is_numeric($amount) ? (int) round(((float) $amount) * 100) : null;
    }
}
