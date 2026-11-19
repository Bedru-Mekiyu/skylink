# SUPERSEDED: use deploy/UPLOAD-GUIDE.md (exact paths for this server). Kept for reference only.
#!/usr/bin/env bash
# Commands to run ON THE SERVER (cPanel > Terminal, or SSH) after the release files are in place.
# Not run automatically. Replace APP with the folder that holds artisan, e.g. /home/skylinkictcom/skylink
#
#   bash server-commands.sh before   -> backup + maintenance mode ON
#   bash server-commands.sh after    -> (after the owner OKs the migration) migrate + caches + permissions + maintenance OFF
#
# Never touches .env or storage/app (uploads). Never runs news:publish-anniversary.
set -euo pipefail
APP="${APP:-$HOME/skylink}"
PHP="${PHP:-php}"
cd "$APP"

case "${1:-}" in
before)
  mkdir -p "$HOME/backups"
  STAMP=$(date +%Y%m%d-%H%M%S)
  # Database dump: reads DB_* from .env without printing them.
  set -a; . ./.env; set +a
  mysqldump --single-transaction --no-tablespaces -h "${DB_HOST:-localhost}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$HOME/backups/db-$STAMP.sql.gz"
  # Code and uploads backup, kept outside the web root.
  tar --exclude='./storage/logs' -czf "$HOME/backups/app-$STAMP.tar.gz" -C "$APP" .
  ls -lh "$HOME/backups" | tail -n 4
  $PHP artisan down --secret="${BYPASS_SECRET:?set BYPASS_SECRET to a long random word first}" --retry=60
  ;;
after)
  $PHP artisan migrate --force
  [ -L public/storage ] || $PHP artisan storage:link
  $PHP artisan optimize:clear
  $PHP artisan config:cache
  $PHP artisan route:cache
  $PHP artisan view:cache
  chmod -R ug+rwX storage bootstrap/cache
  $PHP artisan up
  $PHP artisan about --only=environment | head -n 12
  ;;
*)
  echo "usage: bash server-commands.sh before|after"; exit 1 ;;
esac
