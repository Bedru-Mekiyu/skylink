#!/bin/sh
# DISABLED 2026-11-21: this old script put a Basic Auth password on the whole test site. Public pages must stay open to everyone;
# only /admin is protected (by the app login). It now refuses to run.
echo "02-lockdown.sh is DISABLED: public pages must not have a password."; exit 1
# ---- old content below, kept only for history ----
set -e
# Runs ON THE SERVER (piped over ssh; the staging password arrives in the PW variable, see the command in the guide). Writes ONLY inside
# /home/skylinkictcom/bedru.skylinkict.com and sets PHP 8.2 for bedru.skylinkict.com only.
S=/home/skylinkictcom/bedru.skylinkict.com
[ -d "$S/public" ] || { echo "staging folder missing"; exit 1; }
uapi LangPHP php_set_vhost_versions vhost=bedru.skylinkict.com version=ea-php82 | grep -E "^  status:|^  errors:"
umask 022
HASH=$(printf '%s' "$PW" | openssl passwd -apr1 -stdin)
printf 'staging:%s\n' "$HASH" > "$S/.htpasswd"
cat > "$S/.htaccess" <<EOF
# Staging only: password + no indexing. Lives ABOVE public/ so a deploy never overwrites it.
AuthType Basic
AuthName "Sky Link bedru test"
AuthUserFile $S/.htpasswd
Require valid-user
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow"
</IfModule>
EOF
printf 'User-agent: *\nDisallow: /\n' > "$S/public/robots.txt"
printf "Sky Link pre-deployment test site (bedru), created %s. Contact Bedru before removing.\n" "$(date +%F)" > "$S/README-DO-NOT-DELETE.txt"
ls -la "$S" | sed -n '1,20p'
