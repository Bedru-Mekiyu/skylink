#!/usr/bin/env bash
# Sky Link deploy script. The OWNER runs it over SSH, from the app folder:
#
#     cd /home/skylinkictcom/s.skylinkict.com
#     bash deploy/server-deploy.sh
#
# The release files (skylink-app.zip, skylink-public.zip, their .sha256, RELEASE.txt) and server-common.sh,
# server-rollback.sh sit next to this script in <app>/deploy/.
#
# It works ONLY inside /home/skylinkictcom/s.skylinkict.com and it never deletes anything:
#   - backs up every file the release will overwrite, plus a database dump, into deploy/backups/<date>/
#   - maintenance mode, extracts the zips (overwrite only), asks before running migrations
#   - never touches .env, uploads (storage/app/public), public/.user.ini or public/php.ini
#   - never runs news:publish-anniversary
# On any error it stops and prints the exact rollback command.
set -Eeuo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
# shellcheck source=server-common.sh
. "$HERE/server-common.sh"

STAMP="$(date +%Y%m%d-%H%M%S)"
BK="$APP/deploy/backups/$STAMP"
STATE="start"   # start -> backup-done -> down -> extracted -> migrated -> up
hint() {
    printf '\n!! STOPPED (state: %s). Nothing else was run.\n' "$STATE" >&2
    case "$STATE" in
        start) printf '   Nothing was changed on the server.\n' >&2;;
        backup-done) printf '   Only the backup was made: %s\n   The site is still online and unchanged.\n' "$BK" >&2;;
        *) printf '   Backup: %s\n   Roll back with:  bash deploy/server-rollback.sh %s\n' "$BK" "$BK" >&2
           [ "$STATE" = up ] || printf '   The site is in maintenance mode until you run: php artisan up (or the rollback script does it).\n' >&2;;
    esac
}
die() { printf '\nERROR: %s\n' "$*" >&2; hint; exit 1; }
trap 'hint; exit 1' ERR

step "1/9 Checks"
require_app_dir
APP_ZIP="$HERE/skylink-app.zip"; PUB_ZIP="$HERE/skylink-public.zip"
for z in "$APP_ZIP" "$PUB_ZIP"; do
    [ -f "$z" ] || die "Missing $(basename "$z") in $HERE. Upload it there first."
    [ -f "$z.sha256" ] || die "Missing $(basename "$z").sha256 in $HERE."
    want="$(awk '{print tolower($1)}' "$z.sha256")"; got="$(sha256sum "$z" | awk '{print tolower($1)}')"
    [ "$want" = "$got" ] || die "Checksum of $(basename "$z") does not match: the upload is damaged. Upload it again."
done
command -v unzip >/dev/null 2>&1 || die "unzip is not available. Extract both zips with File Manager (app zip into $APP, public zip into $APP/public), then run this script with SKIP_EXTRACT=1."
[ -f "$HERE/RELEASE.txt" ] && sed -n '1,12p' "$HERE/RELEASE.txt"
say "App folder : $APP"
say "APP_ENV    : $(env_get APP_ENV)   APP_DEBUG: $(env_get APP_DEBUG)   APP_URL: $(env_get APP_URL)"
if [ "$(env_get APP_DEBUG)" = "true" ] || [ "$(env_get APP_ENV)" != "production" ]; then
    say "WARNING   : .env is not production yet (APP_ENV=production, APP_DEBUG=false). Fix it after this script (guide step 4)."
fi

# What the zips contain, and a guard that they never carry anything protected.
list_entries() { unzip -Z1 "$1" | sed 's#^\./##' | grep -v '/$' | grep -v '^$' || true; }
{ list_entries "$APP_ZIP"; list_entries "$PUB_ZIP" | sed 's#^#public/#'; } > "$HERE/.entries.tmp"
if grep -Eq '(^|/)\.\.(/|$)|^/|^\.env($|\.)|^public/\.user\.ini$|^public/php\.ini$|^public/\.well-known/|^public/storage($|/)|^deploy/|^storage/app/public/[^.]' "$HERE/.entries.tmp"; then
    grep -E '(^|/)\.\.(/|$)|^/|^\.env($|\.)|^public/\.user\.ini$|^public/php\.ini$|^public/\.well-known/|^public/storage($|/)|^deploy/|^storage/app/public/[^.]' "$HERE/.entries.tmp" | head -5
    die "A zip contains a protected path (listed above). Refusing to extract."
fi
grep -qx 'artisan' "$HERE/.entries.tmp" && grep -qx 'vendor/autoload.php' "$HERE/.entries.tmp" && grep -qx 'public/index.php' "$HERE/.entries.tmp" || die "The zips do not look like a Sky Link release."
TOTAL="$(wc -l < "$HERE/.entries.tmp")"
say "Release files: $TOTAL"

step "2/9 Backup into $BK"
mkdir -p "$BK"
chmod 700 "$APP/deploy/backups" "$BK" 2>/dev/null || true
: > "$BK/existing-files.txt"; : > "$BK/new-files.txt"
while IFS= read -r f; do
    if [ -e "$APP/$f" ]; then printf '%s\n' "$f" >> "$BK/existing-files.txt"; else printf '%s\n' "$f" >> "$BK/new-files.txt"; fi
done < "$HERE/.entries.tmp"
tar -czf "$BK/files-before.tar.gz" -C "$APP" -T "$BK/existing-files.txt"
tar -tzf "$BK/files-before.tar.gz" >/dev/null || die "The file backup cannot be read back."
say "   files backed up : $(wc -l < "$BK/existing-files.txt")   (new files the release adds: $(wc -l < "$BK/new-files.txt"))"
db_dump "$BK/db"
{ echo "backup of $APP"; echo "made $(date '+%F %T')"; [ -f "$HERE/RELEASE.txt" ] && sed -n '1,3p' "$HERE/RELEASE.txt"; } > "$BK/BACKUP-INFO.txt"
ls -lh "$BK" | sed 's/^/   /'
STATE="backup-done"

step "3/9 Maintenance mode"
SECRET="$(od -An -N8 -tx1 /dev/urandom | tr -d ' \n')"
"$PHP" artisan down --retry=60 --secret="$SECRET"
STATE="down"
say "   You can still open the site with: $(env_get APP_URL)/$SECRET"

step "4/9 Extract (overwrite only, nothing is deleted)"
if [ "${SKIP_EXTRACT:-0}" = "1" ]; then
    say "   SKIP_EXTRACT=1: assuming you extracted the zips yourself."
else
    unzip -o -q "$APP_ZIP" -d "$APP"
    unzip -o -q "$PUB_ZIP" -d "$APP/public"
fi
[ -f "$ENVF" ] || die ".env disappeared (it must never be touched)."
STATE="extracted"

step "5/9 Pending migrations"
"$PHP" artisan migrate:status --pending || true
PRETEND="$("$PHP" artisan migrate --pretend --force 2>&1 || true)"
if printf '%s' "$PRETEND" | grep -Eiq '\bdrop (table|column)\b'; then
    printf '%s\n' "$PRETEND" | grep -Ei '\bdrop (table|column)\b' | head -5
    confirm_yes "The migrations above would DROP something. Type yes only if you expected that" || die "Stopped before migrating (a DROP was found)."
fi
if confirm_yes "Run these migrations now (php artisan migrate --force)?"; then
    "$PHP" artisan migrate --force
else
    die "Migrations were not run."
fi
STATE="migrated"

step "6/9 Storage link, caches, permissions"
[ -L "$APP/public/storage" ] || "$PHP" artisan storage:link
rebuild_caches

step "7/9 Back online"
"$PHP" artisan up
STATE="up"

step "8/9 Quick check"
URL="$(env_get APP_URL)"
for p in / /login /news; do
    code="$(curl -s -o /dev/null -m 20 -w '%{http_code}' "$URL$p" 2>/dev/null || echo ---)"
    say "   GET $p -> $code"
done
MARK="$(curl -s -m 20 "$URL/" 2>/dev/null | grep -c 'anniv5' || true)"
say "   anniversary markup in the public home page: $MARK  (0 = hidden)"
say "   ceremony for visitors (from the app): $("$PHP" artisan tinker --execute='echo App\Support\Anniversary::liveForVisitors() ? "LIVE" : "HIDDEN (Off)";' 2>/dev/null | tail -n1)"
say "   still-pending migrations: $("$PHP" artisan migrate:status --pending 2>/dev/null | grep -c 'Pending' || true)"
[ "$(env_get APP_DEBUG)" = "false" ] || say "   !! APP_DEBUG is not false in .env. Fix it now."
grep -Eq '^[[:space:]]*ANNIVERSARY_FORCE=' "$ENVF" && say "   !! .env has ANNIVERSARY_FORCE. Remove that line."
crontab -l 2>/dev/null | grep -q 's.skylinkict.com' || say "   note: no cron line for this site yet (guide: Deploy Day, step 'Cron')."

step "9/9 Done"
say "Backup folder : $BK"
say "Roll back     : bash deploy/server-rollback.sh $BK"
say "Not done on purpose: news:publish-anniversary (run it on the ceremony day), turning the ceremony on (Admin > Anniversary Ceremony)."
