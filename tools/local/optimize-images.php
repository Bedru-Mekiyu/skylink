<?php
/*
 * Image optimization for files under public/assets/images. LOCAL USE ONLY
 * (run on this computer; upload the results as listed in deploy-checklist.md).
 *
 * For every listed image that is used by the site and is over the threshold:
 *   1. the original is COPIED to the non-public archive
 *        storage/app/private/archive/public-originals/<same path>
 *      and checked (same size and SHA-256) before anything else happens;
 *   2. the public file keeps its name and URL (the database and the views
 *      still point at it) but becomes a resized copy (max 1920 px wide;
 *      JPEG quality 82, PNG stays PNG);
 *   3. WebP copies for phones/tablets/desktops are written next to it under
 *        public/assets/images/_opt/<same path without extension>-<width>.webp
 *      (widths 480, 960, 1440, 1920, never wider than the original).
 * Originals are never deleted. Running it again starts from the archived
 * original, so quality never degrades. Photo rotation (EXIF) is applied.
 *
 *   php tools/local/optimize-images.php            dry run: shows what would happen
 *   php tools/local/optimize-images.php --apply    does it
 *   php tools/local/optimize-images.php --apply --only=services/cabling.jpg
 *
 * Report: footage/_review/images/optimize-report.md (ranked by bytes saved).
 */
ini_set('memory_limit', '2G');
$root = dirname(__DIR__, 2);
$apply = in_array('--apply', $argv, true);
$only = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--only=')) {
        $only = substr($a, 7);
    }
}
$imagesDir = $root.'/public/assets/images';
$archiveDir = $root.'/storage/app/private/archive/public-originals/assets/images';
$optDir = $imagesDir.'/_opt';
$threshold = 200 * 1024;
$widths = [480, 960, 1440, 1920];
$maxFallback = 1920;

// Images the site uses (views, seeders, config, controllers). Files nobody
// references are reported but not touched: local pages never download them.
$refSources = array_merge(
    glob($root.'/resources/views/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE),
    glob($root.'/database/seeders/*.{json,php}', GLOB_BRACE),
    glob($root.'/app/Http/Controllers/*.php'),
    glob($root.'/config/*.php'),
);
$refText = '';
foreach ($refSources as $f) {
    if (! str_contains(str_replace('\\', '/', $f), '/views/admin/')) {
        $refText .= file_get_contents($f)."\n";
    }
}

$rows = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imagesDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    $path = str_replace('\\', '/', $file->getPathname());
    $rel = substr($path, strlen(str_replace('\\', '/', $imagesDir)) + 1);
    if (str_starts_with($rel, '_opt/') || ! preg_match('/\.(jpe?g|png)$/i', $rel)) {
        continue;
    }
    if ($only !== null && $rel !== $only) {
        continue;
    }
    $archived = $archiveDir.'/'.$rel;
    $source = is_file($archived) ? $archived : $path;
    if (filesize($source) < $threshold) {
        continue;
    }
    if (! str_contains($refText, basename($rel))) {
        $rows[] = ['rel' => $rel, 'status' => 'not used by the site: left as is', 'before' => filesize($path), 'after' => filesize($path), 'variants' => []];
        continue;
    }
    $rows[] = process($rel, $path, $source, $archived, $optDir, $widths, $maxFallback, $apply);
}

function load(string $file): GdImage
{
    $info = getimagesize($file);
    $img = match ($info[2]) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($file),
        IMAGETYPE_PNG => imagecreatefrompng($file),
        default => throw new RuntimeException("unsupported type: $file"),
    };
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($file)['Orientation'] ?? 1);
        $img = match ($o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
        if (in_array($o, [2, 4, 5, 7], true)) {
            imageflip($img, IMG_FLIP_HORIZONTAL);
        }
    }

    return $img;
}

function resized(GdImage $src, int $w): GdImage
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    if ($w >= $sw) {
        return $src;
    }
    $h = (int) round($sh * $w / $sw);
    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);

    return $dst;
}

function process(string $rel, string $path, string $source, string $archived, string $optDir, array $widths, int $max, bool $apply): array
{
    $before = filesize($source);
    $img = load($source);
    imagesavealpha($img, true);
    $w = imagesx($img);
    $isPng = (bool) preg_match('/\.png$/i', $rel);
    $plan = array_values(array_unique(array_merge(array_filter($widths, fn ($x) => $x < $w), [min($w, end($widths))])));
    sort($plan);
    $base = preg_replace('/\.(jpe?g|png)$/i', '', $rel);
    $row = ['rel' => $rel, 'status' => $apply ? 'optimized' : 'dry run', 'before' => $before, 'origSize' => imagesx($img).'x'.imagesy($img), 'variants' => []];

    if (! $apply) {
        $row['after'] = null;
        $row['variants'] = array_map(fn ($x) => "_opt/$base-$x.webp", $plan);

        return $row;
    }

    // 1. Archive the original (copy, verify, then the public copy may be replaced).
    if (! is_file($archived)) {
        @mkdir(dirname($archived), 0777, true);
        if (! copy($path, $archived) || filesize($archived) !== filesize($path) || hash_file('sha256', $archived) !== hash_file('sha256', $path)) {
            throw new RuntimeException("archive copy failed for $rel; nothing changed");
        }
    }

    // 2. Public fallback with the same name and format.
    $fb = resized($img, min($w, $max));
    $tmp = $path.'.tmp';
    $ok = $isPng ? imagepng($fb, $tmp, 9) : imagejpeg($fb, $tmp, 82);
    if (! $ok) {
        throw new RuntimeException("could not write $rel");
    }
    // Keep the original bytes if the "optimized" copy would be bigger.
    if (filesize($tmp) < filesize($source)) {
        rename($tmp, $path);
    } else {
        unlink($tmp);
        copy($source, $path);
    }
    $row['after'] = filesize($path);
    $row['fallbackSize'] = imagesx($fb).'x'.imagesy($fb);

    // 3. WebP copies.
    foreach ($plan as $vw) {
        $out = "$optDir/$base-$vw.webp";
        @mkdir(dirname($out), 0777, true);
        imagewebp(resized($img, $vw), $out, 80);
        $row['variants'][] = "_opt/$base-$vw.webp (".round(filesize($out) / 1024).' KB)';
    }
    $row['webpLargest'] = filesize("$optDir/$base-".end($plan).'.webp');

    return $row;
}

// Report, ranked by bytes saved on the public file.
usort($rows, fn ($a, $b) => (($b['before'] - ($b['after'] ?? $b['before'])) <=> ($a['before'] - ($a['after'] ?? $a['before']))));
$kb = fn ($b) => number_format($b / 1024).' KB';
$lines = ['# Image optimization report', '', ($apply ? 'Applied' : 'Dry run').' on '.date('Y-m-d H:i').'. Originals: storage/app/private/archive/public-originals/assets/images/ (never deleted).', '',
    '| # | File (public/assets/images/…) | Original | Public copy now | Saved | Largest WebP | WebP copies | Status |', '|---|---|---|---|---|---|---|---|'];
$total = 0;
foreach ($rows as $i => $r) {
    $saved = $r['after'] === null ? 0 : $r['before'] - $r['after'];
    $total += $saved;
    $lines[] = sprintf('| %d | %s | %s%s | %s%s | %s | %s | %s | %s |', $i + 1, $r['rel'], $kb($r['before']), isset($r['origSize']) ? ' ('.$r['origSize'].')' : '',
        $r['after'] === null ? '–' : $kb($r['after']), isset($r['fallbackSize']) ? ' ('.$r['fallbackSize'].')' : '',
        $saved ? $kb($saved) : '–', isset($r['webpLargest']) ? $kb($r['webpLargest']) : '–', count($r['variants']), $r['status']);
}
$lines[] = '';
$lines[] = 'Total saved on the public copies: '.$kb($total).'.';
@mkdir($root.'/footage/_review/images', 0777, true);
file_put_contents($root.'/footage/_review/images/optimize-report.md', implode("\n", $lines)."\n");
echo implode("\n", $lines), "\n\nReport: footage/_review/images/optimize-report.md\n";
