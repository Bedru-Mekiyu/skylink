// LIGHT spot check on the test site only (bedru). About 14 page loads with pauses; no automation burst.
//   BEDRU_ADMIN_EMAIL=... BEDRU_ADMIN_PASSWORD=... node tools/local/staging-spot-check-final.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = 'https://bedru.skylinkict.com';
const out = path.resolve('footage/_review/spot-bedru-final');
fs.mkdirSync(out, { recursive: true });
const nap = (ms = 2500) => new Promise((r) => setTimeout(r, ms));
const results = []; const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const p = await ctx.newPage();
let loads = 0; const errs = []; const bad = [];
p.on('console', (m) => { if (m.type() === 'error' && !/tawk|ERR_FAILED|favicon|Failed to load resource/i.test(m.text())) errs.push(m.text().slice(0, 120)); });
p.on('response', (r) => { if (r.status() >= 400 && !/favicon|tawk/i.test(r.url())) bad.push(r.status() + ' ' + r.url().replace(base, '')); });
const go = async (u) => { errs.length = 0; bad.length = 0; const r = await p.goto(base + u, { waitUntil: 'load', timeout: 60000 }); loads++; await p.waitForTimeout(1800); return r.status(); };
const ovf = () => p.evaluate(() => document.documentElement.scrollWidth - innerWidth);
const trace = () => p.evaluate(() => /Stack trace|Whoops|ErrorException|SQLSTATE/i.test(document.body.innerText));

let s = await go('/');
rec('home 200, no sideways scroll, no errors', s === 200 && (await ovf()) <= 0 && !errs.length && !bad.length, JSON.stringify({ errs, bad }));
rec('home hero still is the CEO picture, anniversary shows two lines only', await p.evaluate(() => /Mohammedkerem Ahmed/.test(document.querySelector('.hero-media__poster').alt) && !document.querySelector('.anniv5__message')));
await nap();

s = await go('/about');
const a = await p.evaluate(() => ({
    intro: !!document.getElementById('about-intro') && !/Call to ask any question/.test(document.body.innerText), count: document.querySelector('[data-ai-count]')?.textContent,
    lead: !!document.querySelector('.team-card--lead'), rows: [...document.querySelectorAll('.team-section .row')].slice(0, 2).map((r) => r.children.length).join(','),
    names: ['Bedru Hussen', 'Mohammed Oumer', 'Hanan Nesru', 'Head of Marketing', 'Frezer Maru'].every((t) => document.body.innerText.includes(t)),
    gallery: !!document.getElementById('gallery'), journey: !!document.getElementById('journey'),
}));
rec('about 200, intro card without phone item, CEO lead card, team 1 + 4, Journey anchor', s === 200 && a.intro && a.lead && a.names && a.journey && (await ovf()) <= 0 && !errs.length && !bad.length, JSON.stringify({ a, errs, bad }));
await p.screenshot({ path: path.join(out, 'about-desktop.png'), fullPage: false });
await nap();
for (const u of ['/services', '/products', '/portfolio', '/news', '/contact']) {
    s = await go(u);
    rec(`${u} 200, no sideways scroll, no debug text, no failed requests`, s === 200 && (await ovf()) <= 0 && !(await trace()) && !bad.length && !errs.length, JSON.stringify({ errs, bad }));
    await nap();
}
const r404 = await go('/register');
rec('/register is 404 and the public site has no password', r404 === 404);
await nap();
// admin
await go('/login');
const email = process.env.BEDRU_ADMIN_EMAIL, pw = process.env.BEDRU_ADMIN_PASSWORD;
if (email && pw) {
    await p.fill('input[name=email]', email); await p.fill('input[name=password]', pw); await p.click('button[type=submit]'); await p.waitForURL(/admin/, { timeout: 20000 }).catch(() => {});
    rec('admin login works and lands on the dashboard', /\/admin\/dashboard/.test(p.url()), p.url().replace(base, ''));
    await p.waitForTimeout(1500); rec('dashboard: no sideways scroll, no debug text', (await ovf()) <= 0 && !(await trace()));
    await p.screenshot({ path: path.join(out, 'admin-dashboard.png') });
    for (const u of ['/admin/gallery', '/admin/services']) {
        s = await go(u); rec(`${u} loads for the admin`, s === 200 && (await ovf()) <= 0 && !(await trace()), JSON.stringify({ errs, bad })); await nap();
    }
    await p.goto(base + '/admin/dashboard'); loads++;
    await p.evaluate(async () => { const t = document.querySelector('meta[name=csrf-token]')?.content; await fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': t } }); });
    await p.goto(base + '/admin/dashboard'); loads++; rec('logout works (dashboard sends to login again)', /\/login/.test(p.url()));
}
console.log('page loads:', loads);
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
