<?php

namespace App\Support;

/**
 * Validation rules shared by every admin image upload (news, video
 * thumbnails, portfolio, services).
 *
 * - Only JPG/JPEG, PNG and WebP. Three independent checks must all pass:
 *   `mimes` (extension guessed from the file's real content),
 *   `mimetypes` (the detected MIME type) and `extensions` (the extension
 *   the client sent). So "shell.php", "page.html" or "notes.txt" renamed
 *   to .jpg, or a real image renamed to .php, are all rejected.
 * - `image` also rejects SVG (scriptable).
 * - Max 5 MB.
 *
 * Files are then stored with UploadedFile::store(), which uses a random
 * 40-character name and an extension derived from the detected MIME type,
 * never the client's file name.
 */
final class ImageUploadRules
{
    public const MAX_KILOBYTES = 5120;

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return array<int, string>
     */
    public static function rules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:'.implode(',', self::EXTENSIONS),
            'mimetypes:'.implode(',', self::MIME_TYPES),
            'extensions:'.implode(',', self::EXTENSIONS),
            'max:'.self::MAX_KILOBYTES,
        ];
    }
}
