<?php

// LOCAL DEBUGGING ONLY: run one GET request through an app folder in-process and print the real exception.
//   php tools/local/diag-request.php <app-folder> [/uri]
// Uses the process environment (DB_*, APP_KEY ...). Prints no secrets, only the status or the exception.
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;

require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->singleton(ExceptionHandler::class, fn () => new class implements ExceptionHandler {
    public function report(Throwable $e) {}
    public function shouldReport(Throwable $e) { return false; }
    public function render($request, Throwable $e) { throw $e; }
    public function renderForConsole($output, Throwable $e) { throw $e; }
});
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
try {
    $response = $kernel->handle(Request::create('/'.ltrim(($argv[2] ?? '.') === '.' ? '' : $argv[2], '/')));
    echo 'status '.$response->getStatusCode()."\n";
} catch (Throwable $e) {
    echo get_class($e).': '.substr($e->getMessage(), 0, 400)."\n  at ".$e->getFile().':'.$e->getLine()."\n";
}
