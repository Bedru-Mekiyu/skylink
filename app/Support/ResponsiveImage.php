<?php

namespace App\Support;

/**
 * Finds the resized WebP copies of an image under public/assets/images/_opt
 * (made by tools/local/optimize-images.php) so views can offer phones a small
 * file. If no copies exist (for example a file not optimized yet), callers get
 * null and simply keep the plain <img src>.
 *
 * Paths are relative to public/, e.g. "assets/images/services/cabling.jpg".
 */
final class ResponsiveImage
{
    /** @var array<string, array<int, string>> */
    private static array $variants = [];

    /** @var array<string, array{0:int,1:int}|null> */
    private static array $sizes = [];

    /**
     * "url 480w, url 960w, ..." for the WebP copies, or null when there are none.
     */
    public static function webpSrcset(string $asset): ?string
    {
        $list = self::variants($asset);
        if ($list === []) {
            return null;
        }

        return implode(', ', array_map(
            // Spaces in file names must be encoded: a space separates URL and width in srcset.
            fn (int $w, string $rel) => str_replace(' ', '%20', asset($rel)).' '.$w.'w',
            array_keys($list),
            $list,
        ));
    }

    /**
     * URL of the WebP copy closest to (and at least) the given width, or null.
     */
    public static function webpUrl(string $asset, int $minWidth): ?string
    {
        foreach (self::variants($asset) as $w => $rel) {
            if ($w >= $minWidth) {
                return str_replace(' ', '%20', asset($rel));
            }
        }
        $list = self::variants($asset);

        return $list === [] ? null : str_replace(' ', '%20', asset(end($list)));
    }

    /**
     * [width, height] of the public file, used for width/height attributes
     * so the browser reserves the space before the image arrives.
     *
     * @return array{0:int,1:int}|null
     */
    public static function size(string $asset): ?array
    {
        if (! array_key_exists($asset, self::$sizes)) {
            $file = public_path($asset);
            $info = is_file($file) ? @getimagesize($file) : false;
            self::$sizes[$asset] = $info ? [(int) $info[0], (int) $info[1]] : null;
        }

        return self::$sizes[$asset];
    }

    /**
     * @return array<int, string> width => path relative to public/, smallest first
     */
    private static function variants(string $asset): array
    {
        if (! isset(self::$variants[$asset])) {
            $rel = ltrim(str_replace('\\', '/', $asset), '/');
            $found = [];
            if (str_starts_with($rel, 'assets/images/')) {
                $base = preg_replace('/\.[A-Za-z0-9]+$/', '', substr($rel, strlen('assets/images/')));
                $files = glob(public_path('assets/images/_opt/'.self::globEscape($base).'-*.webp')) ?: [];
                foreach ($files as $file) {
                    if (preg_match('/-(\d+)\.webp$/', $file, $m)) {
                        $found[(int) $m[1]] = 'assets/images/_opt/'.$base.'-'.$m[1].'.webp';
                    }
                }
                ksort($found);
            }
            self::$variants[$asset] = $found;
        }

        return self::$variants[$asset];
    }

    private static function globEscape(string $s): string
    {
        return preg_replace('/([*?\[\]])/', '[$1]', $s);
    }
}
