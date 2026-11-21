#!/bin/bash
# Makes https://skylinkict.com the one address. Touches ONLY: public_html/.htaccess (backup exists), the NEW names
# public_html/skylink-front.php, public_html/sl/, public_html/favicon.ico, and s.skylinkict.com/public/.htaccess.
# Run ON THE SERVER after the app is deployed, with the two uploaded files next to this script:
#   SKYLINK_APP_DIR=/home/skylinkictcom/s.skylinkict.com bash golive-apex.sh <backup-folder>
set -e
APP="${SKYLINK_APP_DIR:?set SKYLINK_APP_DIR}"
[ "$APP" = "/home/skylinkictcom/s.skylinkict.com" ] || { echo "refusing: unexpected folder $APP"; exit 1; }
BK="${1:?backup folder}"
PH=/home/skylinkictcom/public_html
G="$APP/deploy/golive"
[ -f "$BK/public_html-files/.htaccess" ] || { echo "backup of the apex .htaccess missing"; exit 1; }
[ -f "$G/public_html.htaccess.NEW" ] && [ -f "$G/skylink-front.php" ] || { echo "uploaded files missing in $G"; exit 1; }

echo "== new names must not exist yet (or already be ours)"
for n in sl favicon.ico skylink-front.php; do
    if [ -e "$PH/$n" ] || [ -L "$PH/$n" ]; then echo "  exists: $n"; fi
done
cd "$PH"
[ -e sl ] || mkdir sl
[ -L sl/assets ]  || ln -s "$APP/public/assets"       sl/assets
[ -L sl/build ]   || ln -s "$APP/public/build"        sl/build
[ -L sl/storage ] || ln -s "$APP/storage/app/public"  sl/storage
{ [ -e favicon.ico ] || [ -L favicon.ico ]; } || ln -s "$APP/public/favicon.ico" favicon.ico
cp "$G/skylink-front.php" skylink-front.php.new && mv skylink-front.php.new skylink-front.php
echo "== apex .htaccess (the old one is saved in $BK)"
cp "$G/public_html.htaccess.NEW" .htaccess.golive.tmp && mv .htaccess.golive.tmp .htaccess
echo "== s.skylinkict.com -> https://skylinkict.com (301, path and query kept; assets/build/storage keep answering)"
SH="$APP/public/.htaccess"
if ! grep -q 'STAGE 2: send s.skylinkict.com' "$SH"; then
    awk '{print} /^[[:space:]]*RewriteEngine On/ && !done {print "    # STAGE 2: send s.skylinkict.com to https://skylinkict.com (301). Asset, build and upload URLs stay answered here."; print "    RewriteCond %{HTTP_HOST} ^s\\.skylinkict\\.com$ [NC]"; print "    RewriteCond %{REQUEST_URI} !^/(assets|build|storage)/"; print "    RewriteRule ^(.*)$ https://skylinkict.com/$1 [L,R=301]"; done=1}' "$SH" > "$SH.new" && mv "$SH.new" "$SH"
fi
grep -c 'STAGE 2: send s.skylinkict.com' "$SH"
echo "ROLLBACK:  cp -p $BK/public_html-files/.htaccess $PH/.htaccess ; cp -p $BK/public.htaccess.pre-golive $SH ; then in $APP/.env set APP_URL=https://s.skylinkict.com and remove ASSET_URL, php artisan config:clear"
