set -e
# Runs ON THE SERVER (piped over ssh). Reuses the existing staging database + user (skylinkictcom_staging / skylinkictcom_stg),
# resets ONLY that staging user's password (the old .env was lost) and writes the DB lines into the bedru .env.
# The password is never printed.
S=/home/skylinkictcom/bedru.skylinkict.com
DB=skylinkictcom_staging; U=skylinkictcom_stg
[ -d "$S" ] || { echo "bedru folder missing"; exit 1; }
[ ! -e "$S/.env" ] || { echo ".env already exists in bedru; stopping"; exit 1; }
uapi Mysql list_databases | grep -q "$DB" || { echo "staging database missing"; exit 1; }
PW=$(openssl rand -hex 24)
uapi Mysql set_password user=$U password="$PW" | grep -E "^  status:|^  errors:"
uapi Mysql set_privileges_on_database user=$U database=$DB privileges=ALL | grep -E "^  status:|^  errors:"
umask 077
{ echo "DB_CONNECTION=mysql"; echo "DB_HOST=127.0.0.1"; echo "DB_PORT=3306"; echo "DB_DATABASE=$DB"; echo "DB_USERNAME=$U"; echo "DB_PASSWORD=$PW"; } > "$S/.env"
ls -l "$S/.env" | awk '{print $1, $NF}'