# Changed files since the previous release (Homepage Video + typography release, 2026-11-20)

New migrations (run by `server-deploy.sh`):
- `database/migrations/2026_11_22_100000_create_hero_video_settings_table.php` (already on bedru)
- `database/migrations/2026_11_23_100000_create_partners_table.php`

New command after deploy: `php artisan partners:optimize-legacy`

Code:
- `app/Models/HeroVideoSetting.php`, `Partner.php`
- `app/Support/HeroVideo.php`, `HeroVideoProcessor.php`, `PartnerLogoProcessor.php`, `UploadLock.php`
- `app/Http/Controllers/Admin/HeroVideoController.php`, `PartnerController.php`, `app/Http/Controllers/AboutController.php`
- `app/Console/Commands/OptimizePartnerLogos.php`, `app/Providers/AppServiceProvider.php` (rate limiters `hero-upload`, `partner-upload`), `routes/web.php`

Views:
- `resources/views/admin/hero-video/edit.blade.php`, `admin/partners/index.blade.php`, `admin/partners/form.blade.php`, `admin/layouts/master.blade.php`
- `resources/views/partials/typography.blade.php`, `partials/partners.blade.php`, `partials/hero-media.blade.php`, `partials/anniversary/head.blade.php`
- `resources/views/components/team-member-card.blade.php`, `pages/aboutpage.blade.php`, `pages/index.blade.php`

Public files:
- `public/assets/fonts/*` (6 WOFF2 + LICENSE-OFL.txt)
- Added: `public/assets/images/about/hanan.jpg`, `public/assets/images/_opt/about/hanan-480.webp`
- Removed: `public/assets/images/about/jemil.png`, `public/assets/images/_opt/about/jemil-370.webp`

Not shipped: tests, tools, deploy scripts' backups, uploads (`storage/app/public`), `.env`.
