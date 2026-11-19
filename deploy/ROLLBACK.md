# Rollback plan

Use this if the live site breaks after a deploy. Do the steps in order and stop at the first one that fixes it.
Nothing here deletes uploads (`storage/app/public`) or the production `.env`.

## 0. Put the site in maintenance mode first
`php artisan down --secret=<your-secret-word>` (visit `https://<site>/<your-secret-word>` to keep working while it is down).

## 1. Quick fix: clear the caches (fixes most "it was fine before" problems)
```
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 2. Switch the ceremony off (if only the anniversary looks wrong)
Admin > Anniversary Ceremony > Mode **Off** > Save. No deploy needed.

## 3. Restore the code
The deploy script saved the old code as `~/backups/app-<stamp>.tar.gz` (outside the web root).
```
cd ~/skylink                      # the folder that holds artisan
# keep the live .env and uploads, replace everything else
tar -xzf ~/backups/app-<stamp>.tar.gz --exclude='./.env' --exclude='./storage/app' -C .
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 4. Undo the migration (only if the new table or a migration caused it)
The only new migration in this release is `2026_11_21_100000_create_anniversary_settings_table` (adds one table, touches nothing else).
```
php artisan migrate:rollback --path=database/migrations/2026_11_21_100000_create_anniversary_settings_table.php --force
```
The site works without that table: the ceremony simply follows the dates in `config/anniversary.php`.

## 5. Restore the database (last resort, loses everything written since the backup)
```
gunzip < ~/backups/db-<stamp>.sql.gz | mysql -u <db user> -p <db name>
```
Messages sent through the contact form after the backup would be lost, so use step 4 instead whenever you can.

## 6. Bring the site back
`php artisan up`

## Backups that exist before every deploy
1. cPanel > Backup Wizard: full account backup (downloaded to your computer).
2. `~/backups/db-<stamp>.sql.gz` and `~/backups/app-<stamp>.tar.gz` (made by `server-commands.sh before`).
