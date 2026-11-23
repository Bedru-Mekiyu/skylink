import { spawn } from 'node:child_process';

const bashScript = `#!/usr/bin/env bash
set -Eeuo pipefail

LIVE_APP="/home/skylinkictcom/s.skylinkict.com"
BEDRU_APP="/home/skylinkictcom/bedru.skylinkict.com"
STAMP="$(date +%Y%m%d-%H%M%S)"
BK_DIR="$BEDRU_APP/deploy/backups/bedru-pre-partners-$STAMP"

echo "=== 1. Backing up Bedru database ==="
mkdir -p "$BK_DIR"
SKYLINK_APP_DIR="$BEDRU_APP"
. "$BEDRU_APP/deploy/server-common.sh"
db_dump "$BK_DIR/bedru-db"
echo "Bedru backup complete: $BK_DIR/bedru-db.sql.gz"

echo "=== 2. Read-only dump of partners table from Live ==="
ENVF="$LIVE_APP/.env"
LIVE_HOST="$(env_get DB_HOST)"
LIVE_PORT="$(env_get DB_PORT)"
LIVE_USER="$(env_get DB_USERNAME)"
LIVE_PASS="$(env_get DB_PASSWORD)"
LIVE_DB="$(env_get DB_DATABASE)"

DUMP_FILE="$BK_DIR/live_partners_table.sql"
MYSQL_PWD="$LIVE_PASS" mysqldump -h "$LIVE_HOST" -P "$LIVE_PORT" -u "$LIVE_USER" \\
    --single-transaction --quick --no-tablespaces --default-character-set=utf8mb4 "$LIVE_DB" partners > "$DUMP_FILE"
echo "Live partners table dumped: $(wc -l < "$DUMP_FILE") lines"

echo "=== 3. Importing partners table into Bedru staging DB ==="
ENVF="$BEDRU_APP/.env"
BEDRU_HOST="$(env_get DB_HOST)"
BEDRU_PORT="$(env_get DB_PORT)"
BEDRU_USER="$(env_get DB_USERNAME)"
BEDRU_PASS="$(env_get DB_PASSWORD)"
BEDRU_DB="$(env_get DB_DATABASE)"

MYSQL_PWD="$BEDRU_PASS" mysql -h "$BEDRU_HOST" -P "$BEDRU_PORT" -u "$BEDRU_USER" "$BEDRU_DB" < "$DUMP_FILE"
echo "Import complete."

echo "=== 4. Copying partner logo files from Live to Bedru ==="
mkdir -p "$BEDRU_APP/storage/app/public/partners"
cp -p -u "$LIVE_APP"/storage/app/public/partners/* "$BEDRU_APP"/storage/app/public/partners/
COPIED_COUNT="$(ls -1 "$BEDRU_APP/storage/app/public/partners" | wc -l)"
echo "Bedru storage/app/public/partners now has $COPIED_COUNT files."

echo "=== 5. Clearing cache on Bedru ==="
cd "$BEDRU_APP"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== 6. Verifying counts on Bedru ==="
php -r "require 'vendor/autoload.php'; \\$app = require_once 'bootstrap/app.php'; \\$app->make(Illuminate\\\\Contracts\\\\Console\\\\Kernel::class)->bootstrap(); echo 'Bedru partners count: ' . \\App\\Models\\Partner::count() . PHP_EOL;"
`;

const ssh = spawn('ssh', [
    '-i', 'C:\\Users\\AFRO-EAGLE\\.ssh\\cpanel_bedru',
    '-p', '22',
    'skylinkictcom@91.204.209.13',
    'bash -s'
]);

let stdout = '';
let stderr = '';
ssh.stdout.on('data', d => { stdout += d.toString(); });
ssh.stderr.on('data', d => { stderr += d.toString(); });

ssh.on('close', code => {
    console.log(stdout);
    if (code !== 0) {
        console.error('STDERR:', stderr);
        console.error('Exit code:', code);
    }
});

ssh.stdin.write(bashScript);
ssh.stdin.end();
