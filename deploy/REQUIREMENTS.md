# What the server needs (from the code, not guessed)

## PHP
- **PHP 8.2 or newer** (composer.json `php ^8.2`, Laravel 12). 8.3 is a safe choice on cPanel.
- Extensions: `mbstring`, `pdo_mysql`, `mysqlnd`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `zip`, `intl` (recommended).
- **`gd` with WebP and JPEG support is required**: news and portfolio uploads are resized and converted by `App\Support\NewsImageProcessor` (`imagecreatefromstring`, `imagewebp`, `imagejpeg`). Without GD, uploading a news image fails. (Local test PCs without GD skip the 10 image tests.)
- Settings: `memory_limit` 256M or more, `upload_max_filesize` and `post_max_size` at least 10M (images up to 5 MB are accepted), `max_execution_time` 60 or more.
- Do **not** overwrite `public/.user.ini` / `public/php.ini` on the server: cPanel creates and manages them (the repo copy points at the old account path).

## Database
- **MySQL or MariaDB** (utf8mb4). Local development uses SQLite, production must use MySQL.
- Sessions, cache and queue use the database, so these tables must exist (they come from the migrations): `sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`.

## Migrations in this release (run in this order by `php artisan migrate --force`)
Which of these are already applied on the live database is checked in Phase 2 (`php artisan migrate:status`). Known new one:
- `2026_11_21_100000_create_anniversary_settings_table`: adds **one new table** (`anniversary_settings`), changes no existing table.
Other recent additive migrations: `2026_11_20_100000_add_card_fields_to_service_categories_table`, `2026_11_19_100000_add_showcase_video_to_portfolios_table`, `2026_11_19_100100_add_slug_to_portfolios_table_if_missing`, `2026_11_18_100000_create_inquiries_table` (copies old contact/support/quote messages into the new inbox table; old tables stay untouched).

## Files and folders
- `storage/` and `bootstrap/cache/` writable by the PHP user.
- `public/storage` -> `storage/app/public` (`php artisan storage:link`). Uploaded news/portfolio images live in `storage/app/public`: **never overwrite or delete it on deploy**.
- Hero video, posters and team photos are committed in `public/assets/**`, so they travel with the release zip (about 170 MB).
- The news cover for the anniversary article is `resources/news/anniversary-five-years-cover.png` (committed).
- Built front-end assets: `public/build` (made by `npm run build`, not in git; the release zip contains them).

## Scheduler and queue (cron)
One cron line is enough: it runs the Laravel scheduler every minute, which publishes scheduled news, sends queued emails (`queue:work --stop-when-empty`), cleans duplicates daily and anonymises old messages monthly.
```
* * * * * /usr/local/bin/php /home/<cpanel user>/<app folder>/artisan schedule:run >> /dev/null 2>&1
```
(The exact PHP path is shown in cPanel > Cron Jobs; use the same PHP version as the site.)

## Mail
SMTP account from cPanel (host, port 465 SSL or 587 TLS, user, password) in `MAIL_*`. Without working mail the contact form still saves every message (visible in Admin > Inbox) but the team email shows "Failed" there.

## Web server
- Apache with `mod_rewrite` (the committed `public/.htaccess` handles routing).
- The domain's document root must be the Laravel **`public`** folder. Everything else (app, vendor, .env, storage) must sit **outside** the web root.
- HTTPS active (AutoSSL) for `skylinkict.com` and `www`.

## Not deployed
`.env`, `node_modules`, `tests`, `tools`, `footage`, `.bat` helper files, local captures, `storage/logs`, local SQLite database.
