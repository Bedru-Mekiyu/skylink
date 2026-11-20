<?php

namespace App\Rules;

use App\Models\Video;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A video link the site can play: YouTube (watch, youtu.be, shorts, embed, live) or the full link of one TikTok video. */
class EmbeddableVideoUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $probe = new Video(['video_url' => (string) $value]);

        if ($probe->platform !== null) {
            return;
        }

        $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));

        if (str_contains($host, 'tiktok.com')) {
            $fail(preg_match('~^(vm|vt)\.~', $host)
                ? 'This is a short TikTok link, which cannot be played on the site. Open it in your browser first, then copy the full address (tiktok.com/@name/video/1234567890123456789).'
                : 'This TikTok link is not the link of one video. Use the full address (tiktok.com/@name/video/1234567890123456789).');

            return;
        }

        if (str_contains($host, 'youtu')) {
            $fail('This YouTube link has no video in it. Use a link like youtube.com/watch?v=… or youtu.be/…');

            return;
        }

        $fail('This link cannot be played on the site. Use a YouTube link (youtube.com/watch?v=… or youtu.be/…) or the full link of one TikTok video (tiktok.com/@name/video/…).');
    }
}
