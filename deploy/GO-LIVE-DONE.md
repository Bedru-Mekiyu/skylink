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

## Update 2026-11-21 (evening): admin login, gallery, marketing card
- **Backup first:** `deploy/backups/pre-admin-gallery-20261121-201123/` (database dump, `storage/app` tar.gz, `.env` copy, SHA256SUMS). Roll back: `cd /home/skylinkictcom/s.skylinkict.com && . deploy/server-common.sh && db_restore <that folder>/db-*.sql.gz`; uploads: `tar -xzf <folder>/storage-app-*.tar.gz -C storage`; `.env`: `cp -p <folder>/env.pre-admin .env && php artisan config:cache`.
- **Login problem:** settings were correct (SESSION_DOMAIN null, driver database, APP_URL apex, no errors in laravel.log). Causes: the admin password was not known to the owner, and one login throttle timer was still active. Only the login throttle rows in the `cache` table were cleared.
- **Admin account:** id 1, `info@skylinkict.com`, is_admin. A new random password was set with `php artisan admin:set-password` (shown once in the terminal, stored nowhere). Change it at Admin > My Profile right after logging in (page exists: admin-only, CSRF, current password required, now with an audit log line `admin password changed`).
- `.env`: LOG_LEVEL=notice (so audit lines are written), TEAM_SHOW_PLACEHOLDER=true (the one allowed staging-style flag; remove it when the real photo replaces `public/assets/images/about/head-of-marketing.jpg`, then redeploy and `php artisan config:cache`). `php artisan team:check-placeholder` reports the placeholder while the flag is on; that is expected.
- **Gallery:** 38 photos imported (15 visible, 23 hidden; agreements, certificates and contract photos are hidden). Sources in `storage/app/private/gallery-import-src` (read only). Undo: `php artisan gallery:import-site-photos --rollback`. Review in Admin > Gallery.
- Files uploaded outside the zip: `app/Console/Commands/SetAdminPassword.php`, `app/Http/Controllers/Admin/ProfileController.php` (both in git).
- **Read-only findings:** `auto_prepend_file = "/home/skylinkictcom/cpanel.php"` is in `.user.ini` and `php.ini` of `public_html` and of `s.skylinkict.com/public`, but `/home/skylinkictcom/cpanel.php` does not exist (no errors are logged for it). `AddType application/x-httpd-php .html` is in `public_html/.htaccess` (line 55) and `s.skylinkict.com/public/.htaccess` (line 64): any `.html` file runs as PHP (risk: PHP code hidden in an uploaded `.html` would execute). Safe fix, not applied: delete that line from those two files (only if no site relies on PHP inside `.html`). `deploy/backups` is not reachable (301 then 404); `.env` returns 403.
- **Download the backups:** `scp -r -i <key> skylinkictcom@91.204.209.13:/home/skylinkictcom/s.skylinkict.com/deploy/backups ./skylink-backups` (or cPanel File Manager > compress > download).
- **Bing image:** product id 6 "Learning Management System (LMS)" points to a dead Bing address. Fix in Admin > Products > edit > upload an image, or run `php artisan products:fix-lms-image`. Products 5 and 20 also hotlink Bing thumbnails (work today, fragile).
- **Not changed:** other admin pages work (200). The 404 page is the plain default "Not Found" (a branded 404 is a design decision for you).

## Update 2026-11-22: Ethiopian phone format and SEO
**Releases on live:** 505d5c2c (phone format), then 01adea35 (SEO). Backups made by `server-deploy.sh`: `deploy/backups/20261121-204827` (before phone) and `deploy/backups/20261121-211647` (before SEO, also holds `public_html.htaccess.pre-seo`). Rollback: `bash deploy/server-rollback.sh <backup folder>`; apex rule: `cp -p <backup>/public_html.htaccess.pre-seo /home/skylinkictcom/public_html/.htaccess`.
Both were tested locally (447 tests), then deployed to bedru, then live.

### Phone numbers (one formatter: `app/Support/PhoneNumber.php`; numbers from `config/company_contact.php`)
| Place | Before | After |
|---|---|---|
| Nav bar (2 numbers) | `+251-974-967171`, tel link with the raw text | `+251 974 967 171`, `+251 936 177 777`, `tel:+251974967171` |
| Footer | `+251-974-967171`, plain text | `+251 974 967 171`, tap-to-call |
| Contact page card | `+251-974-967171`, plain text | `+251 974 967 171`, tap-to-call |
| Services quote box | `+251-974-967171` | `+251 974 967 171`, tap-to-call |
| Contact launcher | raw config text, copy button copied the raw text | formatted, copy gives `+251974967171` |
| JSON-LD (new, SEO) | none | `telephone: "+251974967171"` |
Digits were not changed. Spaces inside a number are non-breaking and the links use `white-space: nowrap`. Checked at 1440, 1024, 768 and 360 px (nothing wrapped or overflowed; between 992 and 1199 px the nav hides the numbers by design, the footer shows them). There is no Amharic version of the nav/footer. Not changed: an unused placeholder `+251 123 456 789` in `ServiceController` (not shown anywhere). Invalid numbers are shown unchanged and logged as a warning.

### SEO (one mechanism: `config/seo.php` + `app/Support/Seo.php` + `partials/seo.blade.php`)
- Unique titles and descriptions per page (home title is your wording, 61 characters), canonical always `https://skylinkict.com`, og:* and twitter:* tags, default share image `assets/images/og-default.jpg` (1200x630), `og:locale`, `<html lang>` (`am` for Amharic articles), robots `noindex` only on the 404 and on previews.
- JSON-LD: Organization + LocalBusiness (home), NewsArticle (articles), BreadcrumbList (inner pages). Opening hours are not published because they are not in `config/seo.php` (`organization.opening_hours`).
- `sitemap.xml`: pages, news, products, videos, service categories and services with real lastmod; all 129 URLs return 200 (two uncached pages took 16 s on the first request). No per-project portfolio pages exist, so none are listed.
- Real 404 status with a friendly page; `/home`, `/index.php` and `/index.html` give a 301 to `/` (Laravel route and a rule in `public_html/.htaccess` for the apex host). `s.` and `www` still give a 301 with the path kept.
- Alt text added to the logo, article banner, featured news and video thumbnails. Left as they are: partner logos (handled with aria labels), news card thumbnails (decorative next to the headline), product images (no width/height known).
- `php artisan seo:ping` (dry run; `--send` submits the sitemap URLs to Bing IndexNow) needs `INDEXNOW_KEY` in `.env` (the key file is then served at `/<key>.txt`). It never runs by itself.
- `BING_SITE_VERIFICATION` and `GOOGLE_SITE_VERIFICATION` in `.env` add the verification meta tags (empty = no tag).
- Core Web Vitals (Pixel 7, no throttling): home LCP 3.3 s, CLS 0; About LCP 1.9 s, CLS 0. Static files already cache for 7 days and CSS is brotli. No safe small fix was needed, so nothing was changed (hero video engine untouched).
- `test.skylinkict.com`: serves a stock Laravel welcome page from an old separate app, robots allows everything and there is no noindex. NOT touched. Decide: add noindex, or remove the subdomain/redirect it.

### SEO to-do (you)
1. Google Search Console: add `https://skylinkict.com`, verify (or set `GOOGLE_SITE_VERIFICATION` in `.env` and `php artisan config:cache`), submit `https://skylinkict.com/sitemap.xml`.
2. Bing Webmaster Tools: add the site (can import from Search Console), submit the sitemap; for IndexNow set `INDEXNOW_KEY=<8-128 letters/digits>` in `.env`, `php artisan config:cache`, then `php artisan seo:ping` (dry run) and `php artisan seo:ping --send`.
3. Google Business Profile and social profiles: website `https://skylinkict.com`.
4. Decide about `test.skylinkict.com`.
5. Optional: fill `organization.opening_hours` in `config/seo.php` (e.g. `Mo-Fr 08:00-17:00`, `Sa 08:00-12:00`).
