<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryRequest;
use App\Models\Inquiry;
use App\Support\InquiryNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * The single endpoint for every public message form (contact, support,
 * quote, service request, plan subscription). Behind the "public-forms" rate
 * limit and the honeypot/timing guard (routes/web.php).
 *
 * Order: save first, then queue the emails. The visitor gets the thank-you
 * message with a reference number even if mail is down.
 */
class InquiryController extends Controller
{
    public function store(InquiryRequest $request, InquiryNotifier $notifier): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $inquiry = Inquiry::create([
            'type' => $data['type'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'service' => $data['service'] ?? null,
            'plan' => $data['plan'] ?? null,
            'message' => $data['message'] ?? null,
            'language' => preg_match('/\p{Ethiopic}/u', ($data['message'] ?? '').$data['name']) ? 'am' : 'en',
            'source_page' => Str::limit(parse_url(url()->previous(), PHP_URL_PATH) ?: '/', 190, ''),
            // Enough to spot abuse from one address, without storing the IP itself.
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        $notifier->notify($inquiry);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'reference' => $inquiry->reference], 201);
        }

        $anchor = InquiryRequest::anchorFor($data['form_anchor'] ?? null);

        return redirect()->to(InquiryRequest::backTo($anchor))->with('inquiry_success', [
            'anchor' => $anchor,
            'reference' => $inquiry->reference,
            'type' => $inquiry->type,
        ]);
    }
}
