<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Partner logos: validates the real file, archives the original privately, and writes small web copies
 * (WebP + PNG/JPEG twin, at most WIDTH px wide, never upscaled, transparency kept) to the public disk.
 */
final class PartnerLogoProcessor
{
    public const DIR = 'partners';
    public const WIDTH = 400;
    public const MAX_KB = 2048;
    public const MIN_PX = 64;
    public const MAX_PX = 4000;
    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return array{logo_path:string,logo_fallback:string,logo_w:int,logo_h:int,original_path:string} */
    public static function process(UploadedFile $file): array
    {
        [$mime, $w, $h] = self::inspect($file->getRealPath(), $file->getClientOriginalName(), $file->getSize());
        $name = Str::random(40);

        // Original: private archive, never served.
        $original = 'archive/partners/'.$name.'.'.self::MIMES[$mime];
        Storage::disk('local')->put($original, (string) file_get_contents($file->getRealPath()));

        return ['original_path' => $original] + self::makeCopies($file->getRealPath(), $mime, $w, $h, $name);
    }

    /** Web copies of any readable image file (also used for the imported static logos). */
    public static function fromPath(string $path): array
    {
        [$mime, $w, $h] = self::inspect($path, basename($path), (int) filesize($path), false);

        return self::makeCopies($path, $mime, $w, $h, Str::random(40));
    }

    /** @return array{0:string,1:int,2:int} mime, width, height; throws on anything that is not a clean image */
    private static function inspect(string $path, string $clientName, int $bytes, bool $strict = true): array
    {
        if ($strict && preg_match('/\.(php\d*|phtml|phar|pl|py|cgi|sh|asp|aspx|jsp|exe|html?|svg|js)(\.|$)/i', $clientName)) {
            throw new RuntimeException('This file name is not allowed.');
        }
        if ($strict && $bytes > self::MAX_KB * 1024) {
            throw new RuntimeException('The logo is larger than 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $info = @getimagesize($path);
        if (! isset(self::MIMES[$mime]) || $info === false || ! in_array($info['mime'], array_keys(self::MIMES), true)) {
            throw new RuntimeException('Only JPG, PNG or WebP images are allowed.');
        }
        [$w, $h] = $info;
        $max = $strict ? self::MAX_PX : 6000; // the command for the old static logos (trusted files) allows the two 4500 px ones
        if ($w < self::MIN_PX || $h < self::MIN_PX || $w > $max || $h > $max) {
            throw new RuntimeException('The logo must be between '.self::MIN_PX.' and '.$max.' px on each side.');
        }

        return [$mime, $w, $h];
    }

    private static function makeCopies(string $path, string $mime, int $w, int $h, string $name): array
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            throw new RuntimeException('The server cannot resize images (GD with WebP is missing).');
        }
        $src = match ($mime) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromjpeg($path),
        };
        if (! $src) {
            throw new RuntimeException('The image could not be read.');
        }
        $tw = min(self::WIDTH, $w);
        $th = max(1, (int) round($h * $tw / $w));
        $dst = imagecreatetruecolor($tw, $th);
        $alpha = $mime !== 'image/jpeg';
        if ($alpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        UploadLock::ensure(self::DIR);
        $disk = Storage::disk('public');
        $webp = self::DIR.'/'.$name.'.webp';
        $fallbackExt = $alpha ? 'png' : 'jpg';
        $fallback = self::DIR.'/'.$name.'.'.$fallbackExt;

        ob_start(); imagewebp($dst, null, 85); $disk->put($webp, ob_get_clean());
        ob_start();
        $alpha ? imagepng($dst, null, 9) : imagejpeg($dst, null, 85);
        $disk->put($fallback, ob_get_clean());
        imagedestroy($src);
        imagedestroy($dst);

        return ['logo_path' => $webp, 'logo_fallback' => $fallback, 'logo_w' => $tw, 'logo_h' => $th];
    }
}
