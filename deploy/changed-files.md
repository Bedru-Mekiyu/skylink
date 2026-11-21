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
