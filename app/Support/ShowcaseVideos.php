<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;

/**
 * Portfolio showcase clips.
 *
 * Library: clips placed in public/assets/videos/portfolio/ as
 *   <name>.mp4, <name>.webm (optional) and <name>-poster.jpg (optional).
 * The admin picks one by name; large files are uploaded there with the
 * cPanel File Manager instead of through the browser form.
 *
 * Upload: one MP4 or WebM file, checked three ways (extension, detected MIME
 * type, and the file's own signature bytes), max MAX_KILOBYTES.
 */
class ShowcaseVideos
{
    public const DIR = 'assets/videos/portfolio';
    public const MAX_KILOBYTES = 20480; // 20 MB (the host's upload_max_filesize must allow it)

    /** @return array<string, array{mp4: ?string, webm: ?string, poster: ?string}> name => paths */
    public static function library(): array
    {
        $dir = public_path(self::DIR);
        if (! is_dir($dir)) {
            return [];
        }
        $clips = [];
        foreach (glob($dir.'/*.{mp4,webm}', GLOB_BRACE) ?: [] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (! preg_match('/^[a-z0-9][a-z0-9_-]{0,60}$/', $name)) {
                continue;
            }
            $clips[$name] ??= ['mp4' => null, 'webm' => null, 'poster' => null];
            $clips[$name][pathinfo($file, PATHINFO_EXTENSION)] = self::DIR.'/'.basename($file);
        }
        foreach ($clips as $name => &$clip) {
            if (is_file($dir.'/'.$name.'-poster.jpg')) {
                $clip['poster'] = self::DIR.'/'.$name.'-poster.jpg';
            }
        }
        ksort($clips);

        return $clips;
    }

    /** Validation rules for the uploaded clip. */
    public static function uploadRules(): array
    {
        return [
            'nullable',
            'file',
            'max:'.self::MAX_KILOBYTES,
            'mimetypes:video/mp4,video/webm',
            'extensions:mp4,webm',
            function (string $attribute, mixed $value, Closure $fail) {
                if ($value instanceof UploadedFile && self::signatureOf($value) === null) {
                    $fail('The video must be a real MP4 or WebM file.');
                }
            },
        ];
    }

    /** "mp4", "webm" or null, from the file's first bytes (not its name). */
    public static function signatureOf(UploadedFile $file): ?string
    {
        $head = (string) @file_get_contents($file->getRealPath(), false, null, 0, 16);
        if (strlen($head) >= 12 && substr($head, 4, 4) === 'ftyp') {
            return 'mp4';
        }
        if (strncmp($head, "\x1A\x45\xDF\xA3", 4) === 0) {
            return 'webm';
        }

        return null;
    }
}
