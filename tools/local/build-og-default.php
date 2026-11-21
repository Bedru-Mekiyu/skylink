<?php
// Builds public/assets/images/og-default.jpg (1200x630): brand gradient with the logo on a white card. Run: php tools/local/build-og-default.php
$w = 1200; $h = 630;
$img = imagecreatetruecolor($w, $h);
for ($y = 0; $y < $h; $y++) {
    $t = $y / $h;
    $c = imagecolorallocate($img, (int) (11 + (29 - 11) * $t), (int) (27 + (78 - 27) * $t), (int) (59 + (216 - 59) * $t));
    imageline($img, 0, $y, $w, $y, $c);
}
$white = imagecolorallocate($img, 255, 255, 255);
$cw = 820; $ch = 330; $cx = (int) (($w - $cw) / 2); $cy = (int) (($h - $ch) / 2); $r = 36;
imagefilledrectangle($img, $cx + $r, $cy, $cx + $cw - $r, $cy + $ch, $white);
imagefilledrectangle($img, $cx, $cy + $r, $cx + $cw, $cy + $ch - $r, $white);
foreach ([[$cx + $r, $cy + $r], [$cx + $cw - $r, $cy + $r], [$cx + $r, $cy + $ch - $r], [$cx + $cw - $r, $cy + $ch - $r]] as [$px, $py]) {
    imagefilledellipse($img, $px, $py, 2 * $r, 2 * $r, $white);
}
$logo = imagecreatefromwebp(__DIR__.'/../../public/assets/images/skylink-logo.webp');
$lw = imagesx($logo); $lh = imagesy($logo);
$scale = min(($cw - 160) / $lw, ($ch - 100) / $lh);
$nw = (int) ($lw * $scale); $nh = (int) ($lh * $scale);
imagealphablending($img, true);
imagecopyresampled($img, $logo, (int) (($w - $nw) / 2), (int) (($h - $nh) / 2), 0, 0, $nw, $nh, $lw, $lh);
imagejpeg($img, __DIR__.'/../../public/assets/images/og-default.jpg', 86);
echo "written ".filesize(__DIR__.'/../../public/assets/images/og-default.jpg')." bytes, logo {$lw}x{$lh}\n";
