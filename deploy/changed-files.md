# Changed files since the previous release (Homepage Video + typography, 2026-11-20)

New migrations (run by `server-deploy.sh`), each additive:
- `2026_11_22_100000_create_hero_video_settings_table` (rollback: `migrate:rollback --path=database/migrations/2026_11_22_100000_create_hero_video_settings_table.php`)
- `2026_11_23_100000_create_partners_table` (also imports the 27 homepage logos; rollback: `--path=...2026_11_23_100000_create_partners_table.php`)
- `2026_11_24_100000_create_gallery_images_table` (rollback: `--path=...2026_11_24_100000_create_gallery_images_table.php`)

Run once after deploying: `php artisan partners:optimize-legacy` (small web copies for the imported logos).

New admin pages: Homepage Video, Partners, Gallery (left menu).

Code:
- Models: `HeroVideoSetting`, `Partner`, `GalleryImage`
- Support: `HeroVideo`, `HeroVideoProcessor`, `PartnerLogoProcessor`, `GalleryImageProcessor`, `UploadLock`
- Controllers: `Admin/HeroVideoController`, `Admin/PartnerController`, `Admin/GalleryController`, `AboutController`
- Command: `app/Console/Commands/OptimizePartnerLogos.php`
- `app/Providers/AppServiceProvider.php` (rate limiters `hero-upload`, `partner-upload`, `gallery-upload`), `routes/web.php`

Views:
- Admin: `admin/hero-video/edit`, `admin/partners/{index,form}`, `admin/gallery/{index,edit}`, `admin/layouts/master`
- Public: `partials/typography`, `partials/partners`, `partials/gallery`, `partials/noindex`, `partials/hero-media`, `partials/anniversary/head`, `components/team-member-card`, `pages/aboutpage`, `pages/index`, layouts (`layouts/app`, `guest`, `master`, `admin/layouts/master`: noindex include)

Public files:
- `public/assets/fonts/*` (inter, inter italic, plus-jakarta-sans, fraunces, noto-sans-ethiopic 42 KB, noto-serif-ethiopic 56 KB, LICENSE-OFL.txt)
- Added: `public/assets/images/about/hanan.jpg`, `public/assets/images/_opt/about/hanan-480.webp`
- Removed: `public/assets/images/about/jemil.png`, `public/assets/images/_opt/about/jemil-370.webp`

Writable folders: `storage/app/public/{hero-video,partners,gallery}`, `storage/app/private/archive`.
Not shipped: tests, tools, `.env`, uploads (`storage/app/public`), the archive.

# Added 2026-11-21 (gallery without controls, CEO hero still, team layout, anniversary, bedru open)

No new migrations. Rollback for every item below: re-upload the previous version of the file (the previous release zip), or `git revert` the commit before building.

- `resources/views/partials/gallery.blade.php`: the visible prev / next / pause buttons are gone; the gallery turns by itself (hover/focus pause, swipe, arrow keys and the lightbox are unchanged).
- `resources/views/pages/aboutpage.blade.php`: CEO alone on top, the other three centred below.
- `resources/views/components/anniversary/seal.blade.php`: homepage anniversary shows only the two lines (kicker + headline); `lang/*/anniversary.php` is unchanged (the long `message` key is simply unused).
- `resources/views/partials/hero-media.blade.php`: the default hero still is a `<picture>` (WebP + JPEG, phone version) with alt text; an admin-uploaded poster is used as before; "Reset to default" shows the CEO still again.
- `public/assets/videos/hero/poster.jpg` (replaced: CEO instead of the woman), new `poster.webp`, `poster-mobile.jpg`, `poster-mobile.webp`. Rollback: restore the old `poster.jpg` (59 KB, git history) and delete the three new files.
- The video files, the loop engine and the Homepage Video admin page are **unchanged**.
- Portfolio video: **no code change**. The cause was the missing admin step (choose the clip `elida`); see `footage/_review/PORTFOLIO-VIDEO-FIX.md`.
- bedru only: the Basic Auth was removed from `/home/skylinkictcom/bedru.skylinkict.com/.htaccess` (backups `.htaccess.basic-auth.bak`, `.bak2`).
- Not shipped (local only): `tools/local/build-ceo-hero.php`, tests, `storage/app/private/archive/ceo/` (original photo).

# Added 2026-11-21 (2): About page gallery plain, Head of Marketing, safety

No migrations. Rollback: re-upload the previous version of each file (previous release zip) or `git revert`.
- `resources/views/partials/gallery.blade.php`: header is only "Gallery"; plain section background (class `py-5`, no panel/gradient); no visible "Photo N" (the lightbox counter is screen-reader-only); gentler side-slide dimming for the light page.
- `app/Models/GalleryImage.php`: neutral alt fallback "Sky Link gallery photo".
- `config/team.php` (new): the ONE place for the CEO name/title/photo and the Head of Marketing name/photo (TODO placeholders).
- `app/Http/Controllers/AboutController.php`, `resources/views/pages/aboutpage.blade.php`, `resources/views/partials/hero-media.blade.php`: read names from `config/team.php`; team = CEO on top + 4 cards (Bedru, Oumer, Hanan, Head of Marketing).
- `public/assets/images/about/head-of-marketing.jpg` (new, placeholder silhouette 480x600). Replace this file with the real photo (same name).
- `app/Support/TeamPlaceholder.php`, `app/Console/Commands/CheckTeamPlaceholder.php` (new): `php artisan team:check-placeholder`; `deploy/server-deploy.sh` warns and exits 1 on the live site while the placeholder is in use.
- Removed on bedru only: `deploy/mkadmin.php` (backup in `deploy/backups/removed-files/`); added `deploy/.htaccess` (deny all) on bedru.

# Added 2026-11-21 (3): About intro card, bigger CEO card, Gallery at the bottom, admin fixes

No migrations. Rollback: re-upload the previous version of each file (previous release zip) or `git revert`.
- `resources/views/partials/about-intro.blade.php` (new) + `resources/views/pages/aboutpage.blade.php`: the "About Us" intro is now a floating card (headline "5+ YEARS of EXPERIENCE in the IT INDUSTRY.", MORE INFORMATION link to the Journey section) next to the old paragraph; the "Call to ask any question" phone item is removed; the old intro CSS is removed; the Journey section got `id="journey"`; the hero curve is white.
- `config/company.php` (new): `years_experience` (5) feeds the headline. The homepage counter "4+ Years Experience" is NOT linked to it yet (decision pending).
- `resources/views/components/team-member-card.blade.php`, `app/Http/Controllers/AboutController.php`: CEO card is the larger "lead" card (about 1.7x the others).
- `resources/views/pages/aboutpage.blade.php`: the Gallery section now sits at the very bottom (after Milestones, before the footer); nothing left at the old place.
- `resources/views/admin/services/show.blade.php` (bug fix: wrong route names gave a 500), `resources/views/admin/products/show.blade.php` (full-URL product images).
- Not shipped: tests, tools.

# Added 2026-11-21 (4): gallery photo import, homepage years figure, test isolation

No migrations. Rollback: re-upload the previous files, or `git revert`.
- `app/Console/Commands/ImportSitePhotos.php` (new): `php artisan gallery:import-site-photos` (dry run by default; `--apply`; `--rollback` removes everything it imported; `--rollback --hide-only` hides it). List: `database/data/gallery-site-photos.php` (15 visible, 23 hidden). Source photos (NOT in git or the package, 99 MB): `storage/app/private/gallery-import-src/`. Manifest: `storage/app/private/gallery-import/manifest.json`.
- `resources/views/pages/index.blade.php`: the "Years Experience" counter reads `config/company.php` (5+).
- `tests/TestCase.php`: test-only (each test process uses its own temporary storage).

# Added 2026-11-21 (5): About text and spacing

No migrations. Rollback: re-upload the previous version of each file or `git revert`.
- `resources/views/partials/about-intro.blade.php`: the "About Us" label and the lead-in line are removed; headline is now "5+ YEARS OF DRIVING DIGITAL INNOVATION AND TECHNOLOGICAL EXCELLENCE." (5 from `config/company.php`); link text "MORE ABOUT US →" (still #journey).
- `resources/views/pages/aboutpage.blade.php`: team section spacing tightened; CEO photo frame is landscape (4:3, up to 400 px wide); Milestones bottom padding reduced (gap to the Gallery about 60 px).
- `resources/views/partials/gallery.blade.php`: smaller top/bottom padding, title and caption spacing.
- `resources/views/components/team-member-card.blade.php`: image size attributes for the CEO card.
- Not shipped: tests, tools.

# Added 2026-11-21 (6): admin features and About/home fixes (branch feat/admin-features-and-about-fixes)

**New migrations (additive; each imports the existing content):**
- `2026_11_25_100000_create_testimonials_table` imports the 6 testimonials (rollback: `php artisan migrate:rollback --path=database/migrations/2026_11_25_100000_create_testimonials_table.php`)
- `2026_11_25_100100_create_departments_table` imports the 2 department cards (rollback: `--path=database/migrations/2026_11_25_100100_create_departments_table.php`)
Rolling back drops the table; uploaded pictures in `storage/app/public/{testimonials,departments}` stay on disk.

**New admin pages:** Testimonials, Departments (left menu). Reorder with arrows, hide, delete (modal, files removed).
**Delete buttons (files removed too):** Partners, Gallery (edit page), News "Delete permanently" (row, images, old-address redirects), and file cleanup on delete for Portfolio (pinned banner is blocked), Videos, Products, Services. Hide stays everywhere.
**Portfolio banner:** the NEWEST project is the banner automatically; "Pin as banner" (old "Feature on top") overrides it.
**Public changes:** homepage anniversary headline "Happy 5th Anniversary!" (block 20 px higher); About Milestones labels removed; About banner photo cropped (`public/assets/images/about/team-banner.jpg` + `_opt/about/team-banner-*.webp`); testimonial strip is now an endless loop without a jump (home + Services); department cards come from the admin.
**New files/folders:** `config/team.php`, `config/company.php` (years + `email`), `app/Models/{Testimonial,Department}.php`, `app/Support/{SmallImageProcessor,MediaCleanup}.php`, `database/data/testimonials-initial.php`, partials `testimonials-strip`, `departments`; upload folders `storage/app/public/{testimonials,departments}` (script-blocking .htaccess is created automatically).
**Limits:** testimonial photo / department picture: JPG/PNG/WebP, 4 MB, min 80 px; portfolio video clip 20 MB (the server limit is named in the error message).
**Email:** new env variable `COMPANY_EMAIL` (default `info@skylinkict.com`) is the one setting for the Contact page, the inbox (`CONTACT_INBOX` still overrides) and the sender (`MAIL_FROM_ADDRESS` still overrides). (The decision changed: the address is info@skylinkict.com. The domain skylinkictet.com does not exist in DNS and is NOT used.)
**New commands:** `php artisan mail:send-test` (dry run; `--send` sends 5 labelled test mails to COMPANY_EMAIL only), `php artisan email:replace-old-addresses` (dry run; `--apply`), `php artisan gallery:import-site-photos` (earlier).
Not shipped: tests, tools, the photo sources.

# Added 2026-11-21 (7): Frezer Maru card, no public password, intro close icon, news video on phones, delete audit, email address

No new migrations. Rollback: re-upload the previous files or `git revert`.
- Marketing card: `config/team.php` name "Frezer Maru"; `public/assets/images/about/head-of-marketing.jpg` (960x1200 studio-style portrait placeholder, no webp on purpose); `app/Support/TeamPlaceholder.php` + `AboutController`: on the real live site the card is HIDDEN while the placeholder photo is in place; `TEAM_SHOW_PLACEHOLDER=true` in .env shows it anyway.
- No Basic Auth can come back: `deploy/staging/02-lockdown.sh` is disabled; `deploy/server-deploy.sh` warns if an .htaccess contains AuthType; test `NoPublicPasswordTest`.
- Anniversary intro: the "Skip intro" text is replaced by an icon-only close button (`lang/*/anniversary.php` key `close`, `partials/anniversary/{intro,head}.blade.php`).
- News video: `public/assets/js/sky-video-guard.js` (slow start no longer replaced after 25 s; helper bar after 15 s; fallback only on a real player error or after 3 minutes), `partials/news/hero.blade.php`, `pages/video-detail.blade.php` (script URL versioned).
- Delete audit: Subscribers and Subscriptions (delete, delete selected, CSV export; `app/Http/Controllers/Admin/Concerns/ManagesRecordList.php`, `admin/partials/record-list.blade.php`), Inbox messages are now deleted for good with CSV export (an imported older message row is removed too).
- Email: default `COMPANY_EMAIL` is `info@skylinkict.com`.
