#!/usr/bin/env bash
# LOCAL TEST of server-deploy.sh and server-rollback.sh on a copy that mimics the server layout (fresh temp folder,
# SQLite test database, fake .env-style settings file). It never touches a real server.
#   bash deploy/test-server-scripts.sh [deploy/.build/release]
# Run it from the project root in Git Bash. Exit code 0 = all checks passed.
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
REL="$(cd "${1:-$ROOT/deploy/.build/release}" && pwd -P)"
TMP="$(mktemp -d "${TMPDIR:-/tmp}/skylink-sim-XXXXXX")"
TMPW="$(cygpath -m "$TMP" 2>/dev/null || echo "$TMP")"   # native-PHP form of the path (C:/...)
A="$TMP/s.skylinkict.com"
PORT=$((8300 + RANDOM % 600))   # random port; a stale server of an earlier run could otherwise answer
FAIL=0
ok()   { printf 'ok   %s\n' "$1"; }
bad()  { printf 'FAIL %s\n' "$1"; FAIL=1; }
check(){ if eval "$2"; then ok "$1"; else bad "$1"; fi; }
SERVER_PID=""
cleanup() { if [ -n "$SERVER_PID" ]; then kill "$SERVER_PID" 2>/dev/null || true; fi; }
trap cleanup EXIT

echo "sim folder: $TMP"
mkdir -p "$A/public" "$A/deploy"
unzip -q "$REL/skylink-app.zip" -d "$A"
unzip -q "$REL/skylink-public.zip" -d "$A/public"

# ---- turn the extracted release into the OLD server state ------------------------------------------------
# old code marker: previous versions of two files; 8 newest migrations absent; a cPanel .user.ini; an upload.
git -C "$ROOT" show 01a483c:app/Support/Anniversary.php > "$A/app/Support/Anniversary.php"
git -C "$ROOT" show 01a483c:config/anniversary.php      > "$A/config/anniversary.php"
for m in $(ls "$A/database/migrations" | awk '$0 > "2025_07_23_081217_add_project_url_to_portfolios_table.php"'); do rm "$A/database/migrations/$m"; done
echo 'auto_prepend_file = "/home/skylinkictcom/cpanel.php"' > "$A/public/.user.ini"
echo 'php.ini of cpanel' > "$A/public/php.ini"
echo 'favicon of the server' > "$A/public/favicon.ico"
mkdir -p "$A/storage/app/public/news"; echo 'uploaded picture' > "$A/storage/app/public/news/cover.jpg"
cp "$A/app/Support/Anniversary.php" "$TMP/old-Anniversary.php"
sum() { sha256sum "$@" | awk '{print $1}' | tr '\n' ' '; }
BEFORE_PROTECTED="$(sum "$A/public/.user.ini" "$A/public/php.ini" "$A/public/favicon.ico" "$A/storage/app/public/news/cover.jpg")"

# the settings file (named like .env but in the temp folder) and the process environment for artisan
cat > "$TMP/envfile" <<EOF
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:$PORT
DB_CONNECTION=sqlite
DB_DATABASE=$TMPW/server.sqlite
EOF
: > "$TMP/server.sqlite"
export APP_ENV=local APP_DEBUG="${SIM_DEBUG:-false}" ANNIVERSARY_FORCE=false APP_URL="http://127.0.0.1:$PORT" DB_CONNECTION=sqlite DB_DATABASE="$TMPW/server.sqlite"
export APP_KEY="base64:$(openssl rand -base64 32)" SESSION_DRIVER=database CACHE_STORE=database QUEUE_CONNECTION=sync MAIL_MAILER=log
export SKYLINK_APP_DIR="$A" SKYLINK_ENV_FILE="$TMP/envfile"

cd "$A"
php artisan migrate --force --no-interaction >/dev/null
check "old server state: 18 migrations ran" '[ "$(sqlite3 "$DB_DATABASE" "select count(*) from migrations" 2>/dev/null || php -r "echo (new PDO(\"sqlite:\".getenv(\"DB_DATABASE\")))->query(\"select count(*) from migrations\")->fetchColumn();")" = 18 ]'
php -r '$p=new PDO("sqlite:".getenv("DB_DATABASE")); $p->exec("insert into users (name,email,password,created_at,updated_at) values (\"Old User\",\"old@example.test\",\"x\",datetime(),datetime())");'
OLD_USERS="$(php -r 'echo (new PDO("sqlite:".getenv("DB_DATABASE")))->query("select count(*) from users")->fetchColumn();')"

# the release kit, as the owner uploads it
cp "$REL"/skylink-*.zip "$REL"/skylink-*.sha256 "$REL"/RELEASE.txt "$REL"/server-*.sh "$A/deploy/"

( cd "$A/public" && exec php -d display_errors=Off -S "127.0.0.1:$PORT" "$A/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php" >"$TMP/server.log" 2>&1 ) &
SERVER_PID=$!

echo; echo "--- 1. wrong folder is refused"
if ( cd "$TMP" && bash "$A/deploy/server-deploy.sh" >/dev/null 2>"$TMP/wrong.err" ); then bad "deploy ran outside the app folder"; else
  check "refuses to run outside the app folder, changes nothing" 'grep -q "Run this from" "$TMP/wrong.err" && [ ! -d "$A/deploy/backups" ]'; fi

echo; echo "--- 2. a damaged zip is refused"
cp "$A/deploy/skylink-public.zip" "$TMP/public.good"; printf 'x' >> "$A/deploy/skylink-public.zip"
if printf 'yes\n' | bash deploy/server-deploy.sh >"$TMP/bad.out" 2>"$TMP/bad.err"; then bad "damaged zip accepted"; else check "checksum mismatch stops before any change" 'grep -q "does not match" "$TMP/bad.err" && [ ! -d "$A/deploy/backups" ]'; fi
cp "$TMP/public.good" "$A/deploy/skylink-public.zip"

echo; echo "--- 3. deploy (answer yes to the migration prompt)"
printf 'yes\n' | bash deploy/server-deploy.sh | tee "$TMP/deploy.out" | sed 's/^/   | /' | tail -n 40
BK="$(ls -d "$A"/deploy/backups/*/ | head -n1)"; BK="${BK%/}"
[ -z "${SIM_SHELL:-}" ] || eval "$SIM_SHELL"   # debugging hook: run extra commands while the sim server is up
check "backup folder has files-before.tar.gz and a database dump" '[ -s "$BK/files-before.tar.gz" ] && [ -s "$BK/db.sqlite" ]'
check "8 migrations ran (26 total)" '[ "$(php -r "echo (new PDO(\"sqlite:\".getenv(\"DB_DATABASE\")))->query(\"select count(*) from migrations\")->fetchColumn();")" = 26 ]'
check "new code is in place (Anniversary.php differs from the old one)" '! cmp -s "$A/app/Support/Anniversary.php" "$TMP/old-Anniversary.php"'
check ".user.ini, php.ini, favicon and uploads untouched" '[ "$(sum "$A/public/.user.ini" "$A/public/php.ini" "$A/public/favicon.ico" "$A/storage/app/public/news/cover.jpg")" = "$BEFORE_PROTECTED" ]'
check "the settings file was not changed" 'grep -q "APP_ENV=local" "$TMP/envfile"'
check "site is back up (no maintenance file)" '[ ! -f "$A/storage/framework/down" ] && [ ! -f "$A/storage/framework/maintenance.php" ]'
curl -s "http://127.0.0.1:$PORT/" > "$TMP/home.html"; grep -oE "(SQLSTATE[^<]{0,160}|Undefined [^<]{0,100}|Call to [^<]{0,100}|View \[[^<]{0,100})" "$TMP/home.html" | head -2 | sed "s/^/   diag: /" || true
check "home page answers 200 and has no ceremony markup" '[ "$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:$PORT/")" = 200 ] && ! curl -s "http://127.0.0.1:$PORT/" | grep -q anniv5'
check "ceremony is HIDDEN (Off) for visitors on the fresh state" 'grep -q "HIDDEN (Off)" "$TMP/deploy.out"'
check "script printed the rollback command and never ran news:publish-anniversary" 'grep -q "server-rollback.sh" "$TMP/deploy.out" && ! grep -qi "publish-anniversary.*published" "$TMP/deploy.out"'
check "no news article was created by the deploy" '[ "$(php -r "echo (new PDO(\"sqlite:\".getenv(\"DB_DATABASE\")))->query(\"select count(*) from news\")->fetchColumn();")" = 0 ]'
php -r '$p=new PDO("sqlite:".getenv("DB_DATABASE")); $p->exec("insert into users (name,email,password,created_at,updated_at) values (\"After Deploy\",\"after@example.test\",\"x\",datetime(),datetime())");'

echo; echo "--- 4. rollback (restore the database too, then the files)"
printf 'yes\nyes\n' | bash deploy/server-rollback.sh "$BK" | sed 's/^/   | /' | tail -n 16
check "old code restored (Anniversary.php equals the old file)" 'cmp -s "$A/app/Support/Anniversary.php" "$TMP/old-Anniversary.php"'
check "database restored (back to 18 migrations, the row added after the deploy is gone)" '[ "$(php -r "echo (new PDO(\"sqlite:\".getenv(\"DB_DATABASE\")))->query(\"select count(*) from migrations\")->fetchColumn();")" = 18 ] && [ "$(php -r "echo (new PDO(\"sqlite:\".getenv(\"DB_DATABASE\")))->query(\"select count(*) from users\")->fetchColumn();")" = "$OLD_USERS" ]'
check "nothing was deleted: files the release added are still there" '[ -f "$A/database/migrations/2026_11_21_100000_create_anniversary_settings_table.php" ]'
check "protected files still untouched after rollback" '[ "$(sum "$A/public/.user.ini" "$A/public/php.ini" "$A/public/favicon.ico" "$A/storage/app/public/news/cover.jpg")" = "$BEFORE_PROTECTED" ]'
# (the simulated old state is the new code with an old database, so only pages that need no new columns can work here)
check "site is back online after rollback (no maintenance mode, /login answers 200)" '[ ! -f "$A/storage/framework/down" ] && [ "$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:$PORT/login")" = 200 ]'

echo; echo "--- 5. rollback refuses a folder outside deploy/backups"
if printf 'yes\nyes\n' | bash deploy/server-rollback.sh "$TMP" >/dev/null 2>"$TMP/rb.err"; then bad "rollback accepted a foreign folder"; else check "foreign folder refused" 'grep -q "must be inside" "$TMP/rb.err"'; fi

echo
if [ "$FAIL" = 0 ]; then echo "ALL SERVER-SCRIPT CHECKS PASSED  (temp folder kept: $TMP)"; else echo "SOME CHECKS FAILED (temp folder kept: $TMP)"; exit 1; fi
