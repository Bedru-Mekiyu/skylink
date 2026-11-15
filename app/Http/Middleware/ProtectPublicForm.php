<?php

namespace App\Http\Middleware;

use App\Http\Requests\InquiryRequest;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bot protection for public forms (contact, chat, quote, subscribe), without
 * a CAPTCHA:
 *
 * 1. Honeypot: a visually hidden field that people never see or fill in.
 *    Simple bots fill every field.
 * 2. Time-to-submit: the form carries an encrypted "rendered at" timestamp
 *    (<x-form-guard />). A submission sooner than MIN_SECONDS after the page
 *    was rendered, or without a valid timestamp, is rejected.
 *
 * Rate limiting is separate: the "public-forms" limiter in
 * AppServiceProvider, applied before this middleware.
 */
class ProtectPublicForm
{
    public const HONEYPOT = 'website';

    public const TIMESTAMP = '_form_ts';

    public const MIN_SECONDS = 3;

    public const ERROR_BAG = 'formGuard';

    /** Page positions of the public forms (see InquiryRequest::ANCHORS), plus the newsletter box. */
    public const FORM_ANCHORS = ['contact-form', 'quote-form', 'service-form', 'subscribe-form', 'newsletter'];

    public const MESSAGE ='Sorry, your message could not be sent. Please wait a few seconds and try again.';

    public function handle(Request $request, Closure $next): Response
    {
        $reason = match (true) {
            filled($request->input(self::HONEYPOT)) => 'honeypot',
            ! $this->submittedByAHuman($request) => 'timing',
            default => null,
        };

        if ($reason !== null) {
            // No personal data: only which form and why.
            Log::notice('Public form submission blocked.', [
                'route' => $request->route()?->getName() ?? $request->path(),
                'reason' => $reason,
            ]);

            return self::reject($request, self::MESSAGE);
        }

        return $next($request);
    }

    /**
     * Friendly rejection: back to the form with the visitor's input kept
     * (minus the guard fields), or JSON for API-style clients.
     */
    public static function reject(Request $request, string $message, int $jsonStatus = 422): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $jsonStatus);
        }

        // Back to the form itself when the form says where it is (allow-listed).
        $anchor = $request->input('form_anchor');
        $target = in_array($anchor, self::FORM_ANCHORS, true)
            ? redirect()->to(InquiryRequest::backTo($anchor))
            : back();

        return $target
            ->withInput($request->except([self::HONEYPOT, self::TIMESTAMP, '_token']))
            ->withErrors(['form' => $message], self::ERROR_BAG);
    }

    /**
     * Encrypted timestamp for the form (used by <x-form-guard />).
     */
    public static function issueTimestamp(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    private function submittedByAHuman(Request $request): bool
    {
        $token = $request->input(self::TIMESTAMP);
        if (! is_string($token) || $token === '') {
            return false;
        }

        try {
            $renderedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return false;
        }

        return now()->timestamp - $renderedAt >= self::MIN_SECONDS;
    }
}
