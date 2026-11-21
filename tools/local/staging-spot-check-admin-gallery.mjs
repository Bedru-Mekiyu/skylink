// LIGHT (bedru only, 4 page loads): Admin > Gallery list and the real logout button.
//   BEDRU_ADMIN_EMAIL=... BEDRU_ADMIN_PASSWORD=... node tools/local/staging-spot-check-admin-gallery.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = 'https://bedru.skylinkict.com';
const out = path.resolve('footage/_review/spot-bedru-gallery');
fs.mkdirSync(out, { recursive: true });
const results = []; const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const p = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
await p.goto(base + '/login', { waitUntil: 'load' });
await p.screenshot({ path: path.join(out, 'login.png') });
await p.fill('input[name=email]', process.env.BEDRU_ADMIN_EMAIL, { timeout: 15000 });
await p.fill('input[name=password]', process.env.BEDRU_ADMIN_PASSWORD);
await p.click('button[type=submit]'); await p.waitForURL(/admin/, { timeout: 20000 }).catch(() => {});
rec('admin login works', /\/admin\//.test(p.url()), p.url().replace(base, ''));
await p.waitForTimeout(2500);
await p.goto(base + '/admin/gallery', { waitUntil: 'load' }); await p.waitForTimeout(2000);
await p.screenshot({ path: path.join(out, 'admin-gallery.png'), fullPage: true });
const c = await p.evaluate(() => { const t = document.body.innerText; return { imgs: [...document.images].filter((i) => /\/storage\/gallery\//.test(i.src)).length, hiddenWords: (t.match(/\bHidden\b/gi) || []).length, shownWords: (t.match(/\b(Visible|Shown|Showing)\b/gi) || []).length, ovf: document.documentElement.scrollWidth - innerWidth, trace: /Stack trace|Whoops|SQLSTATE/i.test(t) }; });
rec('Admin > Gallery shows 38 thumbnails, no sideways scroll, no debug text', c.imgs === 38 && c.ovf <= 0 && !c.trace, JSON.stringify(c));
await p.waitForTimeout(2500);
await p.goto(base + '/admin/dashboard', { waitUntil: 'load' }); await p.waitForTimeout(1500);
const logout = p.locator('form[action$="/logout"] button, button:has-text("Logout"), button:has-text("Log Out"), a:has-text("Logout"), a:has-text("Log Out")').first();
if (await logout.count()) { if (!(await logout.isVisible())) { const t = p.locator('.dropdown-toggle, [data-bs-toggle="dropdown"], .navbar-toggler').first(); if (await t.count()) await t.click().catch(() => {}); } await logout.click({ timeout: 8000 }).catch(() => {}); await p.waitForTimeout(2000); }
await p.goto(base + '/admin/dashboard', { waitUntil: 'load' });
rec('logout through the real logout button works', /\/login/.test(p.url()), p.url().replace(base, ''));
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
