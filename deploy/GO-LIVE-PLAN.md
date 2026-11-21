# Sky Link: go-live plan (not executed)

Prepared 2026-11-19 after testing the release on the test site `https://bedru.skylinkict.com`
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

Setup: new subdomain, document root `/home/skylinkictcom/bedru.skylinkict.com/public` (outside `public_html`), PHP 8.2.35 (answered in the `X-Powered-By` header), no password for public pages (2026-11-21; only the admin login protects the admin), `noindex`, `MAIL_MAILER=log`, a fresh copy of the live database and uploads, `server-deploy.sh` run on it twice and `server-rollback.sh` twice. All screenshots are in `storage/app/staging-checks/<area>/` (local, gitignored).

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

## 8. Added 2026-11-20 (final polish, tested on bedru; live was not changed)

- **LMS product image:** original illustration (no stock photo, no licence needed, see `deploy/IMAGE-CREDITS.md`), set by `php artisan products:fix-lms-image` (idempotent). `php artisan images:scan-external` lists the other external or missing images.
- **Admin cleanup:** Inbox is now a list with an unread dot, sender, type, preview and date (phone: cards); Products and Newsletter Subscribers lists use the admin card style; the old Contact Messages page lost its `#` and contact columns; two pages no longer load a second copy of Bootstrap from a CDN. All functions kept (open, mark all read, delete, delete selected, search, filters, pagination, confirmation dialog).
- **Images and video:** lazy images fade in over a placeholder, a broken image becomes the site placeholder; 90 MB of oversized originals were resized (public zip 168 to 88 MB) with WebP sets; the video page loads its player only after a click; hero video was already poster-first with a mobile file.
- **Caching:** `.htaccess` rules; Redis/Memcached are not available (no server running), so the file cache is used for a short public data cache that is cleared on every admin save.
- **Lighthouse mobile on bedru (simulated slow 4G, this PC to a busy host), before to after, LCP:** Home 9.4 to 6.4 s, News 13.9 to 10.4 s, Products 8.2 to 6.4 s, Contact 9.9 to 6.1 s. CLS 0.00 on all four (was 0.063 on Products). Scores vary run to run on this host (Products 63 to 47 because of main-thread time on a loaded server), so judge by LCP and CLS. The 2.5 to 3 s LCP target is not reached: the Home page is still 7.3 MB (mostly the mobile hero video, 5.3 MB).
- **Browsers:** layout audit at 16 widths on Chrome, Edge (0 sideways scroll everywhere), Firefox (key widths, 0). WebKit was installed but its run was stopped for time.
- **Email:** forms save, show success and reach the inbox; sending fails because the mailbox password in `.env` is rejected (535). Needs the owner (see UPLOAD-GUIDE section 10). DMARC record missing.
- **Deploy tooling:** `server-deploy.sh` keeps the server's cPanel PHP handler in `public/.htaccess`.

## 9. Owner decides: the bedru test site is now open (changed 2026-11-20)

- **bedru has no password popup any more.** It is reachable by anyone with the address, but it sends `X-Robots-Tag: noindex, nofollow` and a disallow-all `robots.txt` (search engines should not list it). After each deploy to bedru, re-write `public/robots.txt` (the release contains the normal one).
- It **holds a copy of real customer messages and subscribers** (from live) and its test admin uses a simple password (`admin@gmail.com`, see the local `deploy/STAGING-ACCESS.txt`, not in git). Recommended: change that password to a strong one, or put Basic Auth back (the old file is `.htaccess.basic-auth.bak` in the bedru folder: copy it over `.htaccess`), or delete the test site when testing is over.
- **The live site keeps its own admin accounts. This test login is not deployed and exists only in the `skylinkictcom_staging` database.**
- The staging mail guard stays on bedru (`info@` and `info5@` allowed, all others redirected). Mail settings for the live `.env`: UPLOAD-GUIDE section 11.
## 10. Added 2026-11-20: Homepage Video admin page and new typography (local work done; bedru check pending)

- **Homepage Video** (Admin menu): upload, preview, replace, restore previous, reset to default. Details, limits and ffmpeg behaviour: UPLOAD-GUIDE section 12. New migration `create_hero_video_settings_table`.
- **Typography:** Inter (body, forms, numbers), Plus Jakarta Sans (headings, card titles), Fraunces (hero headline and anniversary numerals only), Noto Sans/Serif Ethiopic (Amharic). Self-hosted variable WOFF2, preloaded, metric-matched fallbacks, body 16 to 18 px, 13 px floor for labels.
- Local tests: full suite passes (including `HeroVideoTest` and `TypographyTest`). Verification on bedru, Lighthouse before/after and the release package: see the final report.

## 11. Added 2026-11-20: team, About banner, Partners admin (tested on bedru; live was not changed)

- **Team:** Hanan Nesru, Head of Human Resource & Administration (replaces the old Head of Networking Administration). Photo cropped 4:5, WebP + JPEG, original archived privately. No personal links existed on the old card, so none were removed. Amharic name and title are not set on the site (the team cards are English only); propose Amharic only if the owner wants a translation.
- **Team photos** are now 4:5 rounded rectangles (12 px). Sharpness: Bedru Hussen (370 x 452) is the only photo that is soft at 2x screens; the others are larger.
- **About banner:** about half a screen (News 460 to 489 px and Portfolio 480 px are the reference).
- **Partners:** Admin > Partners (new migration `create_partners_table`, then `php artisan partners:optimize-legacy` once). Homepage marquee is now a seamless loop; up to 6 logos show as a still row; reduced motion shows a static grid; an empty table falls back to the old static list.
- **Gallery:** research only so far (placement options in `storage/app/staging-checks/about-gallery/`), waiting for the owner's "continue".

## 12. Added 2026-11-20 (round 3): gallery, security clean-up of bedru, verification (live was not changed)

- **Gallery** (Admin > Gallery, new migration `create_gallery_images_table`): see UPLOAD-GUIDE section 14. Verified locally (GD on, 367 tests, browser checks: autoplay, hover / focus / pause, swipe, keyboard, lightbox with focus trap, reduced motion, Save-Data, 4x CPU throttle about 50 fps, CLS 0, 1 / 3 / 12 / 60 photos).
- **bedru clean-up:** Basic Auth was restored at that time and **removed again on 2026-11-21** (public pages open to everyone, `noindex` kept; backups `.htaccess.basic-auth.bak` and `.bak2` in the bedru folder); `admin@gmail.com` password changed to a random one; all customer data (inbox 280 + 229 messages, 16 subscribers, 17 subscriptions, 1 service request, sessions) deleted from the staging database after a backup inside the bedru folder (`deploy/backups/pre-anonymize-*`). Older backups and `deploy/live-data.sql.gz` on bedru still contain the copied customer data: delete them when testing is over. The real admin `info@skylinkict.com` was not touched.
- **Homepage speed:** local Lighthouse (mobile, simulated slow 4G) Home LCP before this work 4.5 to 5.1 s, after the Ethiopic font fix 5.0 to 5.3 s (it was 7.4 s before the fix). About LCP 10.7 s (before 11.4 s) with the gallery in place. SEO shows 66 locally only because of the test-site noindex tag.

## Go-live blockers added 2026-11-21
- **Replace the Head of Marketing placeholder before going live** (photo `public/assets/images/about/head-of-marketing.jpg` + name in `config/team.php`; see UPLOAD-GUIDE.md addendum 2). The deploy script stops with a warning while it is still the placeholder.
- Add the cron line (UPLOAD-GUIDE.md addendum 2) on go-live day.
- Do the Elida clip admin step (`footage/_review/PORTFOLIO-VIDEO-FIX.md`).

## Added 2026-11-21 (3)
- About page: new intro card, bigger CEO card, Gallery moved to the bottom (above the footer). Admin service detail page fixed.
- Decision pending: the homepage shows "4+ Years Experience" while the About intro says "5+ YEARS" (`config/company.php`); make them agree before going live.

## Added 2026-11-21 (4)
- Gallery photos are imported on bedru only (15 visible, 23 hidden). Decide what stays visible; review faces and stock photos (GALLERY-IMPORT-REPORT.md) BEFORE importing on the live site (steps in UPLOAD-GUIDE.md addendum 4; rollback: `php artisan gallery:import-site-photos --rollback`).
- The homepage Years Experience counter now reads `config/company.php` (5+), same as the About intro.

## Added 2026-11-21 (5)
- About page text and spacing changes (intro headline/link, compact team section with a landscape CEO photo, Milestones to Gallery gap). No extra steps.

## Added 2026-11-21 (6)
- 2 new migrations (testimonials, departments) with import and tested rollback; new admin pages; delete buttons with file cleanup; Portfolio banner rule (newest, pin overrides); new `COMPANY_EMAIL` setting.
- Email address is info@skylinkict.com (skylinkict.com has MX + SPF + DKIM; no DMARC yet: add the monitoring record from UPLOAD-GUIDE addendum 7).
- Replace the Head of Marketing placeholder (see earlier item).

## Added 2026-11-21 (7)
- Head of Marketing: replace `public/assets/images/about/head-of-marketing.jpg` with the real photo (the card is hidden on the live site until then).
- Set the live admin password to a strong one; `admin@gmail.com` (test account, weak password) must not exist or must be changed.
- Email: fill the MAIL_* variables (UPLOAD-GUIDE addendum 7, items 4-6), add the DMARC TXT record (item 7), run the labelled test send, then enable.


> Done on 2026-11-21: see deploy/GO-LIVE-DONE.md.
