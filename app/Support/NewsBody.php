<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Article body formatting: the allowlist of HTML the admin editor may save
 * (paragraphs, bold, italic, links, lists, headings), and the conversion of
 * older plain-text bodies to paragraphs.
 */
class NewsBody
{
    /** Longest body accepted, in characters (also the sanitiser's input limit). */
    public const MAX_LENGTH = 100_000;

    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(string $html): string
    {
        $clean = self::sanitizer()->sanitize($html);
        // Editors leave empty paragraphs behind (e.g. around lists).
        $clean = preg_replace('#<p>(?:\s|&nbsp;|&\#160;|<br\s*/?>)*</p>#u', '', $clean);

        return trim($clean);
    }

    /** Plain text, for the excerpt, search snippets and duplicate checks. */
    public static function toText(string $html): string
    {
        $html = preg_replace('#<(br|/p|/h[2-4]|/li|/blockquote)\s*/?>#i', "$0\n", $html);

        return trim(preg_replace("/[ \t]+/u", ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** An automatic excerpt (about 200 characters) from the body. */
    public static function excerpt(string $html, int $limit = 200): string
    {
        return Str::limit(preg_replace('/\s+/u', ' ', self::toText($html)), $limit);
    }

    /** Older plain-text bodies: blank lines separate paragraphs, single line breaks are kept. */
    public static function fromPlainText(string $text): string
    {
        $paragraphs = array_filter(array_map('trim', preg_split("/\n\s*\n/u", $text)));

        return implode("\n", array_map(fn ($p) => '<p>'.nl2br(e($p), false).'</p>', $paragraphs));
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer === null) {
            $config = (new HtmlSanitizerConfig())
                // Unknown tags (e.g. <section>, <u> from a paste) are removed but their
                // text is kept; active or embedded content is dropped with its contents.
                ->defaultAction(HtmlSanitizerAction::Block)
                ->dropElement('script')
                ->dropElement('style')
                ->dropElement('template')
                ->dropElement('noscript')
                ->dropElement('iframe')
                ->dropElement('object')
                ->dropElement('embed')
                ->dropElement('svg')
                ->dropElement('math')
                ->dropElement('form')
                ->dropElement('textarea')
                ->dropElement('select')
                ->dropElement('button')
                ->dropElement('title')
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('h4')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('blockquote')
                ->allowElement('a', ['href', 'title'])
                // Wrappers browsers add while editing: drop the tag, keep the text.
                ->blockElement('div')
                ->blockElement('span')
                ->blockElement('font')
                ->blockElement('h1') // the article title is the only h1 on the page
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                // The sanitiser counts bytes (and cuts longer input); validation counts
                // characters. Ethiopic letters are 3 bytes in UTF-8, so allow 4 bytes each.
                ->withMaxInputLength(self::MAX_LENGTH * 4);

            self::$sanitizer = new HtmlSanitizer($config);
        }

        return self::$sanitizer;
    }
}
