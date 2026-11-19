<?php

/*
 * LOCAL TEST ONLY. Mimics deploy/domain-switch/public_html.htaccess.NEW for PHP's built-in server (no Apache here).
 * It reproduces the rules in the same order; it does not read the .htaccess file itself.
 *   env SIM_HTTPS=1   pretend the request came in over HTTPS
 *   env FRONT=skylink-front.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$qs = $_SERVER['QUERY_STRING'] ?? '';
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$docroot = $_SERVER['DOCUMENT_ROOT'];
$file = $docroot.$uri;
$isDir = is_dir($file);
$https = getenv('SIM_HTTPS') === '1';
$sky = in_array($host, ['skylinkict.com', 'www.skylinkict.com'], true);
$port = preg_match('/:(\d+)$/', $_SERVER['HTTP_HOST'] ?? '', $m) ? ':'.$m[1] : '';

// Rule: trailing slash on a non-directory -> 301 without it
if (! $isDir && preg_match('#.+/$#', $uri)) {
    header('Location: '.rtrim($uri, '/').($qs ? "?$qs" : ''), true, 301);
    exit;
}
// Rule: Sky Link hosts -> https + non-www
if ($sky && (! $https || str_starts_with($host, 'www.'))) {
    header('Location: https://skylinkict.com'.$port.$uri.($qs ? "?$qs" : ''), true, 301);
    exit;
}

$front = function () use ($docroot) {
    $_SERVER['SCRIPT_NAME'] = '/skylink-front.php';
    $_SERVER['PHP_SELF'] = '/skylink-front.php';
    $_SERVER['SCRIPT_FILENAME'] = $docroot.'/skylink-front.php';
    require $docroot.'/skylink-front.php';
    exit;
};

// Rule: home page of skylinkict.com -> front controller (before DirectoryIndex could pick index.html)
if ($host === 'skylinkict.com' && $uri === '/') {
    $front();
}
// Existing file: served as is (static file, or PHP file executed)
if (is_file($file)) {
    return false;
}
// Existing folder: served as before (index file or 403 because of -Indexes)
if ($isDir) {
    foreach (['index.php', 'index.html'] as $i) {
        if (is_file(rtrim($file, '/').'/'.$i)) {
            return false; // built-in server picks the index file itself
        }
    }
    http_response_code(403);
    echo 'Forbidden (directory listing off)';
    exit;
}
// Everything else: Sky Link host -> Laravel; any other host -> plain 404 (as Apache would, no rule matches)
if ($host === 'skylinkict.com') {
    $front();
}
http_response_code(404);
echo 'Not Found';
