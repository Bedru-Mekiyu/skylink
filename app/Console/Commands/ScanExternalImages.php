<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\Portfolio;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Video;
use Illuminate\Console\Command;

/**
 * READ-ONLY report: lists every product, service, category, portfolio item, news article and video whose image
 * is an address on another website (hotlinked, can disappear) or a local file that is missing.
 * Prints title + admin edit address. Changes nothing.
 */
class ScanExternalImages extends Command
{
    protected $signature = 'images:scan-external {--live : Also check that external addresses answer (slow, makes web requests)}';

    protected $description = 'List content whose image is hotlinked from another site or missing on disk (read-only)';

    public function handle(): int
    {
        $own = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $rows = [];
        $sets = [
            ['Product', Product::query(), ['image'], 'admin.products.edit', 'name'],
            ['Service', Service::query(), ['image'], 'admin.services.edit', 'title'],
            ['Service category', ServiceCategory::query(), ['image'], 'admin.service-categories.edit', 'name'],
            ['Portfolio', Portfolio::query(), ['image', 'video_poster'], 'admin.portfolio.edit', 'title'],
            ['News', News::query(), ['image', 'image_thumb', 'image_share'], 'admin.news.edit', 'title'],
            ['Video', Video::query(), ['thumbnail_image'], 'admin.videos.edit', 'title'],
        ];
        foreach ($sets as [$label, $query, $cols, $route, $titleCol]) {
            foreach ($query->get() as $m) {
                foreach ($cols as $col) {
                    $v = trim((string) $m->{$col});
                    if ($v === '') {
                        continue;
                    }
                    $problem = $this->problem($v, $own);
                    if ($problem) {
                        $rows[] = [$label, \Illuminate\Support\Str::limit((string) $m->{$titleCol}, 40), $col, $problem, route($route, $m, false)];
                    }
                }
            }
        }
        if (! $rows) {
            $this->info('No external or missing images found.');

            return self::SUCCESS;
        }
        $this->table(['Type', 'Title', 'Field', 'Problem', 'Admin edit'], $rows);
        $this->line(count($rows).' item(s). Fix them in the admin (upload an image) before the host of the picture goes away.');

        return self::SUCCESS;
    }

    private function problem(string $v, string $ownHost): ?string
    {
        if (preg_match('~^https?://~i', $v)) {
            $host = strtolower((string) parse_url($v, PHP_URL_HOST));
            if ($host !== $ownHost) {
                $alive = $this->option('live') ? ($this->answers($v) ? ' (answers now)' : ' (NOT answering)') : '';

                return 'hotlinked from '.$host.$alive;
            }
            $v = (string) parse_url($v, PHP_URL_PATH);
        }
        $rel = ltrim($v, '/');
        $path = str_starts_with($rel, 'storage/') ? storage_path('app/public/'.substr($rel, 8))
            : (is_file(public_path($rel)) ? public_path($rel) : storage_path('app/public/'.$rel));

        return is_file($path) ? null : 'file missing on disk';
    }

    private function answers(string $url): bool
    {
        try {
            return \Illuminate\Support\Facades\Http::timeout(8)->head($url)->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
