set -e
# Runs ON THE SERVER (piped over ssh: first line = admin password, read by the outer command into $PW).
# Writes only inside /home/skylinkictcom/bedru.skylinkict.com and into the staging database.
S=/home/skylinkictcom/bedru.skylinkict.com
cd "$S"
[ "$(pwd -P)" = "$S" ] || { echo wrong folder; exit 1; }
printf 'User-agent: *\nDisallow: /\n' > public/robots.txt
cat > deploy/mkadmin.php <<'EOF'
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'skylinkictcom_staging') { echo "not the staging database\n"; exit(1); }
$u = App\Models\User::where('email', 'staging-admin@skylinkict.test')->first() ?? new App\Models\User();
$u->forceFill(['name' => 'Staging Admin', 'email' => 'staging-admin@skylinkict.test', 'password' => bcrypt(getenv('PW')), 'is_admin' => true])->save();
echo "staging admin id ".$u->id." is_admin=".(int) $u->is_admin."\n";
EOF
php deploy/mkadmin.php
cat public/robots.txt
