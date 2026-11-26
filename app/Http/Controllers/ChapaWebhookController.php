<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\ChapaService;
use App\Services\PaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Chapa tells us something happened; PaymentProcessor then asks Chapa what really happened. */
class ChapaWebhookController extends Controller
{
    /** POST, signed with the webhook secret. */
    public function handle(Request $request, ChapaService $chapa, PaymentProcessor $payments): JsonResponse
    {
        if (! $chapa->hasValidSignature($request->getContent(), $request->header('x-chapa-signature'))) {
            Log::warning('Chapa webhook signature rejected', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $this->confirm($request->json('tx_ref'), $payments);
    }

    /** GET callback_url. Unsigned by design, so it can only trigger a verification. */
    public function callback(Request $request, PaymentProcessor $payments): JsonResponse
    {
        return $this->confirm($request->query('trx_ref', $request->json('trx_ref')), $payments);
    }

    private function confirm(mixed $txRef, PaymentProcessor $payments): JsonResponse
    {
        $payment = is_string($txRef) ? Payment::with('order')->where('tx_ref', $txRef)->first() : null;
        if (! $payment) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $status = $payments->confirm($payment);

        // 200 stops Chapa retrying; "pending" keeps it trying.
        return response()->json(['status' => $status], $status === 'pending' ? 202 : 200);
    }
}
