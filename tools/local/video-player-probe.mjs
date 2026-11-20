// LOCAL USE ONLY. Reproduces the video player on the test site (or any URL) in a real browser, videos NOT blocked.
//   node tools/local/video-player-probe.mjs <base> [bedru|live]      (bedru = send the test-site basic auth, from deploy/STAGING-ACCESS.txt)
// Opens the /news "Watch video" pop-up and a video detail page, then prints what the player frame says.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'https://bedru.skylinkict.com').replace(/\/$/, '');
const mode = process.argv[3] || 'bedru';
const out = path.resolve('storage/app/staging-checks/videos'); fs.mkdirSync(out, { recursive: true });
const creds = mode === 'bedru' ? (() => { const a = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8'); return { username: 'staging', password: a.match(/user\s+staging\s+password\s+(\S+)/)[1] }; })() : undefined;

const browser = await chromium.launch({ args: ['--autoplay-policy=no-user-gesture-required'] });
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, httpCredentials: creds });
await ctx.route(/tawk\.to/, (r) => r.abort());
const page = await ctx.newPage(); page.setDefaultTimeout(60000);
const log = [];
page.on('console', (m) => { if (['error', 'warning'].includes(m.type())) log.push(`console.${m.type()}: ${m.text().slice(0, 200)}`); });
page.on('requestfailed', (r) => log.push(`requestfailed: ${r.url().slice(0, 140)} ${r.failure()?.errorText}`));
page.on('response', (r) => { if (r.status() >= 400 && /tiktok|youtube|tiktokv|ytimg/.test(r.url())) log.push(`http ${r.status()}: ${r.url().slice(0, 140)}`); });

const frameText = async () => {
    const res = [];
    for (const f of page.frames()) {
        if (f === page.mainFrame()) continue;
        const t = await f.locator('body').innerText({ timeout: 4000 }).catch(() => '(unreadable)');
        res.push(`${f.url().slice(0, 90)} => ${t.replace(/\s+/g, ' ').slice(0, 160)}`);
    }
    return res;
};

// 1. hero pop-up on /news
await page.goto(base + '/news', { waitUntil: 'domcontentloaded' });
await page.waitForLoadState('load', { timeout: 60000 }).catch(() => {});
const opener = await page.$('[data-news-player-open]');
console.log('hero opener present:', !!opener, opener ? await opener.getAttribute('data-embed') : '');
if (opener) {
    await opener.click();
    await page.waitForTimeout(9000);
    console.log('--- hero pop-up frames:'); (await frameText()).forEach((l) => console.log('  ', l));
    await page.screenshot({ path: path.join(out, `probe-hero-${mode}.png`) });
}
// 2. a video detail page (TikTok + YouTube)
const links = await page.evaluate(() => [...document.querySelectorAll('a[href*="/videos/"]')].map((a) => a.getAttribute('href')));
const unique = [...new Set(links)].slice(0, 2);
for (const l of unique) {
    await page.goto(l.startsWith('http') ? l : base + l, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(9000);
    console.log(`--- detail ${l.replace(base, '').slice(0, 60)} frames:`); (await frameText()).forEach((x) => console.log('  ', x));
    await page.screenshot({ path: path.join(out, `probe-detail-${unique.indexOf(l)}-${mode}.png`) });
}
// 3. control: the bare TikTok player iframe on a plain page (is the error TikTok's own?)
const ctl = await ctx.newPage();
await ctl.setContent('<html><body style="margin:0"><iframe id=f width=400 height=700 src="https://www.tiktok.com/player/v1/7525050791875464453?autoplay=0" allow="autoplay; fullscreen"></iframe></body></html>');
await ctl.waitForTimeout(9000);
for (const f of ctl.frames()) { if (f === ctl.mainFrame()) continue; const t = await f.locator('body').innerText({ timeout: 4000 }).catch(() => '(unreadable)'); console.log('--- control (bare page) frame:', f.url().slice(0, 80), '=>', t.replace(/\s+/g, ' ').slice(0, 160)); }
await ctl.screenshot({ path: path.join(out, `probe-control-${mode}.png`) });
console.log('--- log (first 14):'); [...new Set(log)].slice(0, 14).forEach((l) => console.log('  ', l));
await browser.close();
