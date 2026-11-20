# Sky Link: Deploy Day checklist

Live site: `https://s.skylinkict.com`. App folder `/home/skylinkictcom/s.skylinkict.com`, document root `/home/skylinkictcom/s.skylinkict.com/public`, database `skylinkictcom_v2`, account `skylinkictcom` on `91.204.209.13`.
Nothing here deletes anything on the server. The scripts work only inside the app folder.

The ceremony **stays hidden after the deploy** (mode Off) until an admin turns it on.

---

## 1. Before (on your PC)

1. Build a fresh package (one command, from the project folder; it runs the tests, builds, and verifies the package itself):
   ```powershell
   powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1
   ```
   It must end with `RELEASE VERIFIED` and `DONE`. If it stops, do not deploy; tell me what it printed.
2. Open `deploy\.build\release\RELEASE.txt`: check the commit, the date, `tests: ... passed`, the PHP line (`PHP_VERSION_ID >= 80200`) and the 8 new migrations.
3. Make a **cPanel full backup** (cPanel > Files > Backup > Download a Full Account Backup). The deploy script also backs up the files it overwrites and the database, but this is your safety net.
4. Quiet time. Not between Nov 23 and Dec 7 for Stage 2; Stage 1 (this checklist) is fine any time.

## 2. Upload

Everything in `deploy\.build\release\` goes into **one new folder**: `/home/skylinkictcom/s.skylinkict.com/deploy/` (create the folder `deploy` in File Manager if it is not there):

| File | Goes to |
|---|---|
| `skylink-app.zip`, `skylink-app.zip.sha256` | `/home/skylinkictcom/s.skylinkict.com/deploy/` |
| `skylink-public.zip`, `skylink-public.zip.sha256` (168 MB) | `/home/skylinkictcom/s.skylinkict.com/deploy/` |
| `RELEASE.txt`, `server-deploy.sh`, `server-rollback.sh`, `server-common.sh` | `/home/skylinkictcom/s.skylinkict.com/deploy/` |

The script extracts the zips itself (the app zip into the app folder, the public zip into `public/`). With `scp` (PowerShell, from the project folder):
```powershell
scp -i "$HOME\.ssh\cpanel_bedru" -P 22 deploy\.build\release\* skylinkictcom@91.204.209.13:/home/skylinkictcom/s.skylinkict.com/deploy/
```
(If `deploy/` does not exist on the server yet, create it first in File Manager.)

## 3. Run

```bash
ssh -i "$HOME\.ssh\cpanel_bedru" -p 22 skylinkictcom@91.204.209.13
cd /home/skylinkictcom/s.skylinkict.com
bash deploy/server-deploy.sh
```
It checks the folder and the checksums, backs up (files + database) into `deploy/backups/<date>/`, puts the site in maintenance mode, extracts, shows the pending migrations and **asks `yes`** before running them, rebuilds the caches, sets permissions on `storage` and `bootstrap/cache`, brings the site back and prints a short check. It stops on any error and prints the rollback command.

It never touches `.env`, uploads (`storage/app/public`), `public/.user.ini`, `public/php.ini`, `public/favicon.ico`, and never runs `news:publish-anniversary`.

## 4. `.env` (important: the live site currently runs in debug mode)

File Manager > `/home/skylinkictcom/s.skylinkict.com/.env` > Edit. Do this **before step 3 if you can** (the script warns if it is still wrong):

- `APP_ENV=production`, **both** `APP_DEBUG` lines `false`, `APP_URL=https://s.skylinkict.com`
- add the keys from `deploy/.env.additions.txt` (fill in the three `*_INBOX` addresses; `MAIL_SCHEME=smtps` for port 465, `smtp` for 587)
- no `ANNIVERSARY_FORCE` line
- then `php artisan config:cache` (the script already does it, if you edited before running it)

## 5. Check

- [ ] `https://s.skylinkict.com`: Home, About, Services, Products, Portfolio, News, Contact open; pictures and hero video show; no error page.
- [ ] Admin login. Admin > **Anniversary Ceremony** says **Currently HIDDEN for visitors (Off). Choose Automatic or On now to enable.** Dates pre-filled Nov 23 - Dec 7, 2026.
- [ ] A private window shows **no** ceremony.
- [ ] Contact form test message arrives in Admin > Inbox (then delete it); unread count goes down when opened.
- [ ] A few other sites on the account still load (`erp`, `newsite`, `basirah.skylinkict.com`).
- [ ] Cron (once): cPanel > Advanced > Cron Jobs > every minute:
  `cd /home/skylinkictcom/s.skylinkict.com && /opt/cpanel/ea-php82/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1 # skylink`
  (without it scheduled news and queued mail never run). Do not touch other cron jobs.

## 6. Turn the ceremony on when you are ready

Admin > **Anniversary Ceremony**: **Automatic** (live between the two dates, Ethiopian time) or **On now** (live at once until the end date). **Admin preview** lets only you see it. Set **Off** to hide it again.

## 7. Ceremony day (optional)

```bash
cd /home/skylinkictcom/s.skylinkict.com
php artisan news:publish-anniversary          # add  --feature  to make it the featured story
```

## 8. Rollback

```bash
cd /home/skylinkictcom/s.skylinkict.com
bash deploy/server-rollback.sh deploy/backups/<the-date-folder-printed-by-the-deploy>
```
It asks `yes` before restoring the files, and asks separately before restoring the database (the database restore replaces the tables, so anything entered after the backup is lost). It deletes nothing: files added by the release stay but the old code ignores them. Then it clears the caches and brings the site back.

## 9. PHP version for `s.skylinkict.com`

cPanel says `ea-php81`, but the live site answers `X-Powered-By: PHP/8.2.35`. The release needs **8.2 or newer**. Check: cPanel > Software > **MultiPHP Manager**, tick only `s.skylinkict.com`. If it shows **8.1**: choose **PHP 8.2** (or 8.3) for this domain only and Apply (never the "all domains" box), then run the deploy. The deploy script refuses to run if the PHP in the shell is older than 8.2.

---

## Stage 2 (optional, later): switch the address to https://skylinkict.com

Separate from deploy day. Do it only when there is time to test, **not between Nov 23 and Dec 7**, and only after Stage 1 is deployed and checked. The app stays in `s.skylinkict.com`; `public_html` answers Sky Link **only** for the host `skylinkict.com`/`www` and only for URLs that are not an existing file or folder, so `erp/`, `newsite/` and the other sites keep working.

Files (in `deploy/domain-switch/`):

| File | Goes to |
|---|---|
| `skylink-front.php` | `/home/skylinkictcom/public_html/skylink-front.php` (new; the old `index.php` is left alone) |
| `public_html.htaccess.NEW` | replaces `/home/skylinkictcom/public_html/.htaccess` (`public_html.htaccess.diff` shows the change) |
| `A-public.htaccess.NEW` | replaces `/home/skylinkictcom/s.skylinkict.com/public/.htaccess` (step 5 only, adds the 301) |

1. **Backup:** `cp /home/skylinkictcom/public_html/.htaccess /home/skylinkictcom/s.skylinkict.com/deploy/backups/public_html.htaccess.bak`, the same for `s.skylinkict.com/public/.htaccess` (`A-public.htaccess.bak`) and for `.env` (`A-env.bak`). Download `public_html/.htaccess` and `index.php` to your PC too.
2. **Create:** File Manager: new file `public_html/skylink-front.php` (paste `skylink-front.php`); replace `public_html/.htaccess` with `public_html.htaccess.NEW`. SSH (creates only new names; stop if `ls` shows they exist):
   ```bash
   cd /home/skylinkictcom/public_html
   ls -d sl favicon.ico skylink-front.php 2>&1      # all three must say "No such file"
   mkdir sl
   ln -s /home/skylinkictcom/s.skylinkict.com/public/assets       sl/assets
   ln -s /home/skylinkictcom/s.skylinkict.com/public/build        sl/build
   ln -s /home/skylinkictcom/s.skylinkict.com/storage/app/public  sl/storage
   ln -s /home/skylinkictcom/s.skylinkict.com/public/favicon.ico  favicon.ico
   ```
3. **`.env`:** `APP_URL=https://skylinkict.com` and add `ASSET_URL=https://skylinkict.com/sl`. Then in `/home/skylinkictcom/s.skylinkict.com`: `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`. (Logged-in users have to log in again on the new host.)
4. **Test** (private window): `https://skylinkict.com/` is Sky Link (not the old "besirah-react" page); `curl -sI https://skylinkict.com/ | grep -i x-powered-by` shows PHP 8.2+; all public pages, pictures, videos, favicon; `www.` jumps to `https://skylinkict.com`; admin login, Anniversary page, inbox, contact form, an upload; and **every other site still loads**: `skylinkict.com/erp/`, `/newsite/`, `/webpro/`, `/newweb/`, `/readymeal/`, `/hanim/`, `/hanim-admin/`, `/alfurqan/`, `/almathurat/`, `/basirah-app/`, `/privacy-policy/`, `/account-deletion/`, `/attendance-api/`, `/slt/`, `basirah.skylinkict.com`, `chapatest.skylinkict.com`.
5. **Only if all is OK:** replace `/home/skylinkictcom/s.skylinkict.com/public/.htaccess` with `A-public.htaccess.NEW`: `s.skylinkict.com/...` then 301-redirects to `skylinkict.com/...` (assets/build/storage URLs keep answering).
6. **1-minute rollback:**
   ```bash
   cp /home/skylinkictcom/s.skylinkict.com/deploy/backups/public_html.htaccess.bak /home/skylinkictcom/public_html/.htaccess
   cp /home/skylinkictcom/s.skylinkict.com/deploy/backups/A-public.htaccess.bak /home/skylinkictcom/s.skylinkict.com/public/.htaccess
   ```
   then in `.env` set `APP_URL=https://s.skylinkict.com` and remove the `ASSET_URL` line, re-run the four `artisan` commands of step 3. Nothing needs deleting: the new files (`skylink-front.php`, `sl/`, `favicon.ico`) are unused once the old `.htaccess` is back.
7. **After:** Google Search Console: add `https://skylinkict.com`, "Change of address" from `s.skylinkict.com`; update Google Business and social profiles.

Notes: only `skylink-front.php` is switched to PHP 8.2 (a `<Files>` handler line in the merged `.htaccess`); the other sites in `public_html` keep their PHP (approved). The Stage 2 rules were proven with an emulation (no Apache here); the real server confirms two things in step 4: that LiteSpeed honours that handler line and follows the symlinks. Clash analysis (assets/build/storage/index.html/robots.txt vs `public_html`) is in `deploy/domain-switch/` and in the previous version of this guide in git history.

---

## For the owner to decide (nothing here was done)

1. **`https://skylinkict.com/Skylink/vendor.zip` is publicly downloadable** (HTTP 200) in the old copy `public_html/Skylink`. Options: (a) block it with a `.htaccess` in `public_html/Skylink/`:
   ```apache
   <FilesMatch "\.(zip|sql|bak|log|env)$">
       Require all denied
   </FilesMatch>
   ```
   (b) move it out of `public_html` yourself in File Manager; (c) leave it. A file named `vendor.zip` normally holds only libraries; it was not downloaded or inspected.
2. **Database password of `skylink_laravel`** (the old copy's database): change it in cPanel > Databases > MySQL Databases and in `public_html/Skylink/.env` if you want to be safe; needed only if that zip contained its `.env`.
3. **Old files in the app folder** (`app.zip` 395 MB, `routes.zip` 168 MB, `node_modules`, `tests`): not downloadable (outside the document root); removing them only frees disk space.
4. **The old copy `public_html/Skylink`** itself (not used by the live site).
5. **The staging site** (`staging.skylinkict.com`, if created): keep for later tests or remove when you decide.
6. The second phone number in the old header (`deploy/SERVER-DIFFS-REVIEW.md`).

---

## 10. Final round additions (2026-11-20): read before Deploy Day

**Package:** build with `deploy\build-release.ps1` from the final commit. Public zip is now about 88 MB (was 168 MB: the oversized images were resized). **New migrations: none** (the 8 of the release are unchanged).

**After step 3 (Run), run these two commands on the server** (`cd /home/skylinkictcom/s.skylinkict.com`, `/opt/cpanel/ea-php82/root/usr/bin/php`):
```bash
php artisan products:fix-lms-image   # idempotent: only changes product 6 if its image is still the dead Bing address
php artisan images:scan-external     # read-only list of other hotlinked or missing images (title + admin edit address)
```

**`.env` on the live site (mail, cache, queue):**
```
MAIL_MAILER=smtp
MAIL_HOST=mail.skylinkict.com
MAIL_PORT=465
MAIL_SCHEME=smtps            # Laravel 12: smtps for port 465, smtp for 587 (MAIL_ENCRYPTION is no longer read)
MAIL_USERNAME=info5@skylinkict.com      # sender mailbox (see section 11)
MAIL_PASSWORD=<typed by the owner>
MAIL_FROM_ADDRESS=info5@skylinkict.com
MAIL_FROM_NAME="Sky Link Technologies"
CONTACT_INBOX=info@skylinkict.com
CACHE_STORE=file             # Redis and Memcached are NOT running on this server (PHP extensions exist, no server); file cache is used
QUEUE_CONNECTION=sync        # mail goes out while the visitor's request runs; use `database` only together with the cron line below
```
- **Mail details and the test are in section 11** (the old `info@` password in the live `.env` is rejected, `535`; the new sender is `info5@`).
- **The test-site mail guard must not exist on live:** do NOT copy `MAIL_STAGING_GUARD`, `MAIL_STAGING_ALLOWLIST`, `MAIL_STAGING_REDIRECT` to the live `.env` (without `MAIL_STAGING_GUARD=true` the guard does nothing).
- **Cron** (one line, every minute, already in step 5) runs the scheduler: scheduled news and, if `QUEUE_CONNECTION=database`, the mail queue (`queue:work --stop-when-empty`).
- **DNS (cPanel > Zone Editor, skylinkict.com):** MX, SPF (`-all`, includes hostns.io) and DKIM (`default._domainkey`) exist. **DMARC is missing.** Add a TXT record `_dmarc` = `v=DMARC1; p=none; rua=mailto:info@skylinkict.com` (monitor first; tighten later). Without it Gmail and others may send the confirmation emails to spam.

**`public/.htaccess`** now carries caching and compression rules (1 year for build files, uploads and WebP copies; 30 days for site images; 1 day for CSS/JS; gzip/brotli). The deploy script keeps the server's own cPanel PHP-handler block when it overwrites this file (it backs the block up in `deploy/backups/<date>/htaccess-php-handler.txt`).

**Public data cache:** home sections, product and service lists are cached for 1 to 5 minutes and rebuilt at once after any admin save. If a change ever seems stuck: `php artisan cache:clear`.

**For the owner (nothing was changed on live data):**
- Run `php artisan images:scan-external` for the full list. On bedru: all 21 products used external image addresses (Bing, Google, Freepik, Unsplash, iconfinder, simplilearn, erp.aero, website-files); product 6 (LMS) now has its own image. Upload own images for the rest in Admin > Products.
- Videos 22 and 23 have no thumbnail (`/admin/videos/22/edit`, `/23/edit`). No video link is dead; the TikTok player can still fail on some networks and then shows "Watch on TikTok".
- Large files nothing on the site uses (about 30 MB, safe to delete later): `about/teammember2.JPG`, `about/diredawa.JPG`, `news1.jpg`, `about/hobe.JPG`, `about/huda.png`, `about/evergreen.JPG`, `about/teammember.JPG`, `about/huda2.jpg`, `teammember.png`.
- Uploads in `storage` of 300 to 620 KB (a services PNG, portfolio WebP files, video thumbnails): re-save them smaller in the admin when convenient.
- The ceremony stays **Off** after deploy. Turn it on in Admin > Anniversary Ceremony when you are ready. Stage 2 (switch to skylinkict.com) is separate and not between Nov 23 and Dec 7.

---

## 11. Email setup (owner enters the password; final decision 2026-11-20)

The mailbox **`info5@skylinkict.com`** (created by the owner) **sends** the website emails; every form notification is **delivered to `info@skylinkict.com`**. (The old password for `info@` in the live `.env` is rejected by the mail server, `535`, so mail from the live site fails until this is changed.) The password is typed **by the owner only**, never in chat, code, seeders or git.

**1. Live `.env` lines** (File Manager > `/home/skylinkictcom/s.skylinkict.com/.env`; replace every existing `MAIL_*`, `CONTACT_INBOX` and `QUEUE_CONNECTION` line, do not leave duplicates):
```
MAIL_MAILER=smtp
MAIL_HOST=mail.skylinkict.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=info5@skylinkict.com
MAIL_PASSWORD=<the mailbox password, typed by the owner>
MAIL_FROM_ADDRESS=info5@skylinkict.com
MAIL_FROM_NAME="Sky Link Technologies"
CONTACT_INBOX=info@skylinkict.com
CONTACT_CONFIRM_VISITOR=true
QUEUE_CONNECTION=sync
CACHE_STORE=file
```
- Port 587 instead: `MAIL_PORT=587`, `MAIL_SCHEME=smtp`.
- **No staging mail guard on live.** Do not add `MAIL_STAGING_GUARD`, `MAIL_STAGING_ALLOWLIST` or `MAIL_STAGING_REDIRECT` (they exist only on bedru; without `MAIL_STAGING_GUARD=true` the guard does nothing).
- Then: `cd /home/skylinkictcom/s.skylinkict.com && /opt/cpanel/ea-php82/root/usr/bin/php artisan config:clear && /opt/cpanel/ea-php82/root/usr/bin/php artisan config:cache`.

**2. DMARC record** (cPanel > Zone Editor > Manage for skylinkict.com > Add Record): type `TXT`, name `_dmarc.skylinkict.com.`, record `v=DMARC1; p=none; rua=mailto:info@skylinkict.com`. MX, SPF and DKIM already exist. Start with `p=none` (reports only), tighten later.

**3. Test right after going live (and the same on bedru):** use `info@skylinkict.com` as the visitor address in all four forms:
1. Contact page: send a **General question**, a **Quote request** and a **Support request**; once in English and once with an Amharic name and message (the confirmation then arrives in Amharic).
2. Home page footer: subscribe to the **newsletter** (welcome email with an unsubscribe link).
3. Open `info@skylinkict.com` (webmail), **Inbox and Spam**: for each contact form there must be **two** emails: the team notification (reference `SK-...`) and the visitor confirmation ("We received your message ..."), both from `info5@`.
4. Admin > Inbox: the messages are listed and each shows **Sent** on its email line (no red "Failed").
5. Delete the test messages afterwards (and the test subscriber in Admin > Subscribers).
On bedru the staging guard allows only `info@skylinkict.com` and `info5@skylinkict.com`; every other address is redirected to `info@`, so no customer in the copied data can receive a test mail.

**4. If an email failed:** Admin > Inbox shows **Failed** (red). Open the message: the page shows the error and a button **Send to team inbox again**. Fix the cause first (usually the password), then press it. The visitor's own confirmation is not re-sent automatically; reply to the visitor from your mail program.
## 12. Homepage Video and new fonts (added 2026-11-20): read before Deploy Day

- **New migration (additive, one table):** `2026_11_22_100000_create_hero_video_settings_table`. `server-deploy.sh` runs it at its normal migrate step (the `yes` prompt). Rollback: `php artisan migrate:rollback --path=database/migrations/2026_11_22_100000_create_hero_video_settings_table.php`. Until someone uploads, the homepage plays the built-in video exactly as before.
- **New admin page: Admin > Homepage Video.** Upload the desktop MP4 (required the first time), optional WebM, optional mobile MP4 and a poster image. Reset to default and Restore previous ask for confirmation. Uploaded files live in `storage/app/public/hero-video/` (served through `public/storage`); the folder gets its own `.htaccess` that blocks scripts. The built-in files in `public/assets/videos/hero/` are never deleted.
- **Server upload limit.** PHP `upload_max_filesize` and `post_max_size` decide how big a video can be. The page shows the real limit. If it is below your video size (the built-in desktop video is 18 MB), raise both in cPanel (MultiPHP INI Editor) for the domain, for example to 64M. `public/php.ini` is cPanel-managed and is not part of the release.
- **ffmpeg.** If the server has `ffmpeg`, uploads are shrunk automatically (no sound, faststart), a phone version and a poster are made. If not, files are stored as uploaded and a poster image is required with a new desktop video. Optional `.env` key `FFMPEG_BIN` points to a non-default ffmpeg path.
- **Fonts are now self-hosted** in `public/assets/fonts/` (Inter, Plus Jakarta Sans, Fraunces, Noto Sans/Serif Ethiopic; SIL Open Font License). The site no longer calls Google Fonts. They ship in the public zip; nothing else to configure. Bodoni Moda, Playfair Display, Belleza, EB Garamond and Manrope are gone.
