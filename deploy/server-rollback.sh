#!/usr/bin/env bash
# Sky Link rollback script. The OWNER runs it over SSH, from the app folder:
#
#     cd /home/skylinkictcom/s.skylinkict.com
#     bash deploy/server-rollback.sh deploy/backups/<date-folder>
#
# Restores the files and the database dump that server-deploy.sh saved in that backup folder.
# It restores only: no file is deleted (files the release added stay in place, the old code ignores them).
# The database restore REPLACES the tables that are in the dump, so rows added since the backup are lost: it asks first.
set -Eeuo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
# shellcheck source=server-common.sh
. "$HERE/server-common.sh"
trap 'printf "\n!! STOPPED. Fix the message above, then run this script again. The site may be in maintenance mode (php artisan up).\n" >&2; exit 1' ERR

[ $# -eq 1 ] || die "Usage: bash deploy/server-rollback.sh <backup-folder>   (folders: $(ls "$APP/deploy/backups" 2>/dev/null | tr '\n' ' '))"
require_app_dir
BK="$(cd "$1" 2>/dev/null && pwd -P)" || die "Backup folder not found: $1"
case "$BK" in "$(cd "$APP" && pwd -P)"/deploy/backups/*) ;; *) die "The backup folder must be inside $APP/deploy/backups/";; esac
[ -s "$BK/files-before.tar.gz" ] || die "No files-before.tar.gz in $BK"
tar -tzf "$BK/files-before.tar.gz" >/dev/null || die "files-before.tar.gz is damaged."

step "Backup to restore"
cat "$BK/BACKUP-INFO.txt" 2>/dev/null || true
say "files in backup : $(tar -tzf "$BK/files-before.tar.gz" | grep -vc '/$' || true)"
RESTORE_DB=0
if [ -e "$BK/db.sql.gz" ] || [ -e "$BK/db.sqlite" ]; then
    say "database dump  : yes"
    say "Restoring the database REPLACES the whole database with the dump (tables created since are removed too): anything entered on the site since the backup is lost."
    if confirm_yes "Restore the database too?"; then RESTORE_DB=1; fi
fi
confirm_yes "Restore the files from this backup now?" || die "Cancelled. Nothing was changed."

step "1/4 Maintenance mode"
"$PHP" artisan down --retry=60 --secret="$(od -An -N8 -tx1 /dev/urandom | tr -d ' \n')" || true

step "2/4 Restore files (overwrite only)"
tar -xzf "$BK/files-before.tar.gz" -C "$APP"
say "   done"

step "3/4 Database"
if [ "$RESTORE_DB" = 1 ]; then db_restore "$BK/db"; say "   restored"; else say "   skipped"; fi

step "4/4 Caches and back online"
rebuild_caches
"$PHP" artisan up
say "   ceremony for visitors: $("$PHP" artisan tinker --execute='echo App\Support\Anniversary::liveForVisitors() ? "LIVE" : "HIDDEN";' 2>/dev/null | tail -n1)"
say "Rolled back from $BK"
