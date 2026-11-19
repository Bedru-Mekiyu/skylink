set -e
# Runs ON THE SERVER (piped over ssh). Creates ONLY the staging database + user and writes the DB lines into the
# staging .env. The generated password is never printed.
S=/home/skylinkictcom/staging.skylinkict.com
DB=skylinkictcom_staging; U=skylinkictcom_stg
[ -d "$S" ] || { echo "staging folder missing"; exit 1; }
[ ! -e "$S/.env" ] || { echo ".env already exists in staging; stopping"; exit 1; }
if uapi Mysql list_databases | grep -q "$DB"; then echo "db exists; stopping"; exit 1; fi
PW=$(openssl rand -hex 24)
uapi Mysql create_database name=$DB | grep -E "^  status:|^  errors:"
uapi Mysql create_user name=$U password="$PW" | grep -E "^  status:|^  errors:"
uapi Mysql set_privileges_on_database user=$U database=$DB privileges=ALL | grep -E "^  status:|^  errors:"
umask 077
{ echo "DB_CONNECTION=mysql"; echo "DB_HOST=127.0.0.1"; echo "DB_PORT=3306"; echo "DB_DATABASE=$DB"; echo "DB_USERNAME=$U"; echo "DB_PASSWORD=$PW"; } > "$S/.env"
ls -l "$S/.env" | awk '{print $1, $NF}'
