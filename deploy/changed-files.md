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
