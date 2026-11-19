set -e
set -o pipefail
# Runs ON THE SERVER (piped over ssh). READ-ONLY on the live site: only SELECTs via mysqldump --single-transaction and
# a file copy FROM live. Every write goes into /home/skylinkictcom/staging.skylinkict.com or the staging database.
LIVE=/home/skylinkictcom/s.skylinkict.com
S=/home/skylinkictcom/staging.skylinkict.com
envget() { # envget <file> <KEY>  (never printed by callers)
  local v; v="$(grep -E "^[[:space:]]*$2=" "$1" | tail -n1)"; v="${v#*=}"; v="${v%$'\r'}"
  case "$v" in \"*\") v="${v:1:${#v}-2}";; \'*\') v="${v:1:${#v}-2}";; esac; printf '%s' "$v"
}
LDB="$(envget $LIVE/.env DB_DATABASE)"; SDB="$(envget $S/.env DB_DATABASE)"
[ "$SDB" = "skylinkictcom_staging" ] || { echo "staging .env does not point at skylinkictcom_staging; stopping"; exit 1; }
[ "$LDB" != "$SDB" ] || { echo "live and staging database are the same; stopping"; exit 1; }
echo "from (read only): database $LDB      into: database $SDB, file $S/deploy/live-data.sql.gz"
MYSQL_PWD="$(envget $LIVE/.env DB_PASSWORD)" mysqldump -h "$(envget $LIVE/.env DB_HOST)" -P "$(envget $LIVE/.env DB_PORT)" -u "$(envget $LIVE/.env DB_USERNAME)" \
  --single-transaction --quick --routines --no-tablespaces --default-character-set=utf8mb4 "$LDB" | gzip > "$S/deploy/live-data.sql.gz"
gunzip -c "$S/deploy/live-data.sql.gz" | tail -n 2 | grep -q 'Dump completed' || { echo "dump incomplete"; exit 1; }
ls -l "$S/deploy/live-data.sql.gz" | awk '{print "dump size:", $5}'
TABLES_BEFORE=$(MYSQL_PWD="$(envget $S/.env DB_PASSWORD)" mysql -N -h 127.0.0.1 -u "$(envget $S/.env DB_USERNAME)" "$SDB" -e 'show tables' | wc -l)
[ "$TABLES_BEFORE" = "0" ] || { echo "staging database is not empty ($TABLES_BEFORE tables); stopping"; exit 1; }
gunzip -c "$S/deploy/live-data.sql.gz" | MYSQL_PWD="$(envget $S/.env DB_PASSWORD)" mysql -h 127.0.0.1 -u "$(envget $S/.env DB_USERNAME)" --default-character-set=utf8mb4 "$SDB"
echo "imported. tables in staging: $(MYSQL_PWD="$(envget $S/.env DB_PASSWORD)" mysql -N -h 127.0.0.1 -u "$(envget $S/.env DB_USERNAME)" "$SDB" -e 'show tables' | wc -l)"
# uploads: copy FROM live (read) INTO staging (write)
mkdir -p "$S/storage/app/public"
cp -a "$LIVE/storage/app/public/." "$S/storage/app/public/"
echo "uploads copied: $(find "$S/storage/app/public" -type f | wc -l) files, $(du -sh "$S/storage/app/public" | cut -f1)"
