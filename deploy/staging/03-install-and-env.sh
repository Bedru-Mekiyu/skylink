set -e
# Runs ON THE SERVER (piped over ssh). Writes ONLY inside /home/skylinkictcom/bedru.skylinkict.com:
# first install (unzip the two zips) + the rest of the staging .env. Prints no secret.
S=/home/skylinkictcom/bedru.skylinkict.com
cd "$S"
[ "$(pwd -P)" = "$S" ] || { echo "wrong folder"; exit 1; }
[ -f deploy/skylink-app.zip ] && [ -f .env ] || { echo "kit or .env missing"; exit 1; }
[ ! -f artisan ] || { echo "already installed; stopping"; exit 1; }
(cd deploy && sha256sum -c skylink-app.zip.sha256 skylink-public.zip.sha256)
echo "target: $S  (app zip) and $S/public (public zip)"
unzip -o -q deploy/skylink-app.zip -d "$S"
unzip -o -q deploy/skylink-public.zip -d "$S/public"
KEY="base64:$(openssl rand -base64 32)"
{
  echo "APP_NAME=\"Sky Link TEST (bedru)\""
  echo "APP_ENV=production"
  echo "APP_DEBUG=false"
  echo "APP_URL=https://bedru.skylinkict.com"
  echo "APP_KEY=$KEY"
  echo "APP_LOCALE=en"
  echo "APP_FALLBACK_LOCALE=en"
  echo "LOG_CHANNEL=stack"
  echo "LOG_STACK=daily"
  echo "LOG_LEVEL=debug"
  echo "SESSION_DRIVER=database"
  echo "SESSION_LIFETIME=120"
  echo "SESSION_SECURE_COOKIE=true"
  echo "CACHE_STORE=database"
  echo "QUEUE_CONNECTION=sync"
  echo "FILESYSTEM_DISK=local"
  echo "MAIL_MAILER=log"
  echo "MAIL_FROM_ADDRESS=staging@skylinkict.com"
  echo "MAIL_FROM_NAME=\"Sky Link TEST (bedru)\""
  echo "CONTACT_CONFIRM_VISITOR=true"
  echo "CONTACT_RETENTION_MONTHS=24"
  echo "NEWS_ADMIN_TIMEZONE=Africa/Addis_Ababa"
  echo "NEWS_DISPLAY_TIMEZONE=Africa/Addis_Ababa"
} >> .env
chmod 600 .env
echo "installed. env keys: $(grep -oE '^[A-Z_]+=' .env | tr -d '=' | tr '\n' ' ')"
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|MAIL_MAILER)=' .env
grep -c '^ANNIVERSARY_FORCE' .env || true
