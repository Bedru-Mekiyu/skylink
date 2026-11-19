set -e
# Runs ON THE SERVER (piped over ssh; the staging password arrives in the PW variable, see the command in the guide). Writes ONLY inside
# /home/skylinkictcom/staging.skylinkict.com and sets PHP 8.2 for staging.skylinkict.com only.
S=/home/skylinkictcom/staging.skylinkict.com
[ -d "$S/public" ] || { echo "staging folder missing"; exit 1; }
uapi LangPHP php_set_vhost_versions vhost=staging.skylinkict.com version=ea-php82 | grep -E "^  status:|^  errors:"
umask 022
HASH=$(printf '%s' "$PW" | openssl passwd -apr1 -stdin)
printf 'staging:%s\n' "$HASH" > "$S/.htpasswd"
cat > "$S/.htaccess" <<EOF
# Staging only: password + no indexing. Lives ABOVE public/ so a deploy never overwrites it.
AuthType Basic
AuthName "Sky Link staging"
AuthUserFile $S/.htpasswd
Require valid-user
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow"
</IfModule>
EOF
printf 'User-agent: *\nDisallow: /\n' > "$S/public/robots.txt"
ls -la "$S" | sed -n '1,20p'
