# Sky Link: go-live plan (not executed)

Prepared 2026-11-19 after testing the release on the password-protected test site `https://bedru.skylinkict.com`
(login in `deploy/STAGING-ACCESS.txt`, local and gitignored). **The live site and all other sites were not changed.**

## 1. Deploy day to https://s.skylinkict.com (same package, same scripts that passed on bedru)

Use `deploy/UPLOAD-GUIDE.md` (the "Deploy Day checklist"). In short:

1. PC: `powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1` (tests + build + verify). It must end with `RELEASE VERIFIED`.
2. cPanel full backup. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://s.skylinkict.com` and add the keys of `deploy/.env.additions.txt` to `/home/skylinkictcom/s.skylinkict.com/.env` (the live site runs in debug mode today).
3. Upload `deploy\.build\release\*` to `/home/skylinkictcom/s.skylinkict.com/deploy/` (scp; the 168 MB public zip may need a retry on a flaky link, the checksum files tell you).
4. SSH: `cd /home/skylinkictcom/s.skylinkict.com && bash deploy/server-deploy.sh`, type `yes` at the migration prompt (8 additive migrations).
5. Check the ceremony says **HIDDEN (Off)** and a guest sees none. Add the cron line (guide, step 5). Turn the ceremony on in Admin when ready.
6. Rollback if needed: `bash deploy/server-rollback.sh deploy/backups/<date>` (restores files and, after a separate `yes`, the whole database).

## 2. Optional Stage 2: switch to https://skylinkict.com

Separate section of the guide ("Stage 2"). **Not between Nov 23 and Dec 7**, only when there is time to test; the LiteSpeed PHP-handler line and the symlinks can only be proven on the real server (the guide has a 1-minute rollback).

## 3. What was verified on bedru, and the result

Setup: new subdomain, document root `/home/skylinkictcom/bedru.skylinkict.com/public` (outside `public_html`), PHP 8.2.35 (answered in the `X-Powered-By` header), basic auth, `noindex`, `MAIL_MAILER=log`, a fresh copy of the live database and uploads, `server-deploy.sh` run on it twice and `server-rollback.sh` twice. All screenshots are in `storage/app/staging-checks/<area>/` (local, gitignored).

| Area | Result | Evidence |
|---|---|---|
| Public pages at 1440 / 768 / 390 px: Home, About, Services (+ category, detail), Products (+ detail), Portfolio, News (+ English and Amharic article), Video detail, Contact, Login, 404 | 41 of 45 checks pass: status, no sideways scroll, no ceremony markup, no console errors. The 4 that fail are console/CORS errors of the **embedded TikTok player** (third party), on the video page and the Amharic article | `storage/app/staging-checks/<page>/<width>.png`, `report-public.json` |
| Fonts | Home/pages load; `document.fonts` is recorded per page in the report | `report-public.json` (`fontsOk`) |
| Guests blocked from admin (`/admin/dashboard`, `/inbox`, `/news`, `/anniversary`, `/services`) | pass (all redirect to `/login`) | `report-admin-all.json` |
| Admin login / logout / login again | pass | `auth/dashboard-1440.png` |
| Contact form: 2 test messages -> admin inbox; email goes to the log (`MAIL_MAILER=log`) | pass; "Mark all as read" empties the unread badge | `contact/`, `inbox/` |
| Create + edit + delete with an image upload, each checked on the public site: **news, services, service categories (card photo), portfolio project, videos, products** | pass (after fixing my test script's form filling; no site bug) | `<section>/list-after-create-1440.png`, `public-1440.png` |
| Anniversary: fresh = HIDDEN (Off) and no ceremony for a guest; **On now** = guest sees intro + panel + strips; reduced-motion fallback renders; **Automatic** live window = visible, future window = hidden; **Admin preview** = admin sees it with the "Admin preview" label, guest does not; set back to **Off** | pass; bedru was left on **Off** | `anniversary/*.png` |
| Rollback test: `server-rollback.sh` then redeploy | pass after fixing a real bug (see 4) | server output |
| Lighthouse mobile (simulated 4x CPU, slow 4G, from this PC to a busy shared host) | Home 64 (LCP 6.1 s, CLS 0), News 64 (LCP 11.2 s, CLS 0), Contact 70 (LCP 5.9 s, CLS 0.017) | `storage/app/staging-checks/lighthouse/*.json` |

**Not verified (say so honestly):**
- The hero **video** and all `.mp4/.webm` files were blocked in every browser check (speed), so video playback itself was not tested; images and the page were.
- The services marquee / centre highlight, testimonials, Featured Products and news banner were captured in screenshots but not asserted by code; the layout checks (no overflow, no console errors) passed.
- **Showcase** videos (the video library selector on a portfolio item), the **newsletter** form and the Amharic *content* (only that the article renders) were not exercised. **No screen recording** was made (screenshots only).
- Pagination, search and filters on the News page were not exercised.
- The 4x-throttled Lighthouse numbers are slow mainly because of large images and external fonts; they say nothing about CLS problems (CLS is about 0).

## 4. Bugs found, and status

| Bug | Status |
|---|---|
| `php artisan route:cache` failed (duplicated `admin.services.*` route names), which would have broken the deploy | **Fixed**, test added (`RouteCacheTest`) |
| `public/assets/images/placeholder.png` did not exist although many views use it as the broken-image fallback | **Fixed** (file added) |
| A dead hot-linked product image returned 404 on `/products` | Page now falls back to the placeholder (**fixed in code**); the dead address itself is product data, see section 5 |
| `server-rollback.sh` restored only tables that are in the dump, so a redeploy after a rollback failed with "table already exists" | **Fixed** (the restore now empties the database first, behind the existing `yes`); proven on MySQL on bedru |
| The test script of the deploy scripts had left stale servers answering | **Fixed** |
| Old staging folder `/home/skylinkictcom/staging.skylinkict.com` vanished at about 18:11 | **Unexplained.** The two crontab entries of another project (`skylink-staging/backend`) cannot delete home folders (the only delete is an hourly prune inside that app's `storage/app/private/analytics-exports`); no deletion appears in the shell history; the bedru folder was checked every 10 minutes from 18:29 to at least 19:50 UTC and was always present |
| Product images are hot-linked from many external sites (Bing, Google, freepik, unsplash ...), which can disappear at any time | Open, owner decision (below) |
| Large images make LCP slow on mobile (5.9 to 11 s under Lighthouse throttling) | Open, not a blocker; image optimization later |
| The `deploy` folder inside the app folder holds the backups (about 170 MB each time) | By design; they are not web-accessible (outside `public/`); remove old ones by hand when you want the disk space back |

## 5. For the owner to decide (nothing here was done)

1. **The bedru test site** (`bedru.skylinkict.com`, folder `/home/skylinkictcom/bedru.skylinkict.com`, database `skylinkictcom_staging`, DB user `skylinkictcom_stg`): keep for further tests or remove later. It contains a copy of live data (customer messages included) behind a password.
2. **The old `staging.skylinkict.com` subdomain**: it still exists in cPanel, but its folder is gone and it answers 404. Remove it in cPanel > Domains when you want.
3. **`public_html/Skylink/vendor.zip`** is publicly downloadable (HTTP 200). Options: delete it, block it with a `.htaccess` deny rule (in `deploy/UPLOAD-GUIDE.md`), move it, or leave it.
4. **The `skylink_laravel` database password** (old copy): change it in cPanel > MySQL Databases and in `public_html/Skylink/.env` if you want to be safe.
5. **Old zips in the live app folder** (`app.zip` 395 MB, `routes.zip` 168 MB, `node_modules`, `tests`): not downloadable, they only use disk space.
6. **The product with the dead image:** id 6, **"Learning Management System (LMS)"** (category `industry-solutions`, slug `lms`). Admin edit page: `https://s.skylinkict.com/admin/products/6/edit` (after deploy day; on bedru `https://bedru.skylinkict.com/admin/products/6/edit`). Replace its image with an uploaded file. Products 1 to 13 all use external image addresses; check them in the admin the same way.
7. **The second phone number** in the old header (`+251-936-177777`), see `deploy/SERVER-DIFFS-REVIEW.md`: keep or drop.
8. **Disk:** the server is at 97% (about 197 GB free); each deploy backup adds about 170 MB.

## 6. Added 2026-11-20: news filter and inbox delete (tested on bedru)

- **News filter:** it listed every row of `news_categories`; the real database also holds nine service-type categories (2025-07-04) next to News, Events and Innovations, which showed up as extra buttons. Fixed in `NewsDisplayController` (only the three news categories). The filter label for the "Innovations" row stays "Innovation" (the label already agreed in the code). No data was changed.
- **Inbox delete:** Delete on each list row and on the message page, checkboxes + "Delete selected", confirmation popups, flash "Message deleted.", soft delete (the `inquiries` table already has `deleted_at`).
- **Deploy day: no new migration** for these two changes (the 8 migrations of the release are unchanged).
- Verified on bedru at 1440 and 390 px (21 checks, `storage/app/staging-checks/report-news-inbox.json`, screenshots in `news-filter/` and `inbox-delete/`).
- Note: on 2026-11-19 at 20:14 UTC the ceremony on bedru was switched to "On now" by someone other than the automated checks (probably an admin trying it); it was set back to Off.

## 7. Added 2026-11-20: video player fallback, in-page confirmation dialogs, My Profile (tested on bedru)

**No new migration.** The release still has the same 8 migrations (see RELEASE.txt). New public files in the **public zip**: `public/assets/js/sky-video-guard.js`, `sky-confirm.js`, `public/assets/css/sky-video-fallback.css`, `sky-confirm.css`.

- **Video "Unable to load player ... Retry":** that text is **TikTok's own** error, shown inside its player in the visitor's browser or network. All 23 videos are embeddable (TikTok/YouTube oEmbed answer 200 for each) and they play in a test browser on both bedru and the live site, so the cause is on the visitor's side (privacy/tracking settings, a blocked TikTok video server, network). The site cannot read inside TikTok's frame, but TikTok tells the page when it fails; the page now replaces a failed or silent player (after an error message or 15 s) with the thumbnail and a **"Watch on TikTok"** button (new tab). The detail page also always shows a "Watch on TikTok/YouTube" link. The admin now rejects video links the site cannot play (short `vm.tiktok.com` links, profile links, other sites) with a clear message.
- **Dead videos:** none. All 23 saved videos (18 TikTok, 5 YouTube) are public and embeddable. Videos **22** and **23** have **no thumbnail** (the fallback then shows a plain dark poster); add one in the admin if you like: `/admin/videos/22/edit`, `/admin/videos/23/edit`.
- **Confirmation dialogs:** every delete (inbox row and "Delete selected" with the count, news, news restore, services, service categories, portfolio, products, videos), the Anniversary "On now" and the news editor's "Insert link" use one in-page dialog (Cancel / Esc / keyboard, focus returns). The browser `confirm()` stays only as a no-JavaScript fallback. Two demo `alert()` calls on the public services page (shopping cart / notifications / view details) were removed.
- **My Profile** (Admin menu > My Profile): name, email, password. Email and password changes need the current password; the password is rate limited and signs out every other device; `AuthenticateSession` was added to the web middleware group (existing sessions keep working; the first request after deploy stores the password hash in each session). On deploy day, **admins stay logged in**.
- **Fixed before this deploy:** the profile link in the admin dropdown was a dead `#`.
