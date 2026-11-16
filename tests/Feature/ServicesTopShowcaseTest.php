<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The technology showcase at the top of /services: exactly the five named
 * logos, served from small local files, with nothing loaded from outside.
 */
class ServicesTopShowcaseTest extends TestCase
{
    use RefreshDatabase;

    private const LOGOS = [
        'Laravel' => 'laravel.svg',
        'Angular' => 'angular.svg',
        'Node.js' => 'nodedotjs.svg',
        'Vue.js' => 'vuedotjs.svg',
        '.NET' => 'dotnet.svg',
    ];

    private function heroHtml(): string
    {
        $html = $this->get('/services')->assertOk()->getContent();
        $start = strpos($html, 'class="hero-section"');
        $end = strpos($html, 'id="services"');
        $this->assertNotFalse($start, 'hero section missing');
        $this->assertNotFalse($end, '#services section missing');

        return substr($html, $start, $end - $start);
    }

    public function test_showcase_has_exactly_the_five_named_local_logos(): void
    {
        $hero = $this->heroHtml();

        preg_match_all('/<img\b[^>]*>/i', $hero, $imgs);
        $this->assertCount(5, $imgs[0], 'expected exactly five logo images');

        foreach (self::LOGOS as $name => $file) {
            $this->assertStringContainsString('assets/images/services-top/'.$file, $hero, "$name logo not used");
            $this->assertMatchesRegularExpression('/aria-label="'.preg_quote($name, '/').'"|alt="'.preg_quote($name, '/').'"/', $hero, "$name has no accessible name");

            $path = public_path('assets/images/services-top/'.$file);
            $this->assertFileExists($path);
            $this->assertLessThan(4096, filesize($path), "$file should be a few KB at most");
        }
        $this->assertFileExists(public_path('assets/images/services-top/LICENSES.md'));
    }

    public function test_showcase_loads_nothing_from_outside_the_site(): void
    {
        $hero = $this->heroHtml();
        $ownHost = parse_url(url('/'), PHP_URL_HOST);

        preg_match_all('/(?:src|srcset)\s*=\s*["\']([^"\']+)|url\(\s*["\']?([^"\')]+)/i', $hero, $m);
        $urls = array_filter(array_merge($m[1], $m[2]));
        $this->assertNotEmpty($urls);

        foreach ($urls as $u) {
            $host = parse_url(trim(explode(' ', trim($u))[0]), PHP_URL_HOST);
            $this->assertTrue($host === null || $host === $ownHost, "outside request in the showcase: $u");
        }
    }
}
