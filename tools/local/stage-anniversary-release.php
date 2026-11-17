<?php

$manifestPath = __DIR__ . '/../../footage/_review/release-anniversary/FILES.txt';
$files = file($manifestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$lines = [];

foreach ($files as $rel) {
    $src = __DIR__ . '/../../' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dst = __DIR__ . '/../../footage/_review/release-anniversary/files/' . $rel;
    $dstDir = dirname($dst);
    if (!is_dir($dstDir)) {
        mkdir($dstDir, 0777, true);
    }
    copy($src, $dst);
    $hash = hash_file('sha256', $src);
    $lines[] = $hash . '  ' . $rel;
}

$checksumPath = __DIR__ . '/../../footage/_review/release-anniversary/SHA256SUMS.txt';
file_put_contents($checksumPath, implode("\n", $lines) . "\n");
echo "Updated " . count($lines) . " files in release-anniversary/files/ and regenerated SHA256SUMS.txt:\n";
echo file_get_contents($checksumPath);
