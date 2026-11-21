#!/bin/bash
# Pre-go-live backup of the LIVE app (read-only on the live files: copies only, nothing is deleted or changed).
# Run ON THE SERVER:  SKYLINK_APP_DIR=/home/skylinkictcom/s.skylinkict.com bash golive-backup.sh
# Writes into <app>/deploy/backups/pre-golive-<timestamp>/ (not served by the web: the web root is <app>/public).
set -e
APP="${SKYLINK_APP_DIR:?set SKYLINK_APP_DIR}"
[ "$APP" = "/home/skylinkictcom/s.skylinkict.com" ] || { echo "refusing: unexpected folder $APP"; exit 1; }
TS="$(date +%Y%m%d-%H%M%S)"
B="$APP/deploy/backups/pre-golive-$TS"
umask 077
mkdir -p "$B/public_html-files"
chmod 700 "$APP/deploy" "$APP/deploy/backups" "$B" 2>/dev/null || true
. "$APP/deploy/server-common.sh" 2>/dev/null || { echo "server-common.sh missing in $APP/deploy"; exit 1; }

echo "== 1/4 files of the live folder (code, public, storage with uploads, .env, .htaccess; without node_modules and deploy)"
tar -czf "$B/live-folder-$TS.tar.gz" -C "$(dirname "$APP")" --exclude="$(basename "$APP")/node_modules" --exclude="$(basename "$APP")/deploy" "$(basename "$APP")"
echo "== 2/4 database dump"
db_dump "$B/db-$TS"
echo "== 3/4 the files of public_html that will change (copies)"
for f in .htaccess .user.ini index.php; do cp -p "/home/skylinkictcom/public_html/$f" "$B/public_html-files/$f" 2>/dev/null || echo "  (no $f)"; done
cp -p "$APP/.env" "$B/env.pre-golive"
cp -p "$APP/public/.htaccess" "$B/public.htaccess.pre-golive"
echo "== 4/4 checksums and verification"
( cd "$B" && sha256sum live-folder-*.tar.gz db-*.sql.gz env.pre-golive public.htaccess.pre-golive public_html-files/* > SHA256SUMS && sha256sum -c SHA256SUMS )
tar -tzf "$B/live-folder-$TS.tar.gz" > /tmp/golive-tar-list.$$ && echo "tar readable: $(wc -l < /tmp/golive-tar-list.$$) entries; has .env: $(grep -c '/\.env$' /tmp/golive-tar-list.$$); has storage/app: $(grep -c '/storage/app/' /tmp/golive-tar-list.$$)"; rm -f /tmp/golive-tar-list.$$
gunzip -t "$B"/db-*.sql.gz && echo "db dump gzip OK; tables in dump: $(gunzip -c "$B"/db-*.sql.gz | grep -c '^CREATE TABLE')"
chmod 600 "$B"/* 2>/dev/null || true
echo "BACKUP FOLDER: $B"
echo "RESTORE (files):   tar -xzf $B/live-folder-$TS.tar.gz -C /home/skylinkictcom"
echo "RESTORE (db):      gunzip -c $B/db-$TS.sql.gz | MYSQL_PWD=<db password> mysql -h <host> -u <user> <database>   (the app's own db_dump/db_restore helpers in deploy/server-common.sh do this without typing the password)"
echo "RESTORE (public_html): cp -p $B/public_html-files/.htaccess /home/skylinkictcom/public_html/.htaccess"
