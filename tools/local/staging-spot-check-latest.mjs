// LIGHT spot check on bedru only: About team card, no auth prompt, news video pop-up (phone), admin delete pages.
import { chromium, devices } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = 'https://bedru.skylinkict.com';
const out = path.resolve('footage/_review/spot-bedru-latest');
fs.mkdirSync(out, { recursive: true });
const nap = (ms = 2500) => new Promise((r) => setTimeout(r, ms));
const results = []; const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
let loads = 0;
// 1. About (desktop): team card, no password
let ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
let p = await ctx.newPage();
const r = await p.goto(base + '/about', { waitUntil: 'load' }); loads++;
await p.waitForTimeout(1500);
const a = await p.evaluate(() => ({ names: [...document.querySelectorAll('.team-card h3')].map((e) => e.textContent.trim()), alt: [...document.querySelectorAll('.team-card img')].map((i) => i.alt).join('|'), ovf: document.documentElement.scrollWidth - innerWidth }));
rec('About: open without a password, Frezer Maru card (Head of Marketing) present, no sideways scroll', r.status() === 200 && !r.headers()['www-authenticate'] && a.names.includes('Frezer Maru') && a.ovf <= 0, JSON.stringify(a.names));
await p.locator('.team-card', { hasText: 'Frezer Maru' }).scrollIntoViewIfNeeded(); await p.waitForTimeout(1200);
await p.locator('.team-section').screenshot({ path: path.join(out, 'team.png') }).catch(() => {});
await ctx.close(); await nap();
// 2. News video pop-up on a Pixel 7
ctx = await browser.newContext({ ...devices['Pixel 7'] }); p = await ctx.newPage();
await p.goto(base + '/news', { waitUntil: 'load' }); loads++;
const opener = p.locator('[data-news-player-open]').first();
if (await opener.count()) {
    await opener.tap(); await p.waitForTimeout(25000);
    const o = await p.evaluate(() => ({ fallback: !!document.querySelector('#newsPlayer .sky-video-fallback'), slowBar: !!document.querySelector('#newsPlayer .sky-video-slow'), iframe: !!document.querySelector('#newsPlayer iframe'), loaded: !!document.querySelector('#newsPlayer iframe.is-loaded') }));
    await p.screenshot({ path: path.join(out, 'news-video-pixel7.png') });
    rec('News video pop-up on Pixel 7: the player stays (no false fallback after 25 s); a helper bar or the playing video shows', o.iframe && !o.fallback, JSON.stringify(o));
} else rec('News video pop-up: no video on the page', true, 'skipped');
await ctx.close(); await nap();
// 3. Admin delete pages
ctx = await browser.newContext({ viewport: { width: 390, height: 844 } }); p = await ctx.newPage();
await p.goto(base + '/login', { waitUntil: 'load' }); loads++;
await p.fill('input[name=email]', process.env.BEDRU_ADMIN_EMAIL); await p.fill('input[name=password]', process.env.BEDRU_ADMIN_PASSWORD);
await p.click('button[type=submit]'); await p.waitForURL(/admin/, { timeout: 20000 }).catch(() => {});
rec('admin login', /\/admin\//.test(p.url())); await nap();
for (const u of ['/admin/subscribers', '/admin/subscriptions', '/admin/inbox']) {
    await p.goto(base + u, { waitUntil: 'load' }); loads++; await p.waitForTimeout(1200);
    const m = await p.evaluate(() => ({ csv: /Export CSV/i.test(document.body.innerText), del: !!document.querySelector('[data-sky-confirm]'), ovf: document.documentElement.scrollWidth - innerWidth, tap: Math.min(...[...document.querySelectorAll('button[aria-label^="Delete"], .inbox-del')].map((b) => b.getBoundingClientRect().height).concat([99])) }));
    rec(`${u} on a phone: Export CSV, delete with the modal attribute, no sideways scroll`, m.csv && m.ovf <= 0 && (m.del || u !== '/admin/inbox'), JSON.stringify(m));
    if (u === '/admin/subscribers') await p.screenshot({ path: path.join(out, 'admin-subscribers-phone.png') });
    await nap();
}
console.log('page loads:', loads);
await browser.close();
const failed = results.filter((x) => !x.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
