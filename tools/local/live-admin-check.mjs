// LIVE admin login check. Credentials come ONLY from the environment (LIVE_ADMIN_EMAIL / LIVE_ADMIN_PASSWORD); nothing is written anywhere.
import { chromium } from 'playwright-core';
const base = 'https://skylinkict.com';
const email = process.env.LIVE_ADMIN_EMAIL, pw = process.env.LIVE_ADMIN_PASSWORD;
if (!email || !pw) { console.error('credentials missing'); process.exit(2); }
const results = []; const rec = (n, pass, note = '') => { results.push(pass); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1366, height: 800 } }); const p = await ctx.newPage();
const bad = [], errs = [];
p.on('response', (r) => { if (r.status() >= 400 && r.url().startsWith(base)) bad.push(r.status() + ' ' + r.url().slice(0, 100)); });
p.on('console', (m) => m.type() === 'error' && !/tawk/i.test(m.text()) && errs.push(m.text().slice(0, 120)));
await p.goto(base + '/login', { waitUntil: 'domcontentloaded', timeout: 60000 });
// eye toggle
await p.fill('input[name=password]', 'x-typed-by-check');
const t1 = await p.getAttribute('input[name=password]', 'type'); await p.click('button[onclick="togglePassword()"]'); const t2 = await p.getAttribute('input[name=password]', 'type');
rec('eye toggle shows the typed password', t1 === 'password' && t2 === 'text', `${t1} -> ${t2}`);
// wrong password is rejected
await p.fill('input[name=email]', email); await p.fill('input[name=password]', pw + 'x');
await p.click('button[type=submit]'); await p.waitForLoadState('domcontentloaded');
rec('wrong password rejected', /\/login/.test(p.url()) && !/dashboard/.test(p.url()), p.url());
await p.waitForTimeout(2000);
// correct login
await p.goto(base + '/login', { waitUntil: 'domcontentloaded' });
await p.fill('input[name=email]', email); await p.fill('input[name=password]', pw);
await p.click('button[type=submit]'); await p.waitForURL(/admin/, { timeout: 30000 }).catch(() => {});
rec('login works, lands in the admin', /\/admin\//.test(p.url()), p.url());
const pages = ['/admin/dashboard', '/admin/gallery', '/admin/anniversary', '/admin/profile', '/admin/news', '/admin/products', '/admin/services', '/admin/portfolio', '/admin/inbox', '/admin/subscribers', '/admin/testimonials', '/admin/partners', '/admin/hero-video', '/admin/departments'];
for (const u of pages) {
    bad.length = 0; errs.length = 0;
    const r = await p.goto(base + u, { waitUntil: 'load', timeout: 60000 }).catch((e) => ({ status: () => 0 }));
    const h = await p.evaluate(() => document.title.slice(0, 40)).catch(() => '');
    rec(`${u} opens (200, no 4xx/5xx, no console errors)`, r.status() === 200 && bad.length === 0 && errs.length === 0, `${r.status()} ${[...bad, ...errs].slice(0, 2).join(' | ')}`);
    await p.waitForTimeout(1500);
}
await p.goto(base + '/admin/gallery', { waitUntil: 'load' });
rec('Admin > Gallery lists the imported photos', (await p.locator('img').count()) >= 15);
await p.goto(base + '/admin/anniversary', { waitUntil: 'load' });
rec('Admin > Anniversary Ceremony shows the mode', /Automatic|On now|Off/i.test(await p.evaluate(() => document.body.innerText)));
// logout
await p.evaluate(() => document.querySelector('form[action$="/logout"]')?.submit());
await p.waitForLoadState('domcontentloaded'); await p.waitForTimeout(1500);
await p.goto(base + '/admin/dashboard', { waitUntil: 'domcontentloaded' });
rec('logged out: admin redirects to login', /\/login/.test(p.url()), p.url());
await browser.close();
process.exit(results.every(Boolean) ? 0 : 1);
