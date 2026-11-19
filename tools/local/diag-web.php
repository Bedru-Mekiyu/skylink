<?php

// LOCAL DEBUGGING ONLY. Copy into a TEST app's public/ folder and open /diag-web.php?u=/news : handles that URI through the
// real web process and prints the real exception (class, message, file:line). Never copy it to a real server.
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->singleton(ExceptionHandler::class, fn () => new class implements ExceptionHandler {
    public function report(Throwable $e) {}
    public function shouldReport(Throwable $e) { return false; }
    public function render($request, Throwable $e) { throw $e; }
    public function renderForConsole($output, Throwable $e) { throw $e; }
});
header('Content-Type: text/plain');
try {
    $req = Request::create($_GET['u'] ?? '/', 'GET', [], $_COOKIE, [], ['HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'localhost']);
    $r = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($req);
    echo 'status '.$r->getStatusCode();
} catch (Throwable $e) {
    echo get_class($e).': '.substr($e->getMessage(), 0, 500)."\n  at ".$e->getFile().':'.$e->getLine()."\n";
    foreach (array_slice($e->getTrace(), 0, 6) as $t) { echo '    '.($t['class'] ?? '').($t['type'] ?? '').($t['function'] ?? '').' '.basename($t['file'] ?? '').':'.($t['line'] ?? '')."\n"; }
}
