<?php
// LOCAL USE ONLY. Builds the homepage hero still (the built-in poster) from the CEO portrait.
//   php tools/local/build-ceo-hero.php
// Source: public/assets/images/about/mohammedkerem.JPG (1920x2560, untouched; a private copy goes to
// storage/app/private/archive/ceo/). Output (public/assets/videos/hero/): poster.jpg + poster.webp (1920x1080),
// poster-mobile.jpg + poster-mobile.webp (900x1200). Same deep navy as the hero overlay; he sits on the right,
// the left stays navy for the headline. Re-run to rebuild; budgets: desktop <= 250 KB, mobile <= 150 KB.

$root = dirname(__DIR__, 2);
$src = $root.'/public/assets/images/about/mohammedkerem.JPG';
$out = $root.'/public/assets/videos/hero/';
$archive = $root.'/storage/app/private/archive/ceo/';
if (! is_dir($archive)) {
    mkdir($archive, 0775, true);
}
if (! is_file($archive.'mohammedkerem-original.JPG')) {
    copy($src, $archive.'mohammedkerem-original.JPG');
}

$photo = imagecreatefromjpeg($src);
[$sw, $sh] = [imagesx($photo), imagesy($photo)];
$navy = [6, 18, 43];

function smooth(float $t): float
{
    $t = max(0.0, min(1.0, $t));

    return $t * $t * (3 - 2 * $t);
}

/** Copy a region of $photo into a W x H box on $canvas at ($dx, $dy), blending towards navy by $alpha(x, y). */
function place($canvas, $photo, array $crop, array $box, callable $alpha, array $navy): void
{
    [$cx, $cy, $cw, $ch] = $crop;
    [$dx, $dy, $w, $h] = $box;
    $tmp = imagecreatetruecolor($w, $h);
    imagecopyresampled($tmp, $photo, 0, 0, $cx, $cy, $w, $h, $cw, $ch);
    $cw0 = imagesx($canvas);
    $ch0 = imagesy($canvas);
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            if ($dx + $x < 0 || $dy + $y < 0 || $dx + $x >= $cw0 || $dy + $y >= $ch0) {
                continue; // the box may start outside the canvas
            }
            $a = $alpha($x / $w, $y / $h);
            $c = imagecolorat($tmp, $x, $y);
            $n = imagecolorat($canvas, $dx + $x, $dy + $y); // blend into the backdrop, so there is no visible seam
            $r = (int) round((($c >> 16) & 255) * (1 - $a) + (($n >> 16) & 255) * $a);
            $g = (int) round((($c >> 8) & 255) * (1 - $a) + (($n >> 8) & 255) * $a);
            $b = (int) round(($c & 255) * (1 - $a) + ($n & 255) * $a);
            imagesetpixel($canvas, $dx + $x, $dy + $y, ($r << 16) | ($g << 8) | $b);
        }
    }
    imagedestroy($tmp);
}

function backdrop(int $w, int $h, array $navy, bool $mobile)
{
    $im = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $t = $mobile ? $y / $h : ($x / $w * 0.7 + $y / $h * 0.3);
            $r = (int) ($navy[0] + (10 - $navy[0]) * $t);
            $g = (int) ($navy[1] + (42 - $navy[1]) * $t);
            $b = (int) ($navy[2] + (102 - $navy[2]) * $t);
            imagesetpixel($im, $x, $y, ($r << 16) | ($g << 8) | $b);
        }
    }

    return $im;
}

function save($im, string $base, int $maxKb, int $q = 92): void
{
    for (; $q >= 50; $q -= 4) {
        imagejpeg($im, $base.'.jpg', $q);
        imagewebp($im, $base.'.webp', $q);
        if (filesize($base.'.jpg') <= $maxKb * 1024 && filesize($base.'.webp') <= $maxKb * 1024) {
            break;
        }
    }
    printf("%s: q%d jpg %d KB, webp %d KB\n", basename($base), $q, filesize($base.'.jpg') / 1024, filesize($base.'.webp') / 1024);
}

// Head and shoulders are scaled down and raised so that the face stays clear of the anniversary seal (desktop,
// seal sits right of centre) and of the headline (phones, copy sits mid-screen). Source head: y 410..1254 of 2560.
// ---- desktop 1920x1080: portrait on the right (face about y 100..390), fades into navy on the left and below ----
$d = backdrop(1920, 1080, $navy, false);
place($d, $photo, [0, 0, $sw, $sh], [1260, -41, 660, 880], function ($u, $v) {
    return max(0.2, 1 - smooth($u / 0.5) * 0.8, smooth(($v - 0.7) / 0.3));
}, $navy);
save($d, $out.'poster', 250);

// ---- mobile 900x1200: face in the top fifth (above the headline), suit fades into navy ----
$m = backdrop(900, 1200, $navy, true);
place($m, $photo, [0, 0, $sw, $sh], [86, -125, 728, 970], function ($u, $v) {
    return max(0.22, 1 - smooth($u / 0.18) * 0.78, 1 - smooth((1 - $u) / 0.18) * 0.78, smooth(($v - 0.55) / 0.45));
}, $navy);
save($m, $out.'poster-mobile', 150);
