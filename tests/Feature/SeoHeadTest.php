<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NewsCategory;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Titles, descriptions, canonicals, share tags, JSON-LD, sitemap and the 404 status of the public pages. */
class SeoHeadTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/', '/about', '/services', '/products', '/portfolio', '/news', '/contact'];

    private function makeNews(string $title, string $slug, string $excerpt): News
    {
        $cat = NewsCategory::create(['name' => 'News']);
        $news = new News(['title' => $title, 'news_category_id' => $cat->id, 'content' => $excerpt, 'excerpt' => $excerpt]);
        $news->forceFill(['slug' => $slug, 'status' => News::STATUS_PUBLISHED, 'published_at' => now()->subDay()])->save();

        return $news;
    }

    private function tag(string $html, string $pattern): ?string
    {
        return preg_match($pattern, $html, $m) ? html_entity_decode($m[1], ENT_QUOTES) : null;
    }

    public function test_every_public_page_has_a_unique_short_title_and_a_unique_description_of_the_right_length(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        $titles = [];
        $descriptions = [];
        foreach (self::PAGES as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $title = $this->tag($html, '#<title>(.*?)</title>#s');
            $desc = $this->tag($html, '#<meta name="description" content="([^"]*)"#');
            $this->assertNotNull($title, $path);
            $this->assertLessThanOrEqual($path === '/' ? 61 : 60, mb_strlen($title), "$path title: $title");
            $this->assertGreaterThanOrEqual(110, mb_strlen($desc), "$path description too short");
            $this->assertLessThanOrEqual(155, mb_strlen($desc), "$path description too long");
            $this->assertSame(1, substr_count($html, '<title>'), $path);
            $this->assertSame(1, preg_match_all('/<h1\b/i', $html), "$path must have exactly one h1");
            $titles[] = $title;
            $descriptions[] = $desc;
        }
        $this->assertCount(count(self::PAGES), array_unique($titles), 'titles are unique');
        $this->assertCount(count(self::PAGES), array_unique($descriptions), 'descriptions are unique');
        $this->assertSame('Sky Link Technologies | IT Solutions in Addis Ababa, Ethiopia', $titles[0]);
        $this->assertSame('About Us | Sky Link Technologies', $titles[1]);
        $this->assertSame('News and Events | Sky Link Technologies', $titles[5], 'route name content.index contains a dot');
        $this->assertSame('Software Development Services | Sky Link Technologies', $titles[2]);
    }

    public function test_canonical_and_share_tags_always_use_the_canonical_host(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        foreach (self::PAGES as $path) {
            $html = $this->get('https://www.skylinkict.com'.$path.'?utm=x')->assertOk()->getContent();
            $expect = 'https://skylinkict.com'.($path === '/' ? '/' : $path);
            $this->assertSame($expect, $this->tag($html, '#<link rel="canonical" href="([^"]*)"#'), $path);
            $this->assertSame($expect, $this->tag($html, '#property="og:url" content="([^"]*)"#'), $path);
            $this->assertNotNull($this->tag($html, '#property="og:site_name" content="([^"]*)"#'));
            $this->assertSame('en_US', $this->tag($html, '#property="og:locale" content="([^"]*)"#'));
            $this->assertStringContainsString('og-default.jpg', (string) $this->tag($html, '#property="og:image" content="([^"]*)"#'), $path);
            $this->assertSame('summary_large_image', $this->tag($html, '#name="twitter:card" content="([^"]*)"#'));
            $this->assertSame('en', $this->tag($html, '#<html lang="([^"]*)"#'));
        }
        $this->assertFileExists(public_path('assets/images/og-default.jpg'));
        [$w, $h] = getimagesize(public_path('assets/images/og-default.jpg'));
        $this->assertSame([1200, 630], [$w, $h]);
    }

    private function jsonLd(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m), 'JSON-LD present');
        $data = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $data['@context']);

        return $data['@graph'];
    }

    public function test_home_has_organization_and_local_business_data_from_config(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        $graph = $this->jsonLd($this->get('/')->getContent());
        $types = array_column($graph, '@type');
        $this->assertContains('Organization', $types);
        $this->assertContains('LocalBusiness', $types);
        $local = $graph[array_search('LocalBusiness', $types, true)];
        $this->assertSame('Sky Link Technologies', $local['name']);
        $this->assertSame('+251974967171', $local['telephone'], 'E.164, from config/company_contact.php');
        $this->assertSame('Addis Ababa', $local['address']['addressLocality']);
        $this->assertSame(config('company.email'), $local['email']);
        $this->assertArrayNotHasKey('openingHours', $local, 'opening hours only when they exist in config');
        $this->assertContains('https://www.linkedin.com/company/skylinkict/', $local['sameAs']);

        config(['seo.organization.opening_hours' => ['Mo-Fr 08:00-17:00']]);
        $local = collect($this->jsonLd($this->get('/')->getContent()))->firstWhere('@type', 'LocalBusiness');
        $this->assertSame(['Mo-Fr 08:00-17:00'], $local['openingHours']);
    }

    public function test_inner_pages_have_a_breadcrumb_list(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        $graph = $this->jsonLd($this->get('/about')->getContent());
        $crumb = collect($graph)->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(['Home', 'About Us'], array_column($crumb['itemListElement'], 'name'));
        $this->assertSame('https://skylinkict.com/about', $crumb['itemListElement'][1]['item']);
    }

    public function test_a_news_article_uses_its_own_headline_and_has_news_article_data(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        $news = $this->makeNews('Sky Link opens a new office', 'sky-link-opens-a-new-office', 'A short summary of the news.');
        $html = $this->get('/news/'.$news->slug)->assertOk()->getContent();
        $this->assertSame('Sky Link opens a new office', $this->tag($html, '#<title>(.*?)</title>#s'));
        $this->assertSame('A short summary of the news.', $this->tag($html, '#<meta name="description" content="([^"]*)"#'));
        $this->assertSame('https://skylinkict.com/news/sky-link-opens-a-new-office', $this->tag($html, '#<link rel="canonical" href="([^"]*)"#'));
        $this->assertSame('article', $this->tag($html, '#property="og:type" content="([^"]*)"#'));
        $this->assertSame(1, substr_count($html, 'rel="canonical"'), 'one canonical');
        $graph = $this->jsonLd($html);
        $article = collect($graph)->firstWhere('@type', 'NewsArticle');
        $this->assertSame('Sky Link opens a new office', $article['headline']);
        $this->assertSame('Sky Link Technologies', $article['publisher']['name']);
        $crumb = collect($graph)->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(['Home', 'News', 'Sky Link opens a new office'], array_column($crumb['itemListElement'], 'name'));
    }

    public function test_title_and_description_cleaning(): void
    {
        $long = str_repeat('word ', 60);
        $this->assertLessThanOrEqual(155, mb_strlen(Seo::cleanDescription($long)));
        $this->assertSame('Fix | Sky Link Technologies', Seo::cleanTitle('Fix - Sky Link', 'Sky Link Technologies'));
        $this->assertLessThanOrEqual(60, mb_strlen(Seo::cleanTitle(str_repeat('a', 90), 'Sky Link Technologies', false, true)));
        $this->assertSame('Sky Link Technologies', Seo::cleanTitle('  ', 'Sky Link Technologies'));
    }

    public function test_unknown_addresses_return_a_real_404_with_the_friendly_page(): void
    {
        $r = $this->get('/this-page-does-not-exist');
        $r->assertStatus(404);
        $html = $r->getContent();
        $this->assertStringContainsString('We could not find that page', $html);
        $this->assertStringContainsString('noindex', $html);
        $this->assertStringNotContainsString('rel="canonical"', $html);
        $this->get('/news/no-such-article')->assertStatus(404);
    }

    public function test_duplicate_home_address_redirects_permanently(): void
    {
        $this->get('/home')->assertStatus(301)->assertRedirect('/');
    }

    public function test_sitemap_lists_real_lastmod_dates_and_no_private_pages(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        $this->makeNews('Listed', 'listed-article', 'Listed in the sitemap.');
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        foreach (self::PAGES as $p) {
            $this->assertStringContainsString('<loc>https://skylinkict.com'.($p === '/' ? '/' : $p).'</loc>', $xml);
        }
        $this->assertStringContainsString('<loc>https://skylinkict.com/news/listed-article</loc>', $xml);
        $this->assertMatchesRegularExpression('#<loc>https://skylinkict.com/news/listed-article</loc><lastmod>\d{4}-\d{2}-\d{2}</lastmod>#', $xml);
        $this->assertDoesNotMatchRegularExpression('#/admin|/gallery|/login#', $xml);
    }

    public function test_verification_tags_only_appear_when_configured_and_indexnow_key_is_served(): void
    {
        $this->assertStringNotContainsString('msvalidate.01', $this->get('/')->getContent());
        config(['seo.bing_verification' => 'BINGCODE123', 'seo.google_verification' => 'GOOGLECODE456']);
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('<meta name="msvalidate.01" content="BINGCODE123">', $html);
        $this->assertStringContainsString('<meta name="google-site-verification" content="GOOGLECODE456">', $html);

        $this->get('/abcdef1234567890.txt')->assertStatus(404);
        config(['seo.indexnow_key' => 'abcdef1234567890']);
        $this->get('/abcdef1234567890.txt')->assertOk()->assertSee('abcdef1234567890', false);
        $this->get('/zzzzzzzz1234567890.txt')->assertStatus(404);
    }

    public function test_seo_ping_is_a_dry_run_and_needs_a_key(): void
    {
        $this->artisan('seo:ping')->assertFailed();
        config(['seo.indexnow_key' => 'abcdef1234567890']);
        \Illuminate\Support\Facades\Http::fake();
        $this->artisan('seo:ping')->assertSuccessful();
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
