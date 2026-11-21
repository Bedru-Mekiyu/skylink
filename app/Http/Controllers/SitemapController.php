<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Video;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

/**
 * /sitemap.xml: the public pages, published news, products, videos, service categories and services, always on the canonical
 * host (APP_URL). lastmod is the real change date: the record's updated_at, or for fixed pages the date of their view file.
 * Admin pages and hidden gallery photos are never listed.
 */
class SitemapController extends Controller
{
    /** @return list<array{0: string, 1: ?string}> path and lastmod (Y-m-d) */
    public static function urls(): array
    {
        $day = fn (?CarbonInterface $d) => $d?->toDateString();
        $file = fn (string $view) => is_file($f = resource_path('views/'.$view.'.blade.php')) ? date('Y-m-d', filemtime($f)) : null;
        $latestNews = null;
        $urls = [];
        $dynamic = [];

        try {
            foreach (News::visible()->whereNotNull('slug')->orderByDesc('id')->limit(5000)->get() as $n) {
                $dynamic[] = ['/news/'.$n->slug, $day($n->updated_at ?? $n->created_at)];
                $latestNews ??= $day($n->updated_at ?? $n->created_at);
            }
            foreach (ServiceCategory::query()->where('is_published', true)->get() as $c) {
                $dynamic[] = ['/services/category/'.$c->slug, $day($c->updated_at)];
            }
            foreach (Service::query()->whereNotNull('slug')->get() as $s) {
                $dynamic[] = ['/services/detail/'.$s->slug, $day($s->updated_at)];
            }
            foreach (Product::query()->whereNotNull('slug')->get() as $p) {
                $dynamic[] = ['/products/'.rawurlencode((string) $p->category).'/'.$p->slug, $day($p->updated_at)];
            }
            foreach (Video::query()->whereNotNull('slug')->get() as $v) {
                $dynamic[] = ['/videos/'.$v->slug, $day($v->updated_at)];
            }
        } catch (\Throwable) {
            // a missing table never breaks the sitemap: the fixed pages are still listed
        }

        $urls[] = ['/', $file('pages/index')];
        $urls[] = ['/about', $file('pages/aboutpage')];
        $urls[] = ['/services', $file('services/index')];
        $urls[] = ['/products', $file('pages/products')];
        $urls[] = ['/portfolio', $file('pages/portfolio')];
        $urls[] = ['/news', $latestNews ?? $file('pages/news-events')];
        $urls[] = ['/contact', $file('pages/contact')];

        return [...$urls, ...$dynamic];
    }

    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (self::urls() as [$path, $mod]) {
            $xml .= '  <url><loc>'.e($base.$path).'</loc>'.($mod ? '<lastmod>'.$mod.'</lastmod>' : '').'</url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
