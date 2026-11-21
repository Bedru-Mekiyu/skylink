<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Tells Bing (IndexNow, shared with other search engines) which URLs exist or changed. Never runs by itself.
 * Needs INDEXNOW_KEY in .env (8-128 letters, digits or dashes); the key file is served at https://skylinkict.com/<key>.txt.
 * Dry run by default: it lists what it would send. Add --send to submit.
 */
class SeoPing extends Command
{
    protected $signature = 'seo:ping {--send : Really submit the sitemap URLs to IndexNow}';

    protected $description = 'Submit the sitemap URLs to IndexNow (Bing); dry run unless --send';

    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public function handle(): int
    {
        $key = (string) config('seo.indexnow_key');
        if (! preg_match('/^[A-Za-z0-9-]{8,128}$/', $key)) {
            $this->error('INDEXNOW_KEY is not set (8-128 letters, digits or dashes). Add it to .env, run php artisan config:cache, then try again.');

            return self::FAILURE;
        }
        $base = rtrim((string) config('app.url'), '/');
        $urls = array_map(fn ($u) => $base.$u[0], SitemapController::urls());
        $this->line(count($urls).' URLs, key file: '.$base.'/'.$key.'.txt');
        if (! $this->option('send')) {
            $this->info('DRY RUN: nothing was sent. Add --send to submit.');

            return self::SUCCESS;
        }
        $keyFile = Http::timeout(15)->get($base.'/'.$key.'.txt');
        if (! $keyFile->ok() || trim($keyFile->body()) !== $key) {
            $this->error('The key file is not reachable at '.$base.'/'.$key.'.txt; IndexNow would reject the request. Nothing was sent.');

            return self::FAILURE;
        }
        $host = parse_url($base, PHP_URL_HOST);
        foreach (array_chunk($urls, 9000) as $chunk) {
            $res = Http::timeout(30)->acceptJson()->post(self::ENDPOINT, ['host' => $host, 'key' => $key, 'keyLocation' => $base.'/'.$key.'.txt', 'urlList' => $chunk]);
            $this->line('IndexNow answered '.$res->status().' for '.count($chunk).' URLs');
            if (! in_array($res->status(), [200, 202], true)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
