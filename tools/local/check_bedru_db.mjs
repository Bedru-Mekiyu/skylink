import { execSync } from 'node:child_process';

const sshCmd = `ssh -i C:\\Users\\AFRO-EAGLE\\.ssh\\cpanel_bedru -p 22 skylinkictcom@91.204.209.13 "cd /home/skylinkictcom/bedru.skylinkict.com && php -r \\"require 'vendor/autoload.php'; \\$app = require_once 'bootstrap/app.php'; \\$app->make(Illuminate\\\\Contracts\\\\Console\\\\Kernel::class)->bootstrap(); \\$tables = ['services', 'products', 'site_settings', 'partners', 'gallery_images', 'news', 'videos', 'users']; foreach (\\$tables as \\$t) { echo \\$t . ': ' . Illuminate\\\\Support\\\\Facades\\\\DB::table(\\$t)->count() . PHP_EOL; }\\""`;

try {
    const out = execSync(sshCmd, { encoding: 'utf8' });
    console.log(out);
} catch (err) {
    console.error(err.message);
}
