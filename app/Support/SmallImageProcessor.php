<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Small admin images (testimonial photos, department pictures): checks the real file (MIME + decoder, not the name),
 * archives the original privately, and writes ONE resized WebP (centre-cropped to $w x $h, never upscaled) to the
 * public disk under a random name. A script-blocking .htaccess is placed in the folder.
 */
final class SmallImageProcessor
{
    public const MAX_KB = 4096;
    public const MIN_PX = 80;
    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return array{path:string,original:string} */
    public static function process(UploadedFile $file, string $dir, int $w, int $h): array
    {
        if (preg_match('/\.(php\d*|phtml|phar|pl|py|cgi|sh|asp|aspx|jsp|exe|html?|svg|js)(\.|$)/i', $file->getClientOriginalName())) {
            throw new RuntimeException('This file name is not allowed.');
        }
        if ($file->getSize() > self::MAX_KB * 1024) {
            throw new RuntimeException('The image is larger than 4 MB.');
        }
        $path = $file->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $info = @getimagesize($path);
        if (! isset(self::MIMES[$mime]) || $info === false || ! isset(self::MIMES[$info['mime']])) {
            throw new RuntimeException('Only JPG, PNG or WebP images are allowed.');
        }
        [$sw, $sh] = $info;
        if (min($sw, $sh) < self::MIN_PX || $sw * $sh > 30_000_000) {
            throw new RuntimeException('The image must be at least '.self::MIN_PX.' px on each side and under 30 megapixels.');
        }
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            throw new RuntimeException('The server cannot resize images (GD with WebP is missing).');
        }
        @ini_set('memory_limit', '512M');
        $src = match ($mime) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromjpeg($path),
        };
        if (! $src) {
            throw new RuntimeException('The image could not be read.');
        }
        $name = Str::random(40);
        $original = 'archive/'.$dir.'/'.$name.'.'.self::MIMES[$mime];
        Storage::disk('local')->put($original, (string) file_get_contents($path));

        // centre crop to the target ratio, then resize (never larger than the source allows)
        $ratio = $w / $h;
        $cw = (int) min($sw, round($sh * $ratio));
        $ch = (int) min($sh, round($sw / $ratio));
        $cx = (int) (($sw - $cw) / 2);
        $cy = (int) (($sh - $ch) / 2);
        $tw = min($w, $cw);
        $th = (int) max(1, round($tw / $ratio));
        $dst = imagecreatetruecolor($tw, $th);
        imagecopyresampled($dst, $src, 0, 0, $cx, $cy, $tw, $th, $cw, $ch);
        imagedestroy($src);
        UploadLock::ensure($dir);
        $rel = $dir.'/'.$name.'.webp';
        ob_start();
        imagewebp($dst, null, 82);
        Storage::disk('public')->put($rel, ob_get_clean());
        imagedestroy($dst);

        return ['path' => $rel, 'original' => $original];
    }
}
