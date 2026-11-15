<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns an uploaded (or existing) news image into:
 *   - a web copy, at most 1600 px wide       -> public disk, news/web/<random>.<ext>
 *   - a card thumbnail, 640 px wide           -> public disk, news/thumbs/<random>.<ext>
 *   - the untouched original (uploads only)   -> local (non-public) disk, news-originals/<random>.<ext>
 *
 * Uses PHP's GD extension (no extra package). Output is WebP when GD supports
 * it, otherwise JPEG. File names are random; nothing from the client is used.
 */
class NewsImageProcessor
{
    /**
     * @return array{image: string, image_thumb: string, image_original: string}
     */
    public function storeUpload(UploadedFile $file): array
    {
        $bytes = (string) file_get_contents($file->getRealPath());
        $paths = $this->makeCopies($bytes);

        $ext = strtolower($file->guessExtension() ?: 'jpg');
        $paths['image_original'] = 'news-originals/'.Str::random(40).'.'.$ext;
        Storage::disk('local')->put($paths['image_original'], $bytes);

        return $paths;
    }

    /**
     * @param  list<string>  $kinds  which copies to make:
     *   "web"   -> image        (WebP, max 1600 px wide: article page and featured story)
     *   "thumb" -> image_thumb  (WebP, 640 px wide: cards)
     *   "share" -> image_share  (JPEG, max 1200 px wide: og:image / twitter:image;
     *              JPEG because not every social network shows WebP previews)
     * @return array<string, string> column => path on the public disk
     */
    public function makeCopies(string $bytes, array $kinds = ['web', 'thumb', 'share']): array
    {
        $info = @getimagesizefromstring($bytes);
        if ($info === false) {
            throw new RuntimeException('The file is not a readable image.');
        }
        [$width, $height] = $info;
        if ($width * $height > config('news.images.max_pixels')) {
            throw new RuntimeException('The image is too large in pixels (max 25 megapixels).');
        }

        $this->ensureMemoryFor($width, $height);

        $source = @imagecreatefromstring($bytes);
        if (! $source instanceof GdImage) {
            throw new RuntimeException('The image could not be decoded.');
        }
        $source = $this->applyExifOrientation($source, $bytes, $info[2] ?? null);

        $name = Str::random(40);
        $ext = $this->outputExtension();

        $plan = [
            'web' => ['image', "news/web/{$name}.{$ext}", 'web_max_width', 'web_quality', $ext],
            'thumb' => ['image_thumb', "news/thumbs/{$name}.{$ext}", 'thumb_width', 'thumb_quality', $ext],
            'share' => ['image_share', "news/share/{$name}.jpg", 'share_max_width', 'share_quality', 'jpg'],
        ];

        $paths = [];
        foreach ($kinds as $kind) {
            [$column, $path, $widthKey, $qualityKey, $format] = $plan[$kind];
            $copy = $this->resize($source, (int) config('news.images.'.$widthKey));
            Storage::disk('public')->put($path, $this->encode($copy, (int) config('news.images.'.$qualityKey), $format));
            $paths[$column] = $path;
        }

        return $paths;
    }

    /**
     * GD keeps the whole decoded picture in memory (about 5 bytes per pixel,
     * plus the rotated and resized copies). An out-of-memory error is fatal
     * and cannot be caught, so check first: raise PHP's limit for this request
     * when the host allows it (up to 512 MB), otherwise refuse with a clear message.
     */
    private function ensureMemoryFor(int $width, int $height): void
    {
        $needed = memory_get_usage() + (int) ($width * $height * 5.5) + 32 * 1024 * 1024;
        $limit = $this->bytes((string) ini_get('memory_limit'));
        if ($limit < 0 || $needed <= $limit) {
            return;
        }
        $ceiling = 512 * 1024 * 1024;
        if ($needed <= $ceiling && @ini_set('memory_limit', (string) (int) ceil($needed / 1048576).'M') !== false
            && $this->bytes((string) ini_get('memory_limit')) >= $needed) {
            return;
        }
        throw new RuntimeException('The image is too large for this server to resize ('.round($width * $height / 1e6).' megapixels). Please save it at about 4000 pixels wide or less.');
    }

    private function bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function outputExtension(): string
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }

    /** Scale down to $maxWidth (never up), keeping the aspect ratio. */
    private function resize(GdImage $source, int $maxWidth): GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        if ($w <= $maxWidth) {
            return $source;
        }
        $newH = max(1, (int) round($h * $maxWidth / $w));
        $target = imagecreatetruecolor($maxWidth, $newH);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $maxWidth, $newH, $w, $h);

        return $target;
    }

    private function encode(GdImage $image, int $quality, string $format): string
    {
        ob_start();
        if ($format === 'webp') {
            imagesavealpha($image, true);
            imagewebp($image, null, $quality);
        } else {
            // JPEG has no transparency: flatten onto white.
            $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imageinterlace($flat, true);
            imagejpeg($flat, null, $quality);
        }

        return (string) ob_get_clean();
    }

    /** Phone photos are often stored sideways with an EXIF rotation flag. */
    private function applyExifOrientation(GdImage $image, string $bytes, ?int $type): GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $image;
        }
        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
