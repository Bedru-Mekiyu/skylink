<?php
/*
 * Local safety guard for the helper scripts (start-site.bat, run-tests.bat,
 * check-*.bat, backup-db.bat). LOCAL USE ONLY; never upload this folder.
 *
 * It boots Laravel with the current .env and refuses (exit code 1) when:
 *   - APP_ENV is "production";
 *   - the database is not SQLite, or it is a server database on a host that
 *     is not this computer;
 *   - the mailer could send real email (anything other than log/array, or
 *     SMTP to a host that is not this computer, e.g. Mailpit on 127.0.0.1).
 *
 * It never prints secrets: no passwords, user names, host names, keys or
 * paths outside the project. Only driver names (sqlite, log, smtp...) appear.
 *
 * Usage:  php tools/local/guard.php            check only
 *         php tools/local/guard.php --sqlite-path   check, then print the
 *                                                   SQLite file path (project-relative)
 */

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$local = ['127.0.0.1', 'localhost', '::1', ''];
$problems = [];

if (config('app.env') === 'production') {
    $problems[] = 'APP_ENV is production. Use APP_ENV=local on this computer.';
}

$dbName = config('database.default');
$db = config("database.connections.$dbName", []);
$driver = $db['driver'] ?? 'unknown';
$sqlitePath = null;
if ($driver === 'sqlite') {
    $sqlitePath = (string) ($db['database'] ?? '');
    $real = realpath($sqlitePath) ?: $sqlitePath;
    if ($sqlitePath !== ':memory:' && ! str_starts_with(str_replace('\\', '/', strtolower($real)), str_replace('\\', '/', strtolower($root)))) {
        $problems[] = 'The SQLite database file is outside the project folder.';
    }
} else {
    $host = strtolower((string) ($db['host'] ?? ''));
    if (! in_array($host, $local, true)) {
        $problems[] = "The database ($driver) is on another computer. These scripts only run against a local database.";
    } else {
        $problems[] = "The database is $driver, not SQLite. The local setup uses SQLite (database/database.sqlite).";
    }
}

$mailer = config('mail.default');
$mailCfg = config("mail.mailers.$mailer", []);
$transport = $mailCfg['transport'] ?? $mailer;
if (in_array($transport, ['log', 'array'], true)) {
    // Safe: nothing leaves this computer.
} elseif ($transport === 'smtp' && in_array(strtolower((string) ($mailCfg['host'] ?? '')), $local, true)) {
    // Safe: SMTP to this computer (Mailpit).
} else {
    $problems[] = "The mailer ($transport) could send real email. Use MAIL_MAILER=log, or Mailpit (MAIL_MAILER=smtp, MAIL_HOST=127.0.0.1, MAIL_PORT=1025).";
}

if ($problems) {
    fwrite(STDERR, "STOPPED: this .env is not safe for local scripts.\n");
    foreach ($problems as $p) {
        fwrite(STDERR, " - $p\n");
    }
    fwrite(STDERR, "Nothing was changed.\n");
    exit(1);
}

if (in_array('--sqlite-path', $argv, true)) {
    $rel = ltrim(str_replace('\\', '/', substr(realpath($sqlitePath) ?: $sqlitePath, strlen($root))), '/');
    echo $rel, PHP_EOL;
    exit(0);
}

echo "Safety check OK (database: $driver, mail: $transport).", PHP_EOL;
