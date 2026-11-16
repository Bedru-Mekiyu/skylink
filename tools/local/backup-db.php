<?php
/*
 * Local database backup. LOCAL USE ONLY (called by backup-db.bat).
 * Runs the safety guard first, then writes a consistent copy of the local
 * SQLite file with SQLite's "VACUUM INTO" (safe even while the site runs) to
 *   footage/_review/db-backup/database.sqlite.<YYYYMMDD-HHMMSS>.<note>.bak
 * The original file is only read, never changed.
 *
 * Usage: php tools/local/backup-db.php [note]     (note: letters, digits, - and _)
 */
$root = dirname(__DIR__, 2);
$php = PHP_BINARY;

$rel = trim((string) shell_exec(escapeshellarg($php).' '.escapeshellarg(__DIR__.'/guard.php').' --sqlite-path'));
if ($rel === '' || ! is_file($root.'/'.$rel)) {
    fwrite(STDERR, "STOPPED: the safety check failed or the SQLite file was not found. Nothing was copied.\n");
    exit(1);
}

$note = preg_replace('/[^A-Za-z0-9_-]/', '', $argv[1] ?? 'manual') ?: 'manual';
$dir = $root.'/footage/_review/db-backup';
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}
date_default_timezone_set('Africa/Addis_Ababa'); // name backups in local (Addis Ababa) time
$target = $dir.'/database.sqlite.'.date('Ymd-His').'.'.$note.'.bak';

$pdo = new PDO('sqlite:'.$root.'/'.$rel, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('VACUUM INTO '.$pdo->quote($target));

// Check the copy opens and holds the same tables.
$copy = new PDO('sqlite:'.$target, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$count = fn (PDO $p) => (int) $p->query("select count(*) from sqlite_master where type='table'")->fetchColumn();
if ($copy->query('pragma integrity_check')->fetchColumn() !== 'ok' || $count($copy) !== $count($pdo)) {
    fwrite(STDERR, "WARNING: the backup was written but did not pass the check: $target\n");
    exit(1);
}

printf("Backup saved: footage/_review/db-backup/%s (%s KB, %d tables, integrity ok)\n", basename($target), number_format(filesize($target) / 1024), $count($copy));
