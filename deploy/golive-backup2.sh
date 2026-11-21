#!/bin/bash
# Second go-live backup: database dump + storage/app (uploads). Copies only. SKYLINK_APP_DIR=/home/skylinkictcom/s.skylinkict.com bash golive-backup2.sh
set -e
APP="${SKYLINK_APP_DIR:?}"; [ "$APP" = "/home/skylinkictcom/s.skylinkict.com" ] || { echo refusing; exit 1; }
TS="$(date +%Y%m%d-%H%M%S)"; B="$APP/deploy/backups/pre-admin-gallery-$TS"
umask 077; mkdir -p "$B"; chmod 700 "$B"
. "$APP/deploy/server-common.sh"
db_dump "$B/db-$TS"
tar -czf "$B/storage-app-$TS.tar.gz" -C "$APP/storage" app
cp -p "$APP/.env" "$B/env.pre-admin"
( cd "$B" && sha256sum db-*.sql.gz storage-app-*.tar.gz env.pre-admin > SHA256SUMS && sha256sum -c SHA256SUMS )
gunzip -t "$B"/db-*.sql.gz && echo "db tables: $(gunzip -c "$B"/db-*.sql.gz | grep -c '^CREATE TABLE')"
echo "tar entries: $(tar -tzf "$B"/storage-app-*.tar.gz | wc -l)"
echo "BACKUP: $B"
echo "ROLLBACK db:      cd $APP && . deploy/server-common.sh && db_restore $B/db-$TS.sql.gz   (check helper name in server-common.sh; it reads the password from .env)"
echo "ROLLBACK uploads: tar -xzf $B/storage-app-$TS.tar.gz -C $APP/storage"
echo "ROLLBACK .env:    cp -p $B/env.pre-admin $APP/.env && php artisan config:cache"
