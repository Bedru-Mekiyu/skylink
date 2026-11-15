<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * One-time token per rendered form, so the same form cannot be saved twice
 * (double click, browser resubmit, two tabs posting the same form).
 *
 * - issue():  a new token, remembered in the user's session (last 20 per form).
 * - claim():  true only for the first request that presents a token issued to
 *             this session. Cache::add is atomic, so two parallel requests
 *             cannot both win.
 */
class FormSubmissionToken
{
    private const KEEP_PER_FORM = 20;
    private const TTL_HOURS = 12;

    public static function issue(string $form): string
    {
        $token = Str::random(40);
        $key = self::sessionKey($form);
        $tokens = array_slice(array_merge(session()->get($key, []), [$token]), -self::KEEP_PER_FORM);
        session()->put($key, $tokens);

        return $token;
    }

    public static function wasIssued(string $form, ?string $token): bool
    {
        return is_string($token) && in_array($token, session()->get(self::sessionKey($form), []), true);
    }

    public static function claim(string $form, string $token): bool
    {
        return Cache::add(self::cacheKey($form, $token), 0, now()->addHours(self::TTL_HOURS));
    }

    /** Give the token back when saving failed, so the admin can fix the problem and resubmit. */
    public static function release(string $form, string $token): void
    {
        Cache::forget(self::cacheKey($form, $token));
    }

    /** Remember what the first submission created, to point the second one to it. */
    public static function remember(string $form, string $token, int $id): void
    {
        Cache::put(self::cacheKey($form, $token), $id, now()->addHours(self::TTL_HOURS));
    }

    public static function resultOf(string $form, string $token): ?int
    {
        $id = Cache::get(self::cacheKey($form, $token));

        return $id ? (int) $id : null;
    }

    private static function sessionKey(string $form): string
    {
        return 'form_tokens.'.$form;
    }

    private static function cacheKey(string $form, string $token): string
    {
        return 'form-token:'.$form.':'.hash('sha256', $token);
    }
}
