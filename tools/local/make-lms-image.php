<?php
// LOCAL USE ONLY: PNG -> WebP files for the LMS product image (1200x900 + 600x450).
$src = imagecreatefrompng(__DIR__.'/../../storage/app/private/archive/product-sources/lms-dashboard.png');
$dir = __DIR__.'/../../public/assets/images/products'; if (!is_dir($dir)) mkdir($dir, 0775, true);
imagewebp($src, $dir.'/lms-learning-platform.webp', 82);
$small = imagescale($src, 600, 450, IMG_BICUBIC); imagewebp($small, $dir.'/lms-learning-platform-600.webp', 82);
foreach (glob($dir.'/lms-*.webp') as $f) echo basename($f).' '.round(filesize($f)/1024).' KB'.PHP_EOL;
