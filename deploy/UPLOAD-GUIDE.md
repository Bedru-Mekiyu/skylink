# Sky Link ICT Solutions: upload guide (exact paths for this server)

Prepared 2026-11-19. Everything here is done **by you** (File Manager, phpMyAdmin, SSH). Nothing was changed on the server while preparing it.

| | |
|---|---|
| Server / account | `skylinkictcom` on `91.204.209.13` (SSH: `ssh -i "$HOME\.ssh\cpanel_bedru" -p 22 skylinkictcom@91.204.209.13`) |
| The Sky Link site (the only thing this guide touches) | App folder `/home/skylinkictcom/s.skylinkict.com`, document root `/home/skylinkictcom/s.skylinkict.com/public` |
| Database | `skylinkictcom_v2` |
| Out of bounds | `/home/skylinkictcom/public_html/Skylink` (old copy) and every other folder in `public_html`, except the two files named in Stage 2 |
| Package files | `deploy/.build/skylink-app.zip` (7.9 MB) and `deploy/.build/skylink-public.zip` (168 MB), checksums in `deploy/.build/*.sha256`, file lists in `deploy/.build/*.files.txt` |
| Built from | commit `0f1f346` (HEAD `01a483c` + the small `ASSET_URL` change in `config/filesystems.php` + `public/assets/.htaccess`), vendor built for PHP 8.2 |

---

## URGENT (do these first, 10 minutes)

### U1. Public download found: `https://skylinkict.com/Skylink/vendor.zip` answers **200**

Status codes checked (headers only, nothing downloaded):

| URL | Status |
|---|---|
| `https://skylinkict.com/Skylink/.env` | 403 (blocked, good) |
| `https://skylinkict.com/Skylink/.git/config` | 403 (blocked, good) |
| `https://skylinkict.com/Skylink/vendor.zip` | **200 (anyone can download it)** |
| `https://s.skylinkict.com/.env` | 403 (good) |
| `https://s.skylinkict.com/storage/logs/laravel.log` | 403 (good) |

A file called `vendor.zip` normally holds only PHP libraries (it is an archive of the `vendor/` folder next to it), so it is a leak of library versions, not of passwords. I did not download it, so I cannot promise it has no `.env`. Pick one:

1. **Best:** File Manager, open `/home/skylinkictcom/public_html/Skylink/`, delete `vendor.zip` (the extracted `vendor/` folder is already there).
2. **Or block it:** File Manager, folder `/home/skylinkictcom/public_html/Skylink/`, New File `.htaccess` (the folder has none), content:
   ```apache
   <FilesMatch "\.(zip|sql|bak|log|env)$">
       Require all denied
   </FilesMatch>
   ```
3. If you know that zip contains a `.env`, also change the database password of `skylink_laravel`: cPanel > Databases > MySQL Databases > Current Users > Change Password, then update `DB_PASSWORD` in `/home/skylinkictcom/public_html/Skylink/.env`.

Why the other two blocked paths (`.env`, `.git`) show 403: the server already denies dot-files. Good, leave it.

### U2. The live site runs in debug mode

`/home/skylinkictcom/s.skylinkict.com/.env` currently has `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost`. In debug mode an error page can show passwords and paths. Change now (File Manager > Edit):

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://s.skylinkict.com
```
(`APP_DEBUG` appears twice in that file; set **both** lines to `false`.) Then run in SSH: `cd /home/skylinkictcom/s.skylinkict.com && php artisan config:clear`.

### U3. Other things I found (not urgent)

- `https://skylinkict.com/` answers a redirect to `http://s.skylinkict.com/` (plain http first). Stage 2 fixes the address; Stage 1 changes nothing there.
- `/home/skylinkictcom/s.skylinkict.com/` contains `app.zip` (395 MB), `routes.zip` (168 MB) and `node_modules/`. They are outside the document root (not downloadable). See the optional clean-up step 12.
- The server's cron list has **no** line for this site, so scheduled news, queued mail and the monthly clean-up never run. Step 9 adds it.

---

## STAGE 1: deploy the new version to s.skylinkict.com (the site keeps its address)

Finish and check this stage before starting Stage 2. Choose a quiet time; the site is in maintenance mode for a few minutes.

### Before you start: files that exist only on the server

`deploy/SERVER-DIFFS.txt` lists the files on the server whose content is not in the project history (checked read-only, line endings ignored): **44 files will be overwritten** (controllers, views, `routes/web.php`, `bootstrap/app.php`, `composer.json` ...) and **7 old files** (`ContactController`, `MessageController`, `ServiceRequestController`, `ContactFormMail`, `UserMessage` and two e-mail views) exist only on the server and are **left untouched** (unused after the update). If somebody edited the live site by hand on purpose, tell me before step 3. The backup in step 1 keeps every old file. The server's `public/favicon.ico` is also different; the package does **not** replace it.

### 1. Backup (do not skip)

1. SSH:
   ```bash
   cd /home/skylinkictcom
   mkdir -p skylink-backups
   tar -czf skylink-backups/skylink-A-before-$(date +%Y%m%d-%H%M).tar.gz --exclude='s.skylinkict.com/node_modules' --exclude='s.skylinkict.com/*.zip' s.skylinkict.com
   ls -lh skylink-backups
   ```
   (This archives only the Sky Link app folder, including its `.env`, `public/` and `storage/app/public` uploads and the current `vendor/`, roughly 250 MB; the server has 197 GB free.)
2. Database: cPanel > Databases > phpMyAdmin > click `skylinkictcom_v2` > **Export** > Method **Custom** > tick **Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER statement** > Go. Save the `.sql` file on your PC and also keep it somewhere safe.
3. Write down the names/sizes of both backups. If either is missing, stop.

### 2. Maintenance mode

```bash
cd /home/skylinkictcom/s.skylinkict.com
php artisan down --retry=60 --secret="pick-one-long-random-word"
```
Visitors now see "503". You can still open `https://s.skylinkict.com/pick-one-long-random-word` in your browser (it sets a cookie that lets you in).

### 3. Upload and extract

1. **Verify your files first** (PowerShell on your PC, folder `deploy\.build`): `Get-FileHash skylink-app.zip -Algorithm SHA256` must equal the line in `skylink-app.zip.sha256`; same for the public zip.
2. **App zip:** File Manager > go to `/home/skylinkictcom/s.skylinkict.com/` > Upload `skylink-app.zip` > select it > **Extract** (extract into this same folder, overwrite is OK) > delete the zip afterwards.
3. **Public zip (168 MB):** File Manager > `/home/skylinkictcom/s.skylinkict.com/public/` > Upload `skylink-public.zip` > Extract > delete the zip.
   If File Manager refuses the size, upload from your PC instead:
   ```powershell
   scp -i "$HOME\.ssh\cpanel_bedru" -P 22 deploy\.build\skylink-public.zip skylinkictcom@91.204.209.13:/home/skylinkictcom/s.skylinkict.com/public/
   ```
   then in SSH: `cd /home/skylinkictcom/s.skylinkict.com/public && unzip -o skylink-public.zip && rm skylink-public.zip`.
4. **Must not be overwritten (the zips already leave these out):**
   - `/home/skylinkictcom/s.skylinkict.com/.env`
   - `/home/skylinkictcom/s.skylinkict.com/storage/app/public/*` (uploaded news, portfolio, service and video images)
   - `/home/skylinkictcom/s.skylinkict.com/public/.user.ini` and `public/php.ini` (cPanel-managed)
   - `/home/skylinkictcom/s.skylinkict.com/public/favicon.ico`, `public/.well-known/`, the `public/storage` link
   - the 7 server-only files listed in `deploy/SERVER-DIFFS.txt`
5. Check: `ls /home/skylinkictcom/s.skylinkict.com/public/.user.ini` still exists, and `ls -l /home/skylinkictcom/s.skylinkict.com/public/storage` still shows the arrow to `.../storage/app/public`.

### 4. `.env`

File Manager > `/home/skylinkictcom/s.skylinkict.com/.env` > Edit.
- Set `APP_ENV=production`, both `APP_DEBUG=false` lines, `APP_URL=https://s.skylinkict.com` (see U2).
- Add the lines from `deploy/.env.additions.txt` (keys the server does not have). Fill in the three `*_INBOX` addresses and set `MAIL_SCHEME` to match your `MAIL_PORT` (465 = `smtps`, 587 = `smtp`).
- Confirm there is **no** `ANNIVERSARY_FORCE` line. Do not add one.
- Do not change `DB_*`, `APP_KEY` or `MAIL_PASSWORD`.

### 5. Run these commands in SSH, in this order

Run them from `/home/skylinkictcom/s.skylinkict.com` (every command works only inside this folder):

```bash
cd /home/skylinkictcom/s.skylinkict.com
php -v | head -1                      # must print PHP 8.2.x (or newer). If it prints 8.1, stop (step 7).
php artisan migrate:status            # read it: the 18 old migrations show "Ran"
php artisan migrate --pretend         # prints the SQL of what would run, changes nothing. Read it.
php artisan migrate --force           # runs the 8 NEW migrations below
php artisan storage:link              # prints "link already exists": fine, nothing changes
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R ug+rwX storage bootstrap/cache
php artisan up
```

The 8 migrations (all additive: new columns and two new tables, no table is dropped):
`2026_11_15_120000_add_soft_deletes_and_duplicate_link_to_news_table`, `2026_11_16_100000_add_publishing_fields_to_news_table`, `2026_11_17_100000_add_image_share_to_news_table`, `2026_11_18_100000_create_inquiries_table`, `2026_11_19_100000_add_showcase_video_to_portfolios_table`, `2026_11_19_100100_add_slug_to_portfolios_table_if_missing`, `2026_11_20_100000_add_card_fields_to_service_categories_table`, `2026_11_21_100000_create_anniversary_settings_table` (the Anniversary admin setting).

If `migrate:status` shows anything other than the 18 old ones as "Ran", or `migrate --pretend` shows a `DROP`, stop and tell me before running `migrate --force`.

### 6. Do NOT run `news:publish-anniversary` now

It publishes the anniversary news article. Run it **only on the ceremony day** (Nov 23, 2026), after the ceremony is live:

```bash
cd /home/skylinkictcom/s.skylinkict.com
php artisan news:publish-anniversary            # add  --feature  to make it the featured story too
```

### 7. cPanel settings (only if needed)

- **PHP version for this domain only:** cPanel > Software > **MultiPHP Manager** > tick only `s.skylinkict.com` > choose **PHP 8.2** (or 8.3) > Apply. Do **not** use the "Set PHP Version for all" box. Note: the live site already answers as PHP 8.2.35 (header `X-Powered-By`) although cPanel's data says ea-php81; the package needs 8.2 or newer.
- **Extensions:** the command-line PHP of this account already has `gd`, `mbstring`, `pdo_mysql`, `fileinfo`, `zip`, `curl`, `openssl`, `tokenizer`, `xml`, `ctype`, `bcmath`, `exif`. GD is needed for image uploads. If the website's PHP lacks one: cPanel > Software > **MultiPHP INI Editor** or **Select PHP Version** > Extensions, for this domain only.

### 8. Check after the upload

Open `https://s.skylinkict.com` (also on your phone):
- [ ] Home, About, Services, Products, Portfolio, News, Contact open, pictures and the hero video show, no error page.
- [ ] A news article and a service page open.
- [ ] Contact form: send one test message (then delete it in the admin inbox).
- [ ] Admin login works. Admin > **Anniversary Ceremony** says **Currently HIDDEN for visitors. Will appear automatically on Nov 23, 2026 at 00:00 (Addis Ababa time)**, mode **Automatic**, Admin preview **not** ticked. The end time on that page is Dec 7, 2026 at 00:00.
- [ ] In a private/incognito window the site shows **no** anniversary intro or banner.
- [ ] Admin Inbox opens; unread count goes down when you open a message.
- [ ] A few other sites on the account still load (this stage did not touch them): `erp`, `newsite`, `basirah.skylinkict.com`, `skylinkict.com/` (still redirects to s.skylinkict.com, as before).
- [ ] If anything shows a plain "500" page: `cd /home/skylinkictcom/s.skylinkict.com && tail -n 40 storage/logs/laravel.log` (with debug off this is where the reason is).

### 9. Cron job for this site (one line)

cPanel > Advanced > **Cron Jobs** > Add New Cron Job > "Once Per Minute (* * * * *)" > Command:

```
cd /home/skylinkictcom/s.skylinkict.com && /opt/cpanel/ea-php82/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1 # skylink
```
Do not edit or delete any other cron job.

### 10. Rollback of Stage 1

1. `cd /home/skylinkictcom/s.skylinkict.com && php artisan down` (site is safe while you restore).
2. Files: `cd /home/skylinkictcom && tar -xzf skylink-backups/skylink-A-before-<date>.tar.gz` (puts the old files back; extra new files stay but are unused).
3. Database (only if the data is wrong; the 8 migrations only add things, so the old code keeps working): phpMyAdmin > `skylinkictcom_v2` > Import > your `.sql` file from step 1.
4. `php artisan optimize:clear` then `php artisan up`.

### 11. Remove the old backups later

Keep `/home/skylinkictcom/skylink-backups/` for at least two weeks after Stage 2.

### 12. Optional, only after Stage 1 works for a few days

Free about 560 MB: File Manager, delete `/home/skylinkictcom/s.skylinkict.com/app.zip` and `/home/skylinkictcom/s.skylinkict.com/routes.zip` (old upload archives, not used by the site). Leave `node_modules` alone unless you are sure nothing is built on the server.

---

## STAGE 2: switch the address to https://skylinkict.com

Only after Stage 1 is deployed and checked. The idea: the Laravel app stays in `/home/skylinkictcom/s.skylinkict.com`. `public_html` (the folder of skylinkict.com) answers Sky Link **only** for the host `skylinkict.com` and only for URLs that are not an existing file or folder. All other sites in `public_html` keep working.

What the package contains (`deploy/domain-switch/`):

| File | Goes to |
|---|---|
| `skylink-front.php` | `/home/skylinkictcom/public_html/skylink-front.php` (new file; the old `index.php` is **not** touched) |
| `public_html.htaccess.NEW` | replaces `/home/skylinkictcom/public_html/.htaccess` (`public_html.htaccess.diff` shows exactly what changes) |
| `A-public.htaccess.NEW` | replaces `/home/skylinkictcom/s.skylinkict.com/public/.htaccess` (only at step 5; adds the 301) |

Why a different front controller name and an assets prefix (details in Appendix B): `public_html` already has `index.html`, `index.php`, `assets/` (another site's files), `build/`, `storage/` and `robots.txt`. A file named `skylink-front.php` and the prefix `/sl/` for Sky Link's own `assets`, `build` and `storage` avoid every clash.

### Steps

1. **Quiet time + backup.** SSH:
   ```bash
   cp /home/skylinkictcom/public_html/.htaccess /home/skylinkictcom/skylink-backups/public_html.htaccess.bak
   cp /home/skylinkictcom/s.skylinkict.com/public/.htaccess /home/skylinkictcom/skylink-backups/A-public.htaccess.bak
   cp /home/skylinkictcom/s.skylinkict.com/.env /home/skylinkictcom/skylink-backups/A-env.bak
   ```
   Also download `public_html/.htaccess` and `public_html/index.php` to your PC (File Manager > Download).
2. **Create the new files and links.**
   - File Manager > `/home/skylinkictcom/public_html/` > New File `skylink-front.php`, paste the content of `deploy/domain-switch/skylink-front.php` > Save.
   - Replace the content of `/home/skylinkictcom/public_html/.htaccess` with `deploy/domain-switch/public_html.htaccess.NEW`.
   - SSH (these only create new names; `sl` and `favicon.ico` do not exist yet; if `ls` shows they do, stop):
     ```bash
     cd /home/skylinkictcom/public_html
     ls -d sl favicon.ico skylink-front.php 2>&1     # sl and favicon.ico must say "No such file"
     mkdir sl
     ln -s /home/skylinkictcom/s.skylinkict.com/public/assets   sl/assets
     ln -s /home/skylinkictcom/s.skylinkict.com/public/build    sl/build
     ln -s /home/skylinkictcom/s.skylinkict.com/storage/app/public sl/storage
     ln -s /home/skylinkictcom/s.skylinkict.com/public/favicon.ico favicon.ico
     ls -l sl
     ```
3. **`.env` and caches.** Edit `/home/skylinkictcom/s.skylinkict.com/.env`: `APP_URL=https://skylinkict.com` and add `ASSET_URL=https://skylinkict.com/sl`. Then:
   ```bash
   cd /home/skylinkictcom/s.skylinkict.com
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   (`SESSION_DOMAIN=null` stays; it makes cookies belong to the exact host. Everybody who was logged in on s.skylinkict.com has to log in again on skylinkict.com.)
4. **Test checklist** (use a private window):
   - [ ] `https://skylinkict.com/` shows the Sky Link home page (not the old "besirah-react" page, not an error). `curl -sI https://skylinkict.com/ | grep -i x-powered-by` shows PHP 8.2 or newer.
   - [ ] About, Services, Products, Portfolio, News, Contact open; pictures, videos and the logo show; the favicon shows.
   - [ ] `https://www.skylinkict.com/about` jumps to `https://skylinkict.com/about`; `http://skylinkict.com/` jumps to https.
   - [ ] Admin login at `https://skylinkict.com/login`, **Anniversary Ceremony** page says HIDDEN / Automatic; inbox opens; contact form works.
   - [ ] Upload a small image in the admin (e.g. a news cover), see it on the site, then remove it.
   - [ ] **Every other site still loads** (these are the names in `public_html`, which are the ones that could be affected): `skylinkict.com/erp/`, `/newsite/`, `/webpro/`, `/newweb/`, `/readymeal/`, `/hanim/`, `/hanim-admin/`, `/alfurqan/`, `/almathurat/`, `/basirah-app/`, `/privacy-policy/`, `/account-deletion/`, `/attendance-api/`, `/slt/`, and the sites with their own address that live inside it: `basirah.skylinkict.com`, `chapatest.skylinkict.com`, `privacy-policy.skylinkict.com`, `account-deletion` if used, `alfurqan` (open each; the same page as before is expected).
   - [ ] `https://skylinkict.com/Skylink/` is unchanged (old folder, not touched).
5. **Only if all is OK:** enable the 301 from s.skylinkict.com. Replace `/home/skylinkictcom/s.skylinkict.com/public/.htaccess` with `deploy/domain-switch/A-public.htaccess.NEW`. Test: `https://s.skylinkict.com/about` jumps to `https://skylinkict.com/about`; images and `/storage/...` links on old pages still load.
6. **1-minute rollback** (any problem in steps 2 to 4 or 5):
   ```bash
   cp /home/skylinkictcom/skylink-backups/public_html.htaccess.bak /home/skylinkictcom/public_html/.htaccess
   cp /home/skylinkictcom/skylink-backups/A-public.htaccess.bak /home/skylinkictcom/s.skylinkict.com/public/.htaccess
   cd /home/skylinkictcom/public_html
   rm sl/assets sl/build sl/storage favicon.ico skylink-front.php    # NO trailing slash, NO -r: this removes only the links/new file
   rmdir sl
   ```
   Then in `/home/skylinkictcom/s.skylinkict.com/.env` set `APP_URL=https://s.skylinkict.com` and delete the `ASSET_URL` line, and run `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`. `skylinkict.com/` is then back to redirecting to s.skylinkict.com.
7. **After the switch:** Google Search Console (if you use it): add the property `https://skylinkict.com`, use "Change of address" from `s.skylinkict.com`, submit a sitemap if you have one (the site has none yet). Update the address in Google Business, social profiles and the e-mail signature.

### Known limits of the local test

No Apache or LiteSpeed is installed on this PC, and Docker was not running. The Stage 2 rules were therefore proven with an equivalent (`deploy/domain-switch/test/router.php` reproduces every rule in the same order; the real `.htaccess` file was not executed). The Laravel side (routes, assets through `/sl`, admin login, uploads URL) is fully tested. What only the real server can confirm: that LiteSpeed honours the `<Files "skylink-front.php">` PHP 8.2 line, and that symlinks are followed. Step 4 tests both; step 6 undoes everything in a minute.

---

## Appendix A: what the current `public_html/.htaccess` does (read-only analysis)

1. `<IfModule mod_rewrite.c>` block, the standard Laravel rules (left over from an earlier Laravel attempt): no directory listings (`-Indexes`), pass the `Authorization` and `X-XSRF-Token` headers, remove a trailing `/` from non-folders, and **send every URL that is not an existing file or folder to `public_html/index.php`** (for every host). That `index.php` tries to load `skylink/vendor/autoload.php`, which does not exist (the `skylink` folder is empty), so such URLs end in an error today.
2. `RewriteCond %{HTTP_HOST} ^skylinkict\.com$ [OR] ^www\.skylinkict\.com$` + `RewriteRule ^/?$ http://s.skylinkict.com/ [R=301,L]`: **only the exact home URL** `/` of skylinkict.com and www is redirected to s.skylinkict.com. Any other path on skylinkict.com is not redirected.
3. cPanel block "php ini directives" (empty) and `AddType application/x-httpd-php .html`: `.html` files are run through PHP (keep: some sites may rely on it).
4. cPanel handler block: `ea-php81` is the PHP version for everything in `public_html` (keep; other sites rely on it).

The new file keeps rules 1 (headers, trailing slash), 3 and 4 unchanged, removes the s.skylinkict.com redirect (2), and replaces the catch-all with three Sky Link-only rules that match only the hosts `skylinkict.com` and `www.skylinkict.com`: https + non-www redirect, the home page, and the catch-all. One behaviour change for other hosts: a missing URL on another site that lives under `public_html` used to hit the broken `index.php`; now it gets the server's normal 404. A PHP 8.2 handler line is added for `skylink-front.php` only.

Top-level names in `public_html` (names only): `.htaccess`, `.user.ini`, `.well-known`, `.wp-cli`, `Skylink`, `account-deletion`, `alfurqan`, `almathurat`, `assets`, `attendance-api`, 3 `attendance-documentation-*.zip`, `basirah-app`, `basirah.skylinkict.com`, `besirah-app.zip`, `build`, `cgi-bin`, `chapatest.skylinkict.com`, 10 `cwpord_*.php`, `dist`, `erp`, `error_log`, `firebase-messaging-sw.js`, `hanim`, `hanim-admin`, `index.html`, `index.php`, `newsite`, `newweb`, `php.ini`, `privacy-policy`, `readymeal`, `robots.txt`, `skylink`, `slt`, `storage`, `vite.svg`, `webpro`.

## Appendix B: clash table

Sky Link's first URL segments (from `routes/web.php`): `about`, `admin`, `confirm-password`, `contact`, `dashboard`, `email`, `forgot-password`, `inquiry`, `login`, `logout`, `news`, `newsletter`, `password`, `portfolio`, `products`, `profile`, `reset-password`, `sanctum`, `services`, `storage`, `subscribe`, `up`, `verify-email`, `videos`. Sky Link's `public/`: `assets/` (css, images, videos), `build/`, `storage` (link), `.well-known`, `.htaccess`, `favicon.ico`, `index.php`, `robots.txt`.

| Clash | Impact | Safe fix (in the package) |
|---|---|---|
| Route names vs folders in `public_html` | **None**: no name in `public_html` equals a Sky Link route segment (`storage` is the only shared word, see below) | none needed |
| `index.html` (besirah-react) | The home page `/` is a folder, so Apache would serve `index.html` instead of Sky Link | explicit rule `RewriteRule ^$ skylink-front.php` for host skylinkict.com only |
| `index.php` (old, broken) | Cannot be used by Sky Link (wrong paths, needs PHP 8.2) | left alone; the new file is `skylink-front.php` |
| `assets/` (Basirah app files, including `assets/images`) | Sky Link's `assets/css|images|videos` would collide with another site's files; cannot symlink over it | Sky Link's URLs get a prefix: `ASSET_URL=https://skylinkict.com/sl`, so they are `/sl/assets/...`; the other site's `/assets/...` is untouched |
| `build/` (an old Sky Link build from 2025) | `/build/...` is an old copy | Sky Link's build is read from `/sl/build/...` (symlink to the current one) |
| `storage/` (empty folder) | Uploaded images use `/storage/...` | uploads are served from `/sl/storage/...` (`config/filesystems.php` now follows `ASSET_URL`); the empty folder stays |
| `robots.txt` (shared, content `User-agent: * / Disallow:`) | Same content as Sky Link's | the shared file stays; nothing to do |
| `favicon.ico` | Not present in `public_html` | symlink to Sky Link's favicon (step 2) |
| `.well-known/` | Shared (SSL validation) | untouched |
| `cwpord_*.php`, `erp`, `newsite`, ... | Real files/folders are answered first (`!-f`/`!-d`) | unchanged |
| Subdomain sites that live inside `public_html` (`basirah.skylinkict.com`, `chapatest.skylinkict.com`) | They also read `public_html/.htaccess` | every Sky Link rule requires host `skylinkict.com`/`www`; `skylink-front.php` also returns 404 for any other host |

**Hard-coded addresses:** the code has none for `s.skylinkict.com`. Only display text `www.skylinkict.com` in the footer and `info@skylinkict.com` on the contact page. Canonical and share tags are built from the request address. After the switch, check the database for old links (read only): phpMyAdmin > `skylinkictcom_v2` > **Search** tab > words `s.skylinkict.com`, all tables > Go. If rows show up (for example links inside news text), edit those rows in the admin or tell me which table and I will write the exact `UPDATE` after a backup.

**Mail links** (password reset, newsletters) are built from `APP_URL`, so they use the new address once step 3 is done.
