set -e
# Runs ON THE SERVER (piped over ssh). Writes ONLY into /home/skylinkictcom/bedru.skylinkict.com/.env.
# Copies the live site's SMTP settings (host, port, username, password, sender) from the live .env WITHOUT printing them
# (read-only on live), points the company inbox at info@skylinkict.com and turns the test-site mail guard on.
# TESTER (env, optional): the one non-company address bedru may email. Empty = real sending stays OFF (MAIL_MAILER=log).
LIVE=/home/skylinkictcom/s.skylinkict.com/.env
B=/home/skylinkictcom/bedru.skylinkict.com/.env
COMPANY="${COMPANY:-info@skylinkict.com}"
[ -f "$LIVE" ] && [ -f "$B" ] || { echo "env file missing"; exit 1; }
getline() { grep -E "^[[:space:]]*$1=" "$LIVE" | tail -n1 | tr -d '\r'; }   # the whole line, never echoed
HOST_L="$(getline MAIL_HOST)"; PORT_L="$(getline MAIL_PORT)"; USER_L="$(getline MAIL_USERNAME)"; PASS_L="$(getline MAIL_PASSWORD)"
[ -n "$HOST_L" ] && [ -n "$USER_L" ] && [ -n "$PASS_L" ] || { echo "live .env has no complete MAIL_HOST/USERNAME/PASSWORD: stopping, ask the owner for SMTP details"; exit 1; }
PORT="${PORT_L#*=}"; PORT="${PORT//\"/}"; PORT="${PORT//\'/}"; [ -n "$PORT" ] || PORT=465
SCHEME=smtp; [ "$PORT" = "465" ] && SCHEME=smtps
cp -p "$B" "$B.before-mail"            # a copy inside the bedru folder, to undo this step
sed -i -e '/^MAIL_/d' -e '/^CONTACT_INBOX=/d' -e '/^QUOTE_INBOX=/d' -e '/^SUPPORT_INBOX=/d' -e '/^QUEUE_CONNECTION=/d' -e '/^CONTACT_CONFIRM_VISITOR=/d' "$B"
{
  if [ -n "$TESTER" ]; then echo "MAIL_MAILER=smtp"; else echo "MAIL_MAILER=log"; fi
  echo "$HOST_L"; echo "MAIL_PORT=$PORT"; echo "MAIL_SCHEME=$SCHEME"; echo "$USER_L"; echo "$PASS_L"
  echo "MAIL_FROM_ADDRESS=\"$COMPANY\""
  echo 'MAIL_FROM_NAME="Sky Link Technologies"'
  echo "CONTACT_INBOX=$COMPANY"
  echo "CONTACT_CONFIRM_VISITOR=true"
  echo "QUEUE_CONNECTION=sync"
  echo "MAIL_STAGING_GUARD=true"
  echo "MAIL_STAGING_ALLOWLIST=${TESTER}"
  echo "MAIL_STAGING_REDIRECT=${TESTER}"
} >> "$B"
chmod 600 "$B"
cd /home/skylinkictcom/bedru.skylinkict.com && php artisan config:clear >/dev/null 2>&1 && php artisan config:cache >/dev/null 2>&1
echo "mail settings written to bedru .env (values not shown)."
echo "mailer=$(grep -E '^MAIL_MAILER=' "$B" | cut -d= -f2)  port=$PORT scheme=$SCHEME  company=$COMPANY  guard=on  tester=${TESTER:-(none yet)}"
echo "smtp host/user/password present: $(grep -c -E '^MAIL_(HOST|USERNAME|PASSWORD)=' "$B") of 3"
