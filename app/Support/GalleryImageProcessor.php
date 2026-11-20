<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Gallery photos: validates the real file, archives the original privately, and writes two WebP copies to the
 * public disk (thumbnail ~480 px and display ~1600 px wide, never upscaled, camera rotation applied).
 */
final class GalleryImageProcessor
{
    public const DIR = 'gallery';
    public const MAX_KB = 8192;
    public const MIN_PX = 300;
    public const MAX_SIDE = 7000;
    public const MAX_PIXELS = 36_000_000;
    public const THUMB = 480;
    public const DISPLAY = 1600;
    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return array{thumb_path:string,display_path:string,thumb_w:int,thumb_h:int,display_w:int,display_h:int,original_path:string} */
    public static function process(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (preg_match('/\.(php\d*|phtml|phar|pl|py|cgi|sh|asp|aspx|jsp|exe|html?|svg|js)(\.|$)/i', $file->getClientOriginalName())) {
            throw new RuntimeException('This file name is not allowed.');
        }
        if ($file->getSize() > self::MAX_KB * 1024) {
            throw new RuntimeException('The photo is larger than 8 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $info = @getimagesize($path);
        if (! isset(self::MIMES[$mime]) || $info === false || ! isset(self::MIMES[$info['mime']])) {
            throw new RuntimeException('Only JPG, PNG or WebP photos are allowed.');
        }
        [$w, $h] = $info;
        if (min($w, $h) < self::MIN_PX || max($w, $h) > self::MAX_SIDE || $w * $h > self::MAX_PIXELS) {
            throw new RuntimeException('The photo must be at least '.self::MIN_PX.' px on each side and at most '.self::MAX_SIDE.' px / 36 megapixels.');
        }
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            throw new RuntimeException('The server cannot resize images (GD with WebP is missing).');
        }
        @ini_set('memory_limit', '512M'); // a 36 MP photo needs about 150 MB while it is resized

        $src = match ($mime) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromjpeg($path),
        };
        if (! $src) {
            throw new RuntimeException('The photo could not be read.');
        }
        $src = self::orient($src, $path, $mime);

        $name = Str::random(40);
        $original = 'archive/gallery/'.$name.'.'.self::MIMES[$mime];
        Storage::disk('local')->put($original, (string) file_get_contents($path));

        UploadLock::ensure(self::DIR);
        $out = ['original_path' => $original];
        foreach (['thumb' => self::THUMB, 'display' => self::DISPLAY] as $kind => $target) {
            [$rel, $tw, $th] = self::write($src, $name.'-'.$kind, $target);
            $out[$kind.'_path'] = $rel;
            $out[$kind.'_w'] = $tw;
            $out[$kind.'_h'] = $th;
        }
        imagedestroy($src);

        return $out;
    }

    /** @return array{0:string,1:int,2:int} */
    private static function write($src, string $name, int $target): array
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $tw = min($target, $w);
        $th = max(1, (int) round($h * $tw / $w));
        $dst = imagecreatetruecolor($tw, $th);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        $rel = self::DIR.'/'.$name.'.webp';
        ob_start();
        imagewebp($dst, null, 80);
        Storage::disk('public')->put($rel, ob_get_clean());
        imagedestroy($dst);

        return [$rel, $tw, $th];
    }

    /** Apply the camera's rotation (EXIF) so portraits are not sideways. */
    private static function orient($img, string $path, string $mime)
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $img;
        }
        $o = @exif_read_data($path)['Orientation'] ?? 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle) {
            $rot = imagerotate($img, $angle, 0);
            if ($rot) {
                imagedestroy($img);

                return $rot;
            }
        }

        return $img;
    }
}
