<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for Chapa (https://developer.chapa.co). It never marks anything paid: see PaymentProcessor.
 * Keys come from the environment only and are never logged, stored or shown.
 */
class ChapaService
{
    /** Needs the server setting, a secret key AND the admin switch. Any one missing means off. */
    public function isEnabled(): bool
    {
        return (bool) config('services.chapa.enabled')
            && filled(config('services.chapa.secret_key'))
            && SiteSetting::get('chapa_enabled') === '1';
    }

    public function isTestMode(): bool
    {
        return str_contains((string) config('services.chapa.secret_key'), '_TEST');
    }

    /** @return array{ok: bool, checkout_url?: string, error?: string} */
    public function initialize(Payment $payment, Order $order, string $returnUrl, string $callbackUrl): array
    {
        $names = preg_split('/\s+/', trim($order->customer_name), 2);

        $payload = array_filter([
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => $payment->currency,
            // Documented limits: email 50 characters, first name 35.
            'email' => strlen($order->customer_email) <= 50 ? $order->customer_email : null,
            'first_name' => mb_substr($names[0] ?: 'Customer', 0, 35),
            'last_name' => mb_substr($names[1] ?? '', 0, 35),
            'phone_number' => self::localPhone($order->customer_phone),
            'tx_ref' => $payment->tx_ref,
            'callback_url' => $callbackUrl,
            'return_url' => $returnUrl,
        ]);

        try {
            $response = $this->client()->post($this->url('transaction/initialize'), $payload);
        } catch (\Throwable $e) {
            Log::error('Chapa initialize unreachable', ['order' => $order->order_number, 'error' => $e::class]);

            return ['ok' => false, 'error' => __('We could not reach the payment service. Please try again.')];
        }

        if ($response->successful() && $response->json('data.checkout_url')) {
            return ['ok' => true, 'checkout_url' => $response->json('data.checkout_url')];
        }

        // The response may echo customer details, so only the status is logged.
        Log::warning('Chapa initialize refused', ['order' => $order->order_number, 'http' => $response->status()]);

        return ['ok' => false, 'error' => __('The payment service did not accept the request. Please try again or contact us.')];
    }

    /**
     * Ask Chapa what really happened to a transaction.
     *
     * @return array{ok: bool, status?: string, amount?: ?string, currency?: ?string, tx_ref?: ?string, reference?: ?string, method?: ?string, mode?: ?string}
     */
    public function verify(string $txRef): array
    {
        if (! filled(config('services.chapa.secret_key'))) {
            return ['ok' => false];
        }

        try {
            $response = $this->client()->get($this->url('transaction/verify/'.rawurlencode($txRef)));
        } catch (\Throwable $e) {
            Log::error('Chapa verify unreachable', ['tx_ref' => $txRef, 'error' => $e::class]);

            return ['ok' => false];
        }

        if (! $response->successful() || $response->json('status') !== 'success') {
            return ['ok' => false];
        }

        $data = (array) $response->json('data');

        return [
            'ok' => true,
            'status' => strtolower((string) ($data['status'] ?? '')),
            'amount' => isset($data['amount']) ? (string) $data['amount'] : null,
            'currency' => $data['currency'] ?? null,
            'tx_ref' => $data['tx_ref'] ?? null,
            'reference' => $data['reference'] ?? null,
            'method' => $data['method'] ?? null,
            'mode' => $data['mode'] ?? null,
        ];
    }

    /**
     * x-chapa-signature = HMAC-SHA256 of the raw body. The other header Chapa sends (chapa-signature) signs a
     * constant, so it proves nothing about the message and is not accepted.
     */
    public function hasValidSignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('services.chapa.webhook_secret');

        return $secret !== '' && filled($signature) && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    /** Chapa accepts only 10-digit Ethiopian numbers (09… or 07…); anything else is left out. */
    public static function localPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($digits, '251')) {
            $digits = '0'.substr($digits, 3);
        }

        return preg_match('/^0[79]\d{8}$/', $digits) ? $digits : null;
    }

    private function client()
    {
        return Http::withToken((string) config('services.chapa.secret_key'))->acceptJson()->timeout(15);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.chapa.base_url'), '/').'/'.$path;
    }
}
