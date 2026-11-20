<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroVideoSetting;
use App\Support\HeroVideo;
use App\Support\HeroVideoProcessor;
use App\Support\PublicCache;
use App\Support\UploadLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Admin > Homepage Video: upload, preview, replace, restore previous, reset to the built-in default. */
class HeroVideoController extends Controller
{
    /** Per field: [allowed extensions, allowed MIME types, max KB, recommended max bytes (null = none)]. */
    private const FIELDS = [
        'desktop_mp4' => [['mp4'], ['video/mp4'], 61440, 5 * 1024 * 1024],
        'desktop_webm' => [['webm'], ['video/webm'], 61440, 5 * 1024 * 1024],
        'mobile_mp4' => [['mp4'], ['video/mp4'], 20480, 1536 * 1024],
        'poster' => [['jpg', 'jpeg', 'png', 'webp'], ['image/jpeg', 'image/png', 'image/webp'], 5120, null],
    ];

    private const VIDEO_KEYS = ['desktop_mp4', 'desktop_webm', 'mobile_mp4'];

    /** A script or markup extension anywhere in the name ("evil.php.mp4", "x.svg") is refused outright. */
    private const DANGEROUS = '/\.(php\d*|phtml|phar|pl|py|cgi|sh|asp|aspx|jsp|exe|html?|svg|js)(\.|$)/i';

    public function edit()
    {
        $row = HeroVideo::row();
        $disk = Storage::disk('public');
        $current = collect(HeroVideo::files($row?->current))
            ->map(fn ($p) => asset('storage/'.$p).'?v='.$disk->lastModified($p))->all();

        return view('admin.hero-video.edit', [
            'current' => $current,
            'hasCustom' => (bool) array_intersect_key(HeroVideo::files($row?->current), array_flip(self::VIDEO_KEYS)),
            'hasPrevious' => (bool) HeroVideo::files($row?->previous),
            'limitBytes' => self::serverLimitBytes(),
            'limitLabel' => self::mb(self::serverLimitBytes()),
            'ffmpeg' => HeroVideoProcessor::binary() !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $rules = [];
        foreach (self::FIELDS as $key => [$exts, $mimes, $kb]) {
            $rules[$key] = ['nullable', 'file', 'extensions:'.implode(',', $exts), 'mimetypes:'.implode(',', $mimes), 'max:'.$this->maxKb($kb)];
        }
        $limit = self::mb(self::serverLimitBytes());
        $wrongType = 'Wrong file type. Allowed: MP4 / WebM video, JPG / PNG / WebP image.';
        $request->validate($rules, [
            'uploaded' => "The file could not be uploaded. The server accepts files up to {$limit} MB.",
            'max' => 'The file is too large for this server (limit :max KB).',
            'extensions' => $wrongType,
            'mimetypes' => $wrongType,
        ]);

        /** @var array<string,UploadedFile> $in */
        $in = [];
        foreach (array_keys(self::FIELDS) as $key) {
            if ($request->hasFile($key)) {
                $in[$key] = $request->file($key);
                $this->assertGenuine($key, $in[$key]);
            }
        }
        if (! $in) {
            throw ValidationException::withMessages(['desktop_mp4' => 'Choose at least one file to upload.']);
        }

        $row = HeroVideo::row() ?? new HeroVideoSetting();
        $old = HeroVideo::files($row->current);
        $hadVideo = (bool) array_intersect_key($old, array_flip(self::VIDEO_KEYS));
        if (! $hadVideo && ! isset($in['desktop_mp4'])) {
            throw ValidationException::withMessages(['desktop_mp4' => 'Start with the desktop MP4 video: the other files only add to it.']);
        }
        $ffmpeg = HeroVideoProcessor::binary() !== null;
        if (isset($in['desktop_mp4']) && ! isset($in['poster']) && ! $ffmpeg) {
            throw ValidationException::withMessages(['poster' => 'A poster image is required with a new desktop video (this server cannot make one).']);
        }

        // Stored under random names; the original file name is never used.
        UploadLock::ensure(HeroVideo::DIR);
        $new = $old;
        foreach ($in as $key => $file) {
            $ext = $key === 'poster' ? $this->imageExt($file) : self::FIELDS[$key][0][0];
            $new[$key] = $file->storeAs(HeroVideo::DIR, Str::random(40).'.'.$ext, 'public');
        }
        if (isset($in['desktop_mp4'])) {
            // A new desktop video makes the old WebM, mobile cut and poster stale unless re-uploaded.
            foreach (['desktop_webm', 'mobile_mp4', 'poster'] as $k) {
                if (! isset($in[$k])) {
                    unset($new[$k]);
                }
            }
        }
        if ($ffmpeg) {
            $new = $this->optimise($new, $in);
        }

        $warnings = [];
        $labels = ['desktop_mp4' => 'desktop video', 'desktop_webm' => 'desktop WebM', 'mobile_mp4' => 'mobile video'];
        foreach ($labels as $k => $label) {
            $max = self::FIELDS[$k][3];
            if (isset($in[$k], $new[$k]) && Storage::disk('public')->size($new[$k]) > $max) {
                $warnings[] = "The {$label} is ".self::mb(Storage::disk('public')->size($new[$k])).' MB, above the recommended '.self::mb($max).' MB. It may load slowly on phones.';
            }
        }

        $oldRaw = [$row->current, $row->previous];
        $row->fill(['previous' => $hadVideo ? $old : $row->previous, 'current' => $new, 'updated_by' => $request->user()->id])->save();
        $this->finish($oldRaw, $row, 'uploaded', array_keys($in), $request->user()->id);

        return $this->respond($request, 'The homepage video was updated. Visitors see it now.', $warnings);
    }

    public function reset(Request $request): RedirectResponse|JsonResponse
    {
        $row = HeroVideo::row();
        if (! $row || ! HeroVideo::files($row->current)) {
            return $this->respond($request, 'The homepage already uses the built-in video.');
        }
        $oldRaw = [$row->current, $row->previous];
        $row->fill(['previous' => $row->current, 'current' => null, 'updated_by' => $request->user()->id])->save();
        $this->finish($oldRaw, $row, 'reset', [], $request->user()->id);

        return $this->respond($request, 'Back to the built-in video. Your last custom video is kept, so you can restore it.');
    }

    public function restore(Request $request): RedirectResponse|JsonResponse
    {
        $row = HeroVideo::row();
        if (! $row || ! HeroVideo::files($row->previous)) {
            return $this->respond($request, 'There is no previous video to restore.');
        }
        $oldRaw = [$row->current, $row->previous];
        $row->fill(['current' => $row->previous, 'previous' => $row->current, 'updated_by' => $request->user()->id])->save();
        $this->finish($oldRaw, $row, 'restored', [], $request->user()->id);

        return $this->respond($request, 'The previous video is live again.');
    }

    /** Re-encode the new videos (and make a mobile cut / poster when missing). A failed step keeps the raw upload. */
    private function optimise(array $new, array $in): array
    {
        $disk = Storage::disk('public');
        $mk = fn (string $ext) => HeroVideo::DIR.'/'.Str::random(40).'.'.$ext;

        if (isset($in['desktop_mp4'])) {
            if (HeroVideoProcessor::optimise($new['desktop_mp4'], $dst = $mk('mp4'), 1920, 26)) {
                $disk->delete($new['desktop_mp4']);
                $new['desktop_mp4'] = $dst;
            }
            if (! isset($new['mobile_mp4']) && HeroVideoProcessor::optimise($new['desktop_mp4'], $dst = $mk('mp4'), 720, 30)) {
                $new['mobile_mp4'] = $dst;
            }
            if (! isset($new['poster']) && HeroVideoProcessor::poster($new['desktop_mp4'], $dst = $mk('jpg'))) {
                $new['poster'] = $dst;
            }
        }
        if (isset($in['mobile_mp4']) && HeroVideoProcessor::optimise($new['mobile_mp4'], $dst = $mk('mp4'), 720, 30)) {
            $disk->delete($new['mobile_mp4']);
            $new['mobile_mp4'] = $dst;
        }

        return $new;
    }

    /** Delete files no longer referenced by current/previous, drop caches, write the audit line. */
    private function finish(array $oldRaw, HeroVideoSetting $row, string $action, array $fields, int $userId): void
    {
        $keep = array_merge(array_values((array) $row->current), array_values((array) $row->previous));
        $disk = Storage::disk('public');
        foreach (array_merge(array_values((array) $oldRaw[0]), array_values((array) $oldRaw[1])) as $path) {
            if (is_string($path) && str_starts_with($path, HeroVideo::DIR.'/') && ! in_array($path, $keep, true)) {
                $disk->delete($path);
            }
        }
        PublicCache::bust();
        Log::notice('hero-video '.$action, ['user_id' => $userId, 'fields' => $fields]);
    }

    private function respond(Request $request, string $message, array $warnings = []): RedirectResponse|JsonResponse
    {
        session()->flash('success', $message);
        session()->flash('hero_warnings', $warnings);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $message])
            : redirect()->route('admin.hero-video.edit');
    }

    /** Real content check, not the client's word: magic bytes + finfo MIME + image decode. */
    private function assertGenuine(string $key, UploadedFile $file): void
    {
        $bad = fn (string $m) => throw ValidationException::withMessages([$key => $m]);
        if (preg_match(self::DANGEROUS, $file->getClientOriginalName())) {
            $bad('This file name is not allowed.');
        }
        $path = $file->getRealPath();
        $head = (string) @file_get_contents($path, false, null, 0, 12);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $ok = match ($key) {
            'desktop_mp4', 'mobile_mp4' => substr($head, 4, 4) === 'ftyp' && $mime === 'video/mp4',
            'desktop_webm' => str_starts_with($head, "\x1A\x45\xDF\xA3") && in_array($mime, ['video/webm', 'video/x-matroska'], true),
            default => in_array($mime, self::FIELDS['poster'][1], true) && @getimagesize($path) !== false,
        };
        if (! $ok) {
            $bad('The file content does not match its type. Upload a real MP4 / WebM video or JPG / PNG / WebP image.');
        }
    }

    private function imageExt(UploadedFile $file): string
    {
        return match ((new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath())) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    /** Smaller of PHP's upload_max_filesize and post_max_size, in bytes. */
    public static function serverLimitBytes(): int
    {
        return min(self::bytes((string) ini_get('upload_max_filesize')), self::bytes((string) ini_get('post_max_size')));
    }

    private function maxKb(int $kb): int
    {
        return max(1, min($kb, intdiv(self::serverLimitBytes(), 1024)));
    }

    private static function bytes(string $v): int
    {
        $v = trim($v);
        $n = (int) $v;
        $bytes = match (strtolower(substr($v, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };

        return $bytes > 0 ? $bytes : PHP_INT_MAX; // 0 / -1 = unlimited
    }

    private static function mb(int $bytes): string
    {
        return $bytes >= PHP_INT_MAX / 2 ? 'no limit' : rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.');
    }
}
