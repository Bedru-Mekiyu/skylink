<?php

/*
 * Sky Link front controller for https://skylinkict.com  (file: /home/skylinkictcom/public_html/skylink-front.php; the existing index.php is left alone)
 *
 * The Laravel app lives in /home/skylinkictcom/s.skylinkict.com. Nothing is copied: this file loads it from there.
 * public_path() stays the app's own public/ folder, which is where the assets and the Vite manifest really are.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Only Sky Link's own host may use this front controller. Any other host that ends up here gets a plain 404.
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if (! in_array($host, ['skylinkict.com', 'www.skylinkict.com'], true)) {
    http_response_code(404);
    exit;
}

$app = '/home/skylinkictcom/s.skylinkict.com';

// Maintenance mode (php artisan down) ...
if (file_exists($maintenance = $app.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app.'/vendor/autoload.php';

/** @var Application $laravel */
$laravel = require_once $app.'/bootstrap/app.php';

$laravel->handleRequest(Request::capture());
