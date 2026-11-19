// LOCAL USE ONLY. Smoke test of the extracted release package (deploy/.build/*.zip unpacked like the server).
//   ADMIN_EMAIL=... ADMIN_PASSWORD=... node tools/local/verify-package.mjs http://127.0.0.1:8201 [hostHeader]
// Optional hostHeader sends "Host: <value>" (stage-2 router test). Prints one line per check, exits 1 on failure.
import { chromium } from 'playwright-core';

const base = (process.argv[2] || 'http://127.0.0.1:8201').replace(/\/$/, '');
const host = process.argv[3];
const fails = [];
const ok = (c, m) => { console.log(c ? 'ok  ' : 'FAIL', m); if (!c) fails.push(m); };

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, extraHTTPHeaders: host ? { Host: host } : {} });
await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
const page = await ctx.newPage();
page.setDefaultTimeout(90000); // slow CDN/font requests on a busy PC made a 30 s default flaky
const bad = [];
page.on('response', (r) => { if (r.status() >= 400 && !/tawk|favicon/.test(r.url())) bad.push(r.status() + ' ' + r.url()); });
page.on('pageerror', (e) => bad.push('JS ' + e.message));

for (const [u, needle] of [['/', 'Sky Link'], ['/about', ''], ['/services', ''], ['/news', ''], ['/portfolio', ''], ['/contact', ''], ['/login', 'Log in']]) {
    const r = await page.goto(base + u, { waitUntil: 'load' });
    ok(r.status() === 200, `GET ${u} -> ${r.status()}`);
    const html = await page.content();
    ok(!/anniv5/.test(html) || u === '/login', `${u}: no anniversary markup for a visitor`);
    if (needle) ok(html.includes(needle), `${u}: contains "${needle}"`);
}
// styles/scripts from public/build and images from public/assets really load
await page.goto(base + '/', { waitUntil: 'networkidle' });
const css = await page.evaluate(() => [...document.styleSheets].length);
ok(css > 0, `stylesheets applied (${css})`);
const imgs = await page.evaluate(() => [...document.images].filter((i) => i.complete && i.naturalWidth > 0).length);
ok(imgs > 3, `images decoded on home (${imgs})`);

// admin: login, Anniversary page, inbox
await page.goto(base + '/login');
await page.fill('input[name=email]', process.env.ADMIN_EMAIL);
await page.fill('input[name=password]', process.env.ADMIN_PASSWORD);
await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
ok(/\/admin/.test(page.url()), 'admin login lands on ' + new URL(page.url()).pathname);
await page.goto(base + '/admin/anniversary');
const status = (await page.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
ok(status === 'Currently HIDDEN for visitors (Off). Choose Automatic or On now to enable.', 'Anniversary status: ' + status);
ok((await page.$('#mode-off:checked')) !== null && (await page.$('#admin_preview:checked')) === null, 'Off selected on a fresh install, preview off');
ok((await page.inputValue('#starts_at')) === '2026-11-23T00:00' && (await page.inputValue('#ends_at')) === '2026-12-07T00:00', 'pre-filled dates Nov 23 - Dec 7 2026');
const inbox = await page.goto(base + '/admin/inbox');
ok(inbox.status() === 200, 'admin inbox -> ' + inbox.status());

ok(bad.length === 0, 'no 4xx/5xx or JS errors' + (bad.length ? ': ' + bad.slice(0, 5).join(' | ') : ''));
await browser.close();
console.log(fails.length ? `\n${fails.length} FAILED` : '\nALL PACKAGE CHECKS PASSED');
process.exit(fails.length ? 1 : 0);
