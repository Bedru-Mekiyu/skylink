import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const outDir = path.resolve('footage/_review/admin-scroll');
fs.mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch();

// First: create context, log in once, and capture cookies/storageState
const authCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const authPage = await authCtx.newPage();

console.log('Logging in to bedru admin...');
await authPage.goto(base + '/login', { waitUntil: 'domcontentloaded' });

let attempts = 0;
while (attempts < 5) {
    const errorText = await authPage.evaluate(() => {
        const el = document.querySelector('.alert-danger, .invalid-feedback');
        return el ? el.textContent.trim() : '';
    });
    if (errorText.includes('Too many login attempts')) {
        console.log('Throttled, waiting 15s... Message:', errorText);
        await authPage.waitForTimeout(15000);
        await authPage.goto(base + '/login', { waitUntil: 'domcontentloaded' });
    } else {
        break;
    }
    attempts++;
}

await authPage.fill('input[name=email]', 'admin@gmail.com');
await authPage.fill('input[name=password]', adminPw);
await Promise.all([
    authPage.waitForNavigation({ timeout: 60000 }).catch(() => {}),
    authPage.click('button[type=submit]')
]);

console.log('After login URL:', authPage.url());
if (!/\/admin\//.test(authPage.url())) {
    const err = await authPage.evaluate(() => document.body.innerText);
    console.error('Failed to log in. Page text:', err.slice(0, 300));
    await browser.close();
    process.exit(1);
}

const storageState = await authCtx.storageState();
await authCtx.close();

// Now test each viewport with stored auth state!
const viewports = [
    { name: '1440x900', width: 1440, height: 900 },
    { name: '1366x768', width: 1366, height: 768 },
    { name: '1024x768', width: 1024, height: 768 },
    { name: '360x780', width: 360, height: 780, isMobile: true }
];

for (const vp of viewports) {
    const ctx = await browser.newContext({
        storageState,
        viewport: { width: vp.width, height: vp.height },
        isMobile: !!vp.isMobile
    });

    const page = await ctx.newPage();
    await page.goto(base + '/admin/dashboard', { waitUntil: 'networkidle' });

    if (vp.isMobile) {
        const toggler = await page.$('.navbar-toggler');
        if (toggler) {
            await toggler.click();
            await page.waitForTimeout(600);
        }
    }

    const metrics = await page.evaluate((isMob) => {
        const el = isMob ? document.querySelector('.offcanvas.sidebar') : document.querySelector('nav.sidebar');
        if (!el) return { error: 'sidebar element not found' };
        const style = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();

        const siteMgmt = [...document.querySelectorAll('a')].find(a => a.textContent.includes('Site Management'));
        const siteMgmtRect = siteMgmt ? siteMgmt.getBoundingClientRect() : null;

        const logout = [...document.querySelectorAll('a')].find(a => a.textContent.includes('Logout'));
        const logoutRect = logout ? logout.getBoundingClientRect() : null;

        return {
            elementTag: el.tagName,
            elementClass: el.className,
            clientHeight: el.clientHeight,
            scrollHeight: el.scrollHeight,
            overflowY: style.overflowY,
            position: style.position,
            height: style.height,
            maxHeight: style.maxHeight,
            windowHeight: window.innerHeight,
            siteMgmtFound: !!siteMgmt,
            siteMgmtTop: siteMgmtRect ? Math.round(siteMgmtRect.top) : null,
            siteMgmtBottom: siteMgmtRect ? Math.round(siteMgmtRect.bottom) : null,
            siteMgmtVisible: siteMgmtRect ? (siteMgmtRect.bottom <= window.innerHeight && siteMgmtRect.top >= 0) : false,
            logoutFound: !!logout,
            logoutTop: logoutRect ? Math.round(logoutRect.top) : null,
            logoutBottom: logoutRect ? Math.round(logoutRect.bottom) : null,
            logoutVisible: logoutRect ? (logoutRect.bottom <= window.innerHeight && logoutRect.top >= 0) : false,
        };
    }, !!vp.isMobile);

    console.log(`\n=== Viewport ${vp.name} ===`);
    console.log(JSON.stringify(metrics, null, 2));

    await page.screenshot({ path: path.join(outDir, `before-${vp.name}.png`) });
    await ctx.close();
}

await browser.close();
console.log('\nDiagnosis complete. Screenshots saved to footage/_review/admin-scroll/');
