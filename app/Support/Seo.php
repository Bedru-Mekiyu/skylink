<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Head data for search engines and share previews, built in one place from config/seo.php.
 *
 * A page that has its own data (a news article, a service ...) calls Seo::set([...]) in its view before the layout
 * prints the head: title, description, image, type, lang, published, modified, breadcrumb, noindex.
 * Static pages need nothing: their title and description come from config('seo.pages.<route name>').
 */
class Seo
{
    public const TITLE_MAX = 60;
    public const DESCRIPTION_MAX = 155;

    /** @var array<string, mixed> */
    private static array $page = [];

    public static function set(array $data): void
    {
        self::$page = array_merge(self::$page, $data);
    }

    /** Language code for <html lang>: set by the page (an Amharic article) or English. */
    public static function lang(): string
    {
        return (string) (self::$page['lang'] ?? 'en');
    }

    public static function flush(): void
    {
        self::$page = [];
    }

    /**
     * @param  string  $sectionTitle  value of @section('title') (may be empty)
     * @param  string  $sectionDescription  value of @section('description') (may be empty)
     */
    public static function resolve(?string $routeName, string $path, string $sectionTitle = '', string $sectionDescription = '', int $status = 200): array
    {
        $page = self::$page;
        self::flush();
        $cfg = $routeName ? config('seo.pages.'.$routeName) : null;
        if ($status === 404) {
            $cfg = config('seo.not_found');
        }
        $brand = (string) config('seo.brand');

        $exact = isset($cfg['title']);
        $title = $cfg['title'] ?? ($page['title'] ?? $sectionTitle);
        $description = $cfg['description'] ?? ($page['description'] ?? $sectionDescription);
        $title = self::cleanTitle((string) $title, $brand, $exact, (bool) ($page['title_is_headline'] ?? false));
        $description = self::cleanDescription((string) $description) ?: (string) config('seo.default_description');

        $base = rtrim((string) config('app.url'), '/');
        $url = $base.($path === '' ? '/' : $path);
        $image = $page['image'] ?? null;
        $hasOwnImage = (bool) $image;
        $image = $image ?: asset((string) config('seo.default_image'));

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $url,
            'type' => $page['type'] ?? 'website',
            'lang' => $page['lang'] ?? 'en',
            'locale' => ($page['lang'] ?? 'en') === 'am' ? 'am_ET' : 'en_US',
            'image' => $image,
            'image_is_default' => ! $hasOwnImage,
            'image_type' => $page['image_type'] ?? null,
            'published' => $page['published'] ?? null,
            'modified' => $page['modified'] ?? null,
            'noindex' => (bool) ($page['noindex'] ?? ($status === 404)),
            'jsonld' => self::jsonLd($routeName, $url, $title, $description, $image, $page, $base, $status),
            'bing' => config('seo.bing_verification'),
            'google' => config('seo.google_verification'),
        ];
    }

    /**
     * Title of at most 60 characters. Page titles get " | Sky Link Technologies" when it fits; an article headline is used
     * on its own (cut at 60 only when it is longer).
     */
    public static function cleanTitle(string $title, string $brand, bool $exact = false, bool $headline = false): string
    {
        $title = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($title), ENT_QUOTES)));
        if ($title === '') {
            return $brand;
        }
        if ($exact) {
            return $title;
        }
        if (! $headline) {
            $title = trim(preg_replace('/\s*[|\-–]\s*Sky ?Link( News| Technologies)?\s*$/iu', '', $title));
            if (! str_contains($title, $brand) && mb_strlen($title.' | '.$brand) <= self::TITLE_MAX) {
                $title .= ' | '.$brand;
            }
        }

        return mb_strlen($title) > self::TITLE_MAX ? rtrim(mb_substr($title, 0, self::TITLE_MAX - 1)).'…' : $title;
    }

    /** One plain line of at most 155 characters, cut at a word end. */
    public static function cleanDescription(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES)));
        if (mb_strlen($text) <= self::DESCRIPTION_MAX) {
            return $text;
        }
        $cut = mb_substr($text, 0, self::DESCRIPTION_MAX - 1);
        $word = Str::beforeLast($cut, ' ');

        return rtrim($word !== '' && $word !== $cut ? $word : $cut, ' ,.;:-').'…';
    }

    private static function jsonLd(?string $route, string $url, string $title, string $description, string $image, array $page, string $base, int $status): array
    {
        if ($status === 404) {
            return [];
        }
        $org = config('seo.organization');
        $logo = asset($org['logo']);
        $phones = array_map(fn ($p) => PhoneNumber::e164($p), (array) config('company_contact.phones'));
        $email = config('company.email');
        $nodes = [];

        if ($route === 'home') {
            $nodes[] = array_filter([
                '@type' => 'Organization', '@id' => $base.'/#organization', 'name' => $org['name'], 'url' => $base.'/',
                'logo' => $logo, 'email' => $email, 'sameAs' => $org['same_as'],
            ]);
            $local = [
                '@type' => 'LocalBusiness', '@id' => $base.'/#localbusiness', 'name' => $org['name'], 'url' => $base.'/',
                'image' => $logo, 'logo' => $logo, 'email' => $email, 'telephone' => $phones[0] ?? null,
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => $org['street'], 'addressLocality' => $org['city'], 'addressCountry' => $org['country']],
                'sameAs' => $org['same_as'], 'parentOrganization' => ['@id' => $base.'/#organization'],
            ];
            if (! empty($org['opening_hours'])) {
                $local['openingHours'] = array_values($org['opening_hours']);
            }
            $nodes[] = array_filter($local);
        }

        if (($page['type'] ?? null) === 'article') {
            $nodes[] = array_filter([
                '@type' => 'NewsArticle', 'headline' => mb_substr((string) ($page['title'] ?? $title), 0, 110), 'description' => $description,
                'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url], 'image' => ($page['image'] ?? null) ? [$page['image']] : null,
                'datePublished' => $page['published'] ?? null, 'dateModified' => $page['modified'] ?? ($page['published'] ?? null),
                'inLanguage' => $page['lang'] ?? 'en',
                'author' => ['@type' => 'Organization', 'name' => $org['name'], 'url' => $base.'/'],
                'publisher' => ['@type' => 'Organization', 'name' => $org['name'], 'logo' => ['@type' => 'ImageObject', 'url' => $logo]],
            ]);
        }

        $crumbs = $page['breadcrumb'] ?? null;
        if ($crumbs === null && $route && $route !== 'home') {
            $crumbs = [[$title, $url]];
        }
        if ($crumbs) {
            $items = [['Home', $base.'/'], ...$crumbs];
            $nodes[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => preg_replace('/\s*\|\s*Sky Link Technologies$/u', '', $c[0]), 'item' => $c[1]], $items, array_keys($items)),
            ];
        }

        return $nodes ? ['@context' => 'https://schema.org', '@graph' => $nodes] : [];
    }
}
