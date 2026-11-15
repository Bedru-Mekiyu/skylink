<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryRequest;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

/**
 * Homepage newsletter sign-up.
 *
 * The visitor always gets the same friendly answer, whether the address was
 * new or already on the list, so the form cannot be used to find out who is
 * subscribed. The answer is shown at the form (#newsletter), not at the top.
 */
class SubscriptionController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $back = InquiryRequest::backTo('newsletter');

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'max:190', 'email:rfc'],
        ]);

        if ($validator->fails()) {
            return redirect()->to($back)->withErrors($validator, 'newsletter')->withInput();
        }

        Subscriber::firstOrCreate(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        return redirect()->to($back)->with('newsletter_status', 'Thank you! You are on our newsletter list.');
    }

    /** Opened from the signed link in a newsletter: ask to confirm (no change on GET). */
    public function unsubscribeForm(string $subscriber)
    {
        $exists = Subscriber::whereKey($subscriber)->exists();

        return view('pages.newsletter-unsubscribe', [
            'done' => ! $exists,
            'confirmUrl' => URL::signedRoute('newsletter.unsubscribe.confirm', ['subscriber' => $subscriber]),
        ]);
    }

    public function unsubscribe(string $subscriber)
    {
        Subscriber::whereKey($subscriber)->delete();

        return view('pages.newsletter-unsubscribe', ['done' => true, 'confirmUrl' => null]);
    }
}
