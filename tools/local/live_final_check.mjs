import { spawn } from 'node:child_process';
import https from 'node:https';

const SSH_KEY = 'C:\\Users\\AFRO-EAGLE\\.ssh\\cpanel_bedru';
const SSH_PORT = '22';
const SSH_HOST = '91.204.209.13';
const SSH_USER = 'skylinkictcom';
const REMOTE_APP = '/home/skylinkictcom/s.skylinkict.com';

function runSSH(cmdString) {
    return new Promise((resolve, reject) => {
        const proc = spawn('ssh', [
            '-i', SSH_KEY,
            '-p', SSH_PORT,
            `${SSH_USER}@${SSH_HOST}`,
            'bash -s'
        ]);
        let stdout = '';
        let stderr = '';
        proc.stdout.on('data', d => { stdout += d.toString(); });
        proc.stderr.on('data', d => { stderr += d.toString(); });
        proc.on('close', code => {
            if (code === 0) resolve(stdout);
            else reject(new Error(`SSH exited ${code}: ${stderr}`));
        });
        proc.stdin.write(cmdString);
        proc.stdin.end();
    });
}

function fetchUrl(url) {
    return new Promise((resolve, reject) => {
        https.get(url, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => resolve({ status: res.statusCode, headers: res.headers, body }));
        }).on('error', reject);
    });
}

async function main() {
    console.log('--- 1. Live PHP & Artisan Verification ---');
    const remotePhp = `
cd "${REMOTE_APP}"
php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$app->make(Illuminate\\\\Contracts\\\\Console\\\\Kernel::class)->bootstrap();

echo '1. Partners row count: ' . \\App\\Models\\Partner::count() . PHP_EOL;
echo '2. Storage partner logos: ' . count(glob('storage/app/public/partners/*')) . PHP_EOL;
echo '3. Settings table exists: ' . (\\Illuminate\\Support\\Facades\\Schema::hasTable('site_settings') ? 'YES' : 'NO') . PHP_EOL;
echo '4. Global show_prices setting: ' . \\App\\Models\\SiteSetting::get('show_prices') . PHP_EOL;
echo '5. Marketing placeholder in use: ' . (\\App\\Support\\TeamPlaceholder::inUse() ? 'YES' : 'NO') . PHP_EOL;
echo '6. Marketing photo size: ' . round(filesize('public/assets/images/about/head-of-marketing.jpg')/1024, 1) . ' KB' . PHP_EOL;
echo '7. Placeholder backup size: ' . round(filesize('public/assets/images/about/head-of-marketing.placeholder-20261123.jpg')/1024, 1) . ' KB' . PHP_EOL;

app()->setLocale('en');
\\$enHtml = view('partials.partners', ['partners' => \\App\\Models\\Partner::where('active', true)->take(1)->get()])->render();
echo '8. Rendered En title: ' . (str_contains(\\$enHtml, 'Trusted Clients') ? 'Trusted Clients OK' : 'FAIL') . PHP_EOL;

app()->setLocale('am');
\\$amHtml = view('partials.partners', ['partners' => \\App\\Models\\Partner::where('active', true)->take(1)->get()])->render();
echo '9. Rendered Am title: ' . (str_contains(\\$amHtml, 'የታመኑ ደንበኞች') ? 'የታመኑ ደንበኞች OK' : 'FAIL') . PHP_EOL;
"
`;
    const res1 = await runSSH(remotePhp);
    console.log(res1.trim());

    console.log('\n--- 2. Public Live URLs Verification ---');
    const pages = ['/', '/about', '/news', '/contact', '/login'];
    for (const p of pages) {
        const r = await fetchUrl(`https://skylinkict.com${p}`);
        console.log(`GET https://skylinkict.com${p} -> HTTP ${r.status}`);
        if (p === '/') {
            console.log('  Home contains "Trusted Clients":', r.body.includes('Trusted Clients'));
            console.log('  Home contains partner-logo natural styling (radius: 0):', r.body.includes('border-radius: 0'));
        }
        if (p === '/about') {
            console.log('  About contains "Frezer Maru, Head of Marketing":', r.body.includes('Frezer Maru, Head of Marketing'));
            console.log('  About contains "head-of-marketing.jpg":', r.body.includes('head-of-marketing.jpg'));
        }
    }

    console.log('\n--- 3. Marketing Photo Asset HTTP Check ---');
    const photoRes = await fetchUrl('https://skylinkict.com/sl/assets/images/about/head-of-marketing.jpg');
    console.log(`GET /sl/assets/images/about/head-of-marketing.jpg -> HTTP ${photoRes.status}`);
    console.log(`  Content-Type: ${photoRes.headers['content-type']}`);
    console.log(`  Content-Length: ${photoRes.headers['content-length']} bytes (~${Math.round(photoRes.headers['content-length'] / 1024)} KB)`);

    console.log('\n--- 4. Sample Partner Logo Asset HTTP Check ---');
    const logoRes = await fetchUrl('https://skylinkict.com/sl/storage/partners/Ukaz.png');
    console.log(`GET /sl/storage/partners/Ukaz.png -> HTTP ${logoRes.status}`);

    console.log('\n--- 5. Live Server Recent Log Audit ---');
    const logScript = `
cd "${REMOTE_APP}"
TODAY="$(date +%Y-%m-%d)"
LOGFILE="storage/logs/laravel-\$TODAY.log"
if [ ! -f "\$LOGFILE" ]; then LOGFILE="storage/logs/laravel.log"; fi
echo "Checking \$LOGFILE:"
tail -20 "\$LOGFILE"
`;
    const logRes = await runSSH(logScript);
    console.log(logRes.trim());
}

main().catch(err => {
    console.error('ERROR:', err);
    process.exit(1);
});
