<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

/**
 * Marks scheduled articles whose time has come as "published".
 *
 * Visitors already see them on time without this (the pages check the time on
 * every request); this only keeps the admin list's status column tidy.
 * Runs every minute via the scheduler (routes/console.php) when the server's
 * cron runs `php artisan schedule:run`.
 */
class PublishScheduledNews extends Command
{
    protected $signature = 'news:publish-scheduled';

    protected $description = 'Mark scheduled news articles whose publish time has passed as published';

    public function handle(): int
    {
        $count = News::where('status', News::STATUS_SCHEDULED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => News::STATUS_PUBLISHED]);
        \App\Support\PublicCache::bust(); // bulk update: no model events

        $this->info("{$count} scheduled article(s) published.");

        return self::SUCCESS;
    }
}
