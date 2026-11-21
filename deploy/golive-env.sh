#!/bin/bash
# Sets the LIVE .env to production values (only these keys; nothing is printed except key names; secrets are never shown).
#   SKYLINK_APP_DIR=/home/skylinkictcom/s.skylinkict.com bash golive-env.sh
set -e
APP="${SKYLINK_APP_DIR:?set SKYLINK_APP_DIR}"
[ "$APP" = "/home/skylinkictcom/s.skylinkict.com" ] || { echo "refusing: unexpected folder $APP"; exit 1; }
ENVF="$APP/.env"
[ -f "$ENVF" ] || { echo ".env missing"; exit 1; }
cp -p "$ENVF" "$ENVF.before-golive.$(date +%Y%m%d-%H%M%S)"

setkv() { # setkv KEY VALUE : replace every line of KEY (dedupe), or append
    local k="$1" v="$2" tmp
    tmp="$(mktemp)"
    grep -v -E "^[[:space:]]*${k}=" "$ENVF" > "$tmp" || true
    printf '%s=%s\n' "$k" "$v" >> "$tmp"
    cat "$tmp" > "$ENVF"; rm -f "$tmp"
    echo "  set $k"
}
delkey() { local tmp; tmp="$(mktemp)"; grep -v -E "^[[:space:]]*$1=" "$ENVF" > "$tmp" || true; cat "$tmp" > "$ENVF"; rm -f "$tmp"; echo "  removed $1 (if it existed)"; }

setkv APP_NAME '"Sky Link Technologies"'
setkv APP_ENV production
setkv APP_DEBUG false
setkv APP_URL https://skylinkict.com
setkv ASSET_URL https://skylinkict.com/sl
setkv COMPANY_EMAIL info@skylinkict.com
setkv ANNIVERSARY_START '"2026-11-21 00:00:00"'
setkv ANNIVERSARY_END '"2026-12-21 00:00:00"'
setkv QUEUE_CONNECTION sync   # no cron job for this site yet: mail goes out while the visitor's request runs
setkv LOG_LEVEL warning
# staging-only settings must never be on the live site
for k in MAIL_STAGING_GUARD MAIL_STAGING_ALLOWLIST MAIL_STAGING_REDIRECT ANNIVERSARY_FORCE TEAM_SHOW_PLACEHOLDER; do delkey "$k"; done
chmod 600 "$ENVF"
echo "keys now: $(grep -E '^(APP_ENV|APP_DEBUG|APP_URL|ASSET_URL|QUEUE_CONNECTION|COMPANY_EMAIL|ANNIVERSARY_START|ANNIVERSARY_END)=' "$ENVF" | tr '\n' ' ')"
