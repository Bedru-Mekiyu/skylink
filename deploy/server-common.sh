#!/usr/bin/env bash
# Shared helpers for server-deploy.sh and server-rollback.sh. Sourced, never run on its own.
# Nothing in this file deletes anything, and nothing prints a password or other .env value
# (only APP_ENV, APP_DEBUG, APP_URL and the database NAME are ever shown).

# The only folder these scripts may work in. SKYLINK_APP_DIR exists so the scripts can be tested on a copy;
# on the server leave it unset.
APP="${SKYLINK_APP_DIR:-/home/skylinkictcom/s.skylinkict.com}"
PHP="${PHP:-php}"
# The settings file. Leave SKYLINK_ENV_FILE unset on the server (it is only for tests on a copy).
ENVF="${SKYLINK_ENV_FILE:-$APP/.env}"

say()  { printf '%s\n' "$*"; }
step() { printf '\n== %s\n' "$*"; }
die()  { printf '\nERROR: %s\n' "$*" >&2; exit 1; }

require_app_dir() {
    local here
    here="$(pwd -P)"
    [ "$here" = "$(cd "$APP" 2>/dev/null && pwd -P)" ] || die "Run this from $APP (you are in $here). Nothing was changed."
    [ -f "$APP/artisan" ] && [ -f "$ENVF" ] || die "artisan or .env is missing in $APP. Nothing was changed."
    "$PHP" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || die "PHP in this shell is older than 8.2 ($("$PHP" -r 'echo PHP_VERSION;')). See the guide, step 'PHP version'. Nothing was changed."
}

# env_get KEY -> value of KEY in .env (quotes and CR removed). Never echo the result for secrets.
env_get() {
    local line
    line="$(grep -E "^[[:space:]]*$1=" "$ENVF" | tail -n1 || true)"
    line="${line#*=}"
    line="${line%$'\r'}"
    if [[ $line == \"*\" ]]; then line="${line:1:${#line}-2}"
    elif [[ $line == \'*\' ]]; then line="${line:1:${#line}-2}"
    else line="${line%% \#*}"; line="${line%"${line##*[![:space:]]}"}"; fi
    printf '%s' "$line"
}

confirm_yes() { # confirm_yes "question"  -> returns 0 only for the exact answer: yes
    local ans
    printf '%s (type yes): ' "$1"
    read -r ans || ans=""
    [ "$ans" = "yes" ]
}

# db_dump <target-prefix>: MySQL/MariaDB -> <prefix>.sql.gz   (SQLite, used only in tests -> <prefix>.sqlite)
db_dump() {
    local out="$1" conn name
    conn="$(env_get DB_CONNECTION)"; name="$(env_get DB_DATABASE)"
    case "$conn" in
        sqlite)
            [ -f "$name" ] || die "SQLite database file not found."
            cp -p "$name" "$out.sqlite"
            ;;
        mysql|mariadb|"")
            command -v mysqldump >/dev/null 2>&1 || die "mysqldump is not available in this shell."
            say "   database: $name"
            set -o pipefail
            MYSQL_PWD="$(env_get DB_PASSWORD)" mysqldump -h "$(env_get DB_HOST)" -P "$(env_get DB_PORT)" -u "$(env_get DB_USERNAME)" \
                --single-transaction --quick --routines --no-tablespaces --default-character-set=utf8mb4 "$name" 2>"$out.err" | gzip > "$out.sql.gz" \
                || die "mysqldump failed (see $out.err, it contains no password)."
            [ -s "$out.sql.gz" ] || die "The database dump is empty."
            gunzip -c "$out.sql.gz" | tail -n 2 | grep -q 'Dump completed' || die "The database dump is incomplete (no 'Dump completed' line)."
            ;;
        *) die "Unsupported DB_CONNECTION ($conn).";;
    esac
}

# db_restore <source-prefix>
db_restore() {
    local src="$1" conn name
    conn="$(env_get DB_CONNECTION)"; name="$(env_get DB_DATABASE)"
    case "$conn" in
        sqlite) [ -f "$src.sqlite" ] || die "No SQLite backup at $src.sqlite"; cp -p "$src.sqlite" "$name";;
        mysql|mariadb|"")
            [ -s "$src.sql.gz" ] || die "No database dump at $src.sql.gz"
            command -v mysql >/dev/null 2>&1 || die "mysql client is not available in this shell."
            say "   database: $name"
            set -o pipefail
            local pw h p u
            pw="$(env_get DB_PASSWORD)"; h="$(env_get DB_HOST)"; p="$(env_get DB_PORT)"; u="$(env_get DB_USERNAME)"
            # A restore must give back the state of the backup. Tables that only exist AFTER the backup (created by the
            # migrations of the release) are not in the dump and would make the next deploy fail ("table already exists"),
            # so this database's own tables are emptied first (same database, same prompt that already warned about it).
            { echo 'SET FOREIGN_KEY_CHECKS=0;'
              MYSQL_PWD="$pw" mysql -N -h "$h" -P "$p" -u "$u" "$name" -e 'show tables' | sed 's/.*/DROP TABLE IF EXISTS `&`;/'
              echo 'SET FOREIGN_KEY_CHECKS=1;'; } | MYSQL_PWD="$pw" mysql -h "$h" -P "$p" -u "$u" "$name" || die "Could not empty the database before the restore."
            gunzip -c "$src.sql.gz" | MYSQL_PWD="$pw" mysql -h "$h" -P "$p" -u "$u" --default-character-set=utf8mb4 "$name" \
                || die "Database restore failed."
            ;;
        *) die "Unsupported DB_CONNECTION ($conn).";;
    esac
}

# Framework cache rebuild + permissions on this app's runtime folders only.
rebuild_caches() {
    "$PHP" artisan optimize:clear
    "$PHP" artisan config:cache
    "$PHP" artisan route:cache
    "$PHP" artisan view:cache
    chmod -R ug+rwX "$APP/storage" "$APP/bootstrap/cache"
}
