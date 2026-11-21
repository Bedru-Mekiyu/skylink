<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** One canonical address: canonical tags, share URL, sitemap and robots all use APP_URL. */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_have_a_canonical_link_on_the_configured_host_even_when_opened_on_another_host(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        $html = $this->get('https://s.skylinkict.com/about?x=1')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="https://skylinkict.com/about">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="https://skylinkict.com/about">', $html);
    }

    public function test_the_sitemap_lists_public_pages_and_published_news_on_the_canonical_host(): void
    {
        config(['app.url' => 'https://skylinkict.com']);
        $r = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $r->headers->get('Content-Type'));
        $xml = $r->getContent();
        foreach (['/', '/about', '/services', '/products', '/portfolio', '/news', '/contact'] as $p) {
            $this->assertStringContainsString('<loc>https://skylinkict.com'.$p.'</loc>', $xml);
        }
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'valid XML');
    }

    public function test_robots_txt_allows_crawling_hides_only_the_admin_and_names_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('User-agent: *', $robots);
        $this->assertDoesNotMatchRegularExpression('/^Disallow:\s*\/\s*$/m', $robots, 'not disallow-all');
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap: https://skylinkict.com/sitemap.xml', $robots);
    }
}
