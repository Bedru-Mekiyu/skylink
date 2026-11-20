<?php
// LOCAL USE ONLY: the logo SVG only wraps a 4096x2193 PNG (700 KB). Writes a small WebP (480 px wide, transparent) for the pages.
$svg = file_get_contents(__DIR__.'/../../public/assets/images/skylink-logo.svg');
preg_match('#base64,([A-Za-z0-9+/=]+)#', $svg, $m);
$src = imagecreatefromstring(base64_decode($m[1]));
$w = 480; $h = (int) round(imagesy($src) * $w / imagesx($src));
$dst = imagecreatetruecolor($w, $h); imagealphablending($dst, false); imagesavealpha($dst, true);
imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
imagewebp($dst, __DIR__.'/../../public/assets/images/skylink-logo.webp', 90);
echo "skylink-logo.webp {$w}x{$h} ".round(filesize(__DIR__.'/../../public/assets/images/skylink-logo.webp') / 1024)." KB\n";
