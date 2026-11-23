import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const outDir = path.resolve('footage/_review/admin-scroll');
fs.mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch();
const viewports = [
    { name: '1440x900', width: 1440, height: 900 },
    { name: '1366x768', width: 1366, height: 768 },
    { name: '1024x768', width: 1024, height: 768 },
    { name: '360x780', width: 360, height: 780, isMobile: true }
];

for (const vp of viewports) {
    const ctx = await browser.newContext({
        httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined,
        viewport: { width: vp.width, height: vp.height },
        isMobile: !!vp.isMobile
    });
    const page = await ctx.newPage();
    page.setDefaultTimeout(60000);

    // Login
    await page.goto(base + '/login', { waitUntil: 'domcontentloaded' });
    if (!/\/admin\//.test(page.url())) {
        await page.fill('input[name=email]', 'admin@gmail.com');
        await page.fill('input[name=password]', adminPw);
        await Promise.all([
            page.waitForNavigation({ timeout: 60000 }).catch(() => {}),
            page.click('button[type=submit]')
        ]);
    }

    await page.goto(base + '/admin/dashboard', { waitUntil: 'load' });
    await page.waitForTimeout(1000);

    if (vp.isMobile) {
        const toggler = await page.$('.navbar-toggler');
        if (toggler) {
            await toggler.click();
            await page.waitForTimeout(600);
        }
    }

    const metrics = await page.evaluate((isMob) => {
        const el = isMob ? document.querySelector('#sidebarMenu') : document.querySelector('nav.sidebar');
        if (!el) return null;
        const style = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();

        const siteMgmt = [...document.querySelectorAll('a')].find(a => a.textContent.includes('Site Management'));
        const siteMgmtRect = siteMgmt ? siteMgmt.getBoundingClientRect() : null;

        const logout = [...document.querySelectorAll('a')].find(a => a.textContent.includes('Logout'));
        const logoutRect = logout ? logout.getBoundingClientRect() : null;

        return {
            scrollHeight: el.scrollHeight,
            clientHeight: el.clientHeight,
            overflowY: style.overflowY,
            height: style.height,
            siteMgmtVisible: siteMgmtRect ? (siteMgmtRect.bottom <= window.innerHeight && siteMgmtRect.top >= 0) : false,
            siteMgmtTop: siteMgmtRect ? Math.round(siteMgmtRect.top) : null,
            siteMgmtBottom: siteMgmtRect ? Math.round(siteMgmtRect.bottom) : null,
            logoutVisible: logoutRect ? (logoutRect.bottom <= window.innerHeight && logoutRect.top >= 0) : false,
            logoutTop: logoutRect ? Math.round(logoutRect.top) : null,
            windowHeight: window.innerHeight
        };
    }, !!vp.isMobile);

    console.log(`Viewport ${vp.name}:`, metrics);
    await page.screenshot({ path: path.join(outDir, `before-${vp.name}.png`) });
    await ctx.close();
}

await browser.close();
console.log('BEFORE screenshots saved to footage/_review/admin-scroll/');
