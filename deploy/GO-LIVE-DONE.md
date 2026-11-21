# Go-live record (2026-11-21)

Live site: https://skylinkict.com is the one address. `www.` and `s.` give a 301 to it (path and query kept), http gives a 301 to https.

## What was changed on the server
| Where | Change |
|---|---|
| `/home/skylinkictcom/s.skylinkict.com` (app) | release 3535219c extracted over the old files; 13 additive migrations; `.env` set to production (see below); caches rebuilt |
| `public_html/.htaccess` | old redirect of the apex to `s.` removed; Sky Link rules for the hosts `skylinkict.com`/`www` added (other sites untouched) |
| `public_html/` new names | `skylink-front.php`, `sl/` (3 symlinks), `favicon.ico` (symlink); `robots.txt` replaced (old copy in the backup) |
| `s.skylinkict.com/public/.htaccess` | 301 to https://skylinkict.com (assets/build/storage URLs keep answering) |

`.env` keys set: APP_NAME, APP_ENV=production, APP_DEBUG=false, APP_URL=https://skylinkict.com, ASSET_URL=https://skylinkict.com/sl, COMPANY_EMAIL, ANNIVERSARY_START/END, QUEUE_CONNECTION=sync, LOG_LEVEL=warning. Removed if present: MAIL_STAGING_GUARD, MAIL_STAGING_*, ANNIVERSARY_FORCE, TEAM_SHOW_PLACEHOLDER. A copy `.env.before-golive.<time>` stays next to `.env`.

## Backups (non-public, sha256 in SHA256SUMS)
- `deploy/backups/pre-golive-20261121-193242/` : whole folder tar.gz, database dump, apex `.htaccess`/`.user.ini`/`index.php`/`robots.txt`, `.env`.
- `deploy/backups/20261121-194240/` : made by `server-deploy.sh` (files the release overwrote + database dump).

## Roll back (minutes)
```
# 1. apex back to the old behaviour
B=/home/skylinkictcom/s.skylinkict.com/deploy/backups/pre-golive-20261121-193242
cp -p $B/public_html-files/.htaccess /home/skylinkictcom/public_html/.htaccess
cp -p $B/public_html-files/robots.txt /home/skylinkictcom/public_html/robots.txt
cp -p $B/public.htaccess.pre-golive /home/skylinkictcom/s.skylinkict.com/public/.htaccess
# 2. app and database back
cd /home/skylinkictcom/s.skylinkict.com && bash deploy/server-rollback.sh /home/skylinkictcom/s.skylinkict.com/deploy/backups/20261121-194240
cp -p .env.before-golive.<time> .env && php artisan optimize:clear
```

## Owner to-do
1. Put `MAIL_PASSWORD` (and check MAIL_HOST/PORT/USERNAME/MAIL_FROM_ADDRESS/MAIL_FROM_NAME) in the live `.env` yourself, then `php artisan config:cache`. Until then enquiries are saved but no e-mail is sent (SMTP 535 in laravel.log).
2. Set your admin password: `cd /home/skylinkictcom/s.skylinkict.com && php artisan admin:set-password <your admin email>` (hidden prompt).
3. Tawk.to: add `skylinkict.com` to the property's allowed domains (the widget script is blocked from the new address).
4. DNS: add TXT `_dmarc` = `v=DMARC1; p=none; rua=mailto:info@skylinkict.com; fo=1`.
5. Google Search Console: add `https://skylinkict.com`, submit `/sitemap.xml`; update Google Business and social links.
6. Admin > Portfolio: pick the banner clip for a project (none is chosen yet, so no banner video shows).
7. Add a cron line for this site if you want scheduled news publishing (`php artisan schedule:run`).
8. Remove old backups holding real data when you are sure they are not needed.
9. Admin > Anniversary Ceremony: switch Off any time; it is Automatic until 2026-12-21 00:00 (Addis).
