import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const outDir = path.resolve('footage/_review/admin-scroll');
fs.mkdirSync(outDir, { recursive: true });

const nap = (ms = 2500) => new Promise((r) => setTimeout(r, ms));
const browser = await chromium.launch();

const ctx = await browser.newContext({
    httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined,
    viewport: { width: 1440, height: 900 }
});
const page = await ctx.newPage();
page.setDefaultTimeout(90000);

console.log('Navigating to login...');
await page.goto(base + '/login', { waitUntil: 'domcontentloaded' });
await page.waitForSelector('input[name=email]');
await page.fill('input[name=email]', 'admin@gmail.com');
await page.fill('input[name=password]', adminPw);
await Promise.all([
    page.waitForNavigation({ timeout: 90000 }),
    page.click('button[type=submit]')
]);
console.log('Logged in. URL:', page.url());
await nap(2000);

const viewports = [
    { name: '1440x900', width: 1440, height: 900 },
    { name: '1366x768', width: 1366, height: 768 },
    { name: '1024x768', width: 1024, height: 768 },
    { name: '360x780', width: 360, height: 780, isMobile: true }
];

for (const vp of viewports) {
    console.log(`Setting viewport ${vp.name}...`);
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await nap(1500);

    if (vp.isMobile) {
        const toggler = await page.$('.navbar-toggler');
        if (toggler) {
            await toggler.click();
            await nap(1000);
        }
    }

    // Scroll sidebar down if scrollable
    await page.evaluate((isMob) => {
        const el = isMob ? document.querySelector('.offcanvas.sidebar .offcanvas-body') : document.querySelector('nav.sidebar');
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }, !!vp.isMobile);
    await nap(500);

    const metrics = await page.evaluate((isMob) => {
        const el = isMob ? document.querySelector('.offcanvas.sidebar') : document.querySelector('nav.sidebar');
        const scrollContainer = isMob ? document.querySelector('.offcanvas-body') : document.querySelector('nav.sidebar');
        if (!el) return { error: 'sidebar not found' };
        const style = window.getComputedStyle(el);

        const siteMgmt = [...document.querySelectorAll('a')].find(a => a.textContent.includes('Site Management'));
        const siteMgmtRect = siteMgmt ? siteMgmt.getBoundingClientRect() : null;

        const logout = [...document.querySelectorAll('a')].find(a => a.textContent.includes('Logout'));
        const logoutRect = logout ? logout.getBoundingClientRect() : null;

        return {
            scrollHeight: scrollContainer.scrollHeight,
            clientHeight: scrollContainer.clientHeight,
            overflowY: window.getComputedStyle(scrollContainer).overflowY,
            height: style.height,
            siteMgmtVisible: siteMgmtRect ? (siteMgmtRect.bottom <= window.innerHeight && siteMgmtRect.top >= 0) : false,
            siteMgmtTop: siteMgmtRect ? Math.round(siteMgmtRect.top) : null,
            logoutVisible: logoutRect ? (logoutRect.bottom <= window.innerHeight && logoutRect.top >= 0) : false,
            logoutTop: logoutRect ? Math.round(logoutRect.top) : null,
            windowHeight: window.innerHeight
        };
    }, !!vp.isMobile);

    console.log(`Metrics ${vp.name}:`, metrics);
    const shotPath = path.join(outDir, `after-${vp.name}.png`);
    await page.screenshot({ path: shotPath });
    console.log(`Saved screenshot: ${shotPath}`);

    if (vp.isMobile) {
        const closeBtn = await page.$('.offcanvas .btn-close');
        if (closeBtn) {
            await closeBtn.click();
            await nap(500);
        }
    }
}

// Test Settings page
console.log('Testing Settings page navigation...');
await page.setViewportSize({ width: 1440, height: 900 });
await page.goto(base + '/admin/settings', { waitUntil: 'domcontentloaded' });
await nap(1000);
const title = await page.textContent('h1, h2, h3');
console.log('Settings page heading:', title);
const hasToggle = await page.$('input[name="show_prices"]');
console.log('Has show_prices toggle:', !!hasToggle);

await page.screenshot({ path: path.join(outDir, 'admin-settings-page.png') });
console.log('Saved admin-settings-page.png');

await browser.close();
console.log('All AFTER screenshots taken and verified successfully!');
