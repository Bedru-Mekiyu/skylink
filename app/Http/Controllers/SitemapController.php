<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Response;

/** /sitemap.xml: the public pages, published news articles, service categories and services, always on the canonical host (APP_URL). */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $urls = [['/', null]];
        foreach (['about', 'services', 'products', 'portfolio', 'news', 'contact'] as $p) {
            $urls[] = ['/'.$p, null];
        }
        try {
            foreach (ServiceCategory::query()->where('is_published', true)->get() as $c) {
                $urls[] = ['/services/category/'.$c->slug, $c->updated_at];
            }
            foreach (Service::query()->whereNotNull('slug')->get() as $s) {
                $urls[] = ['/services/detail/'.$s->slug, $s->updated_at];
            }
            foreach (News::visible()->whereNotNull('slug')->orderByDesc('id')->limit(1000)->get() as $n) {
                $urls[] = ['/news/'.$n->slug, $n->updated_at];
            }
        } catch (\Throwable) {
            // a missing table never breaks the sitemap: the static pages are still listed
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as [$path, $mod]) {
            $xml .= '  <url><loc>'.e($base.$path).'</loc>'.($mod ? '<lastmod>'.$mod->toDateString().'</lastmod>' : '').'</url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
