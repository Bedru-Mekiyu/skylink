import { spawn } from 'node:child_process';
import { chromium } from 'playwright-core';

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
            cmdString
        ]);
        let stdout = '';
        let stderr = '';
        proc.stdout.on('data', d => { stdout += d.toString(); });
        proc.stderr.on('data', d => { stderr += d.toString(); });
        proc.on('close', code => {
            if (code === 0) resolve(stdout);
            else reject(new Error(`SSH exited with code ${code}\nSTDERR: ${stderr}`));
        });
    });
}

async function main() {
    console.log('====================================================');
    console.log('STARTING POST-DEPLOY VERIFICATION ON LIVE');
    console.log('====================================================');

    console.log('\n--- 1. Live Database & Filesystem Audit ---');
    const remoteAuditPhp = `
cd "${REMOTE_APP}"
php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$app->make(Illuminate\\\\Contracts\\\\Console\\\\Kernel::class)->bootstrap();

echo 'PARTNERS_COUNT=' . \\App\\Models\\Partner::count() . PHP_EOL;
echo 'ACTIVE_PARTNERS=' . \\App\\Models\\Partner::where('active', true)->count() . PHP_EOL;
echo 'SITE_SETTINGS=' . (\\Illuminate\\Support\\Facades\\Schema::hasTable('site_settings') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'SERVICES_SHOW_PRICE_COL=' . (\\Illuminate\\Support\\Facades\\Schema::hasColumn('services', 'show_price') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'PRODUCTS_SHOW_PRICE_COL=' . (\\Illuminate\\Support\\Facades\\Schema::hasColumn('products', 'show_price') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'PRODUCTS_PRICE_TEXT_COL=' . (\\Illuminate\\Support\\Facades\\Schema::hasColumn('products', 'price_text') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'SHOW_PRICES_SETTING=' . \\App\\Models\\SiteSetting::get('show_prices', 'not_found') . PHP_EOL;
echo 'STORAGE_PARTNER_FILES=' . count(glob('storage/app/public/partners/*')) . PHP_EOL;
echo 'MARKETING_PHOTO_EXISTS=' . (file_exists('public/assets/images/about/head-of-marketing.jpg') ? 'YES' : 'NO') . PHP_EOL;
echo 'MARKETING_PHOTO_SIZE=' . round(filesize('public/assets/images/about/head-of-marketing.jpg') / 1024, 1) . ' KB' . PHP_EOL;
echo 'PLACEHOLDER_FLAG=' . (\\App\\Support\\TeamPlaceholder::inUse() ? 'IN_USE' : 'REAL_PHOTO') . PHP_EOL;
"
`;
    const auditOutput = await runSSH(remoteAuditPhp);
    console.log(auditOutput.trim());

    console.log('\n--- 2. Public HTTP & Content Verification ---');
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

    // 2a. Home English
    console.log('Checking https://skylinkict.com (English)...');
    let res = await page.goto('https://skylinkict.com/', { waitUntil: 'domcontentloaded' });
    console.log(`HTTP Status: ${res.status()}`);
    const homeTitleEn = await page.$eval('.partners h3, .partners h2, .trusted-clients-title, h3', el => el ? el.textContent : '').catch(() => '');
    const fullHtml = await page.content();
    console.log(`Contains 'Trusted Clients': ${fullHtml.includes('Trusted Clients')}`);
    console.log(`Contains 'Trusted Partners': ${fullHtml.includes('Trusted Partners')}`);

    // Check partner logo styles
    const logoStyles = await page.evaluate(() => {
        const logos = [...document.querySelectorAll('.partners-track img.partner-logo')];
        return logos.map(img => {
            const cs = window.getComputedStyle(img);
            return {
                src: img.src,
                alt: img.alt,
                borderRadius: cs.borderRadius,
                objectFit: cs.objectFit,
                naturalWidth: img.naturalWidth,
                naturalHeight: img.naturalHeight,
                renderedWidth: Math.round(img.getBoundingClientRect().width),
                renderedHeight: Math.round(img.getBoundingClientRect().height)
            };
        });
    });
    console.log(`Audited ${logoStyles.length} rendered logos on homepage.`);
    const circularLogos = logoStyles.filter(l => l.borderRadius === '50%' || parseFloat(l.borderRadius) > 10);
    console.log(`Forced circular logos: ${circularLogos.length}`);
    if (logoStyles.length > 0) {
        console.log(`Sample logo: radius=${logoStyles[0].borderRadius}, objectFit=${logoStyles[0].objectFit}, box=${logoStyles[0].renderedWidth}x${logoStyles[0].renderedHeight}`);
    }

    // 2b. Home Amharic
    console.log('\nChecking https://skylinkict.com/?lang=am (Amharic)...');
    res = await page.goto('https://skylinkict.com/?lang=am', { waitUntil: 'domcontentloaded' });
    console.log(`HTTP Status: ${res.status()}`);
    const amHtml = await page.content();
    console.log(`Contains 'የታመኑ ደንበኞች': ${amHtml.includes('የታመኑ ደንበኞች')}`);

    // 2c. About page and Frezer Maru card
    console.log('\nChecking https://skylinkict.com/about ...');
    res = await page.goto('https://skylinkict.com/about', { waitUntil: 'domcontentloaded' });
    console.log(`HTTP Status: ${res.status()}`);
    await page.waitForTimeout(1000);
    const marketingCard = await page.evaluate(() => {
        const img = document.querySelector('img[src*="head-of-marketing.jpg"]');
        if (!img) return null;
        img.scrollIntoView();
        return {
            src: img.src,
            alt: img.alt,
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
            complete: img.complete
        };
    });
    console.log('Marketing card evaluation:', JSON.stringify(marketingCard, null, 2));

    // 2d. Other pages check
    for (const p of ['/news', '/contact', '/login']) {
        const r = await page.goto(`https://skylinkict.com${p}`, { waitUntil: 'domcontentloaded' });
        console.log(`Page https://skylinkict.com${p} -> HTTP ${r.status()}`);
    }

    // 2e. Check recent laravel.log on live
    console.log('\n--- 3. Checking live laravel.log for recent errors ---');
    const logCheckScript = `
cd "${REMOTE_APP}"
TODAY="$(date +%Y-%m-%d)"
LOGFILE="storage/logs/laravel-\$TODAY.log"
if [ ! -f "\$LOGFILE" ]; then LOGFILE="storage/logs/laravel.log"; fi
if [ -f "\$LOGFILE" ]; then
    echo "Recent log entries from \$LOGFILE:"
    grep -E "ERROR|EXCEPTION|CRITICAL|WARNING" "\$LOGFILE" | tail -10 || echo "No recent errors found in \$LOGFILE."
else
    echo "No log file found."
fi
`;
    const logOutput = await runSSH(logCheckScript);
    console.log(logOutput.trim());

    await browser.close();
    console.log('\n====================================================');
    console.log('LIVE VERIFICATION COMPLETE.');
    console.log('====================================================');
}

main().catch(err => {
    console.error('VERIFICATION FAILED:', err);
    process.exit(1);
});
