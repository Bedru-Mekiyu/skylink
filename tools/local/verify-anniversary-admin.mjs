// LOCAL USE ONLY. Drives Admin > Anniversary Ceremony and checks what a guest and an admin see.
// Needs a server where ANNIVERSARY_FORCE is off, e.g.
//   ANNIVERSARY_FORCE=false php -S 127.0.0.1:8090 -t public vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
//   ADMIN_EMAIL=... ADMIN_PASSWORD=... node tools/local/verify-anniversary-admin.mjs http://127.0.0.1:8090
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8090').replace(/\/$/, '');
const out = path.resolve('storage/app/ui-fixes/anniversary-admin');
fs.mkdirSync(out, { recursive: true });
const report = { steps: [], failures: [] };
const fail = (m) => { report.failures.push(m); console.error('FAIL', m); };

const browser = await chromium.launch();
const mk = async () => { const c = await browser.newContext({ viewport: { width: 1440, height: 900 } }); await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort()); return c; };
const admin = await (await mk()).newPage();
const guestCtx = await mk(); // a separate, logged-out browser profile: the "private window"
const guest = await guestCtx.newPage();
admin.on('dialog', (d) => { report.lastDialog = d.message(); d.accept(); });

await admin.goto(base + '/login');
await admin.fill('input[name=email]', process.env.ADMIN_EMAIL);
await admin.fill('input[name=password]', process.env.ADMIN_PASSWORD);
await Promise.all([admin.waitForNavigation(), admin.click('button[type=submit]')]);

const sees = async (page, url) => {
    await page.goto(base + url, { waitUntil: 'domcontentloaded' });
    return page.evaluate(() => ({ intro: !!document.getElementById('anniv5-intro'), seal: !!document.querySelector('[data-anniv5-seal]'), styles: document.documentElement.innerHTML.includes('anniv5') }));
};
const check = async (label, expectGuest, expectAdmin) => {
    const row = { label, guest: {}, admin: {} };
    for (const u of ['/', '/about', '/news']) {
        row.guest[u] = await sees(guest, u);
        row.admin[u] = await sees(admin, u);
        const g = row.guest[u].seal && row.guest[u].styles, a = row.admin[u].seal && row.admin[u].styles;
        if (g !== expectGuest) fail(`${label}: guest on ${u} expected ${expectGuest ? 'ceremony' : 'nothing'}, got ${g}`);
        if (a !== expectAdmin) fail(`${label}: admin on ${u} expected ${expectAdmin ? 'ceremony' : 'nothing'}, got ${a}`);
        if (!expectGuest && (row.guest[u].intro || row.guest[u].seal || row.guest[u].styles)) fail(`${label}: guest on ${u} has anniversary traces`);
    }
    report.steps.push(row);
    console.log(label, 'guest:', expectGuest, 'admin:', expectAdmin);
};
const save = async ({ mode, start, end, preview, shot }) => {
    await admin.goto(base + '/admin/anniversary');
    await admin.check(`#mode-${mode}`);
    if (start) await admin.fill('#starts_at', start);
    if (end) await admin.fill('#ends_at', end);
    await (preview ? admin.check('#admin_preview') : admin.uncheck('#admin_preview'));
    await Promise.all([admin.waitForNavigation(), admin.click('#anniversary-form button[type=submit]')]);
    const status = (await admin.textContent('#anniversary-status').catch(() => null))?.trim().replace(/\s+/g, ' ');
    const err = await admin.locator('.invalid-feedback').allTextContents();
    if (shot) await admin.screenshot({ path: path.join(out, shot) });
    return { status, err };
};
const fmt = (d) => d.toISOString().slice(0, 16);
const now = new Date();
const addis = (d) => fmt(new Date(d.getTime() + 3 * 3600 * 1000)); // Addis Ababa is UTC+3, no daylight saving
const future = addis(new Date(now.getTime() + 10 * 86400e3)), later = addis(new Date(now.getTime() + 20 * 86400e3));
const past = addis(new Date(now.getTime() - 2 * 86400e3));

// 0. Nothing saved: follows the config dates (today is before them): hidden for everyone.
await admin.goto(base + '/admin/anniversary'); await admin.screenshot({ path: path.join(out, '0-default-page.png') });
report.defaultStatus = (await admin.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
await check('no settings saved (config dates)', false, false);

// 1. Automatic, window in the future + admin preview: only the admin sees it.
let r = await save({ mode: 'auto', start: future, end: later, preview: true, shot: '1-auto-future-preview.png' });
report.steps.push({ saved: 'auto future + preview', ...r }); console.log(r.status);
await check('automatic (future) + admin preview', false, true);

// 2. Preview unticked: nobody.
r = await save({ mode: 'auto', start: future, end: later, preview: false });
await check('automatic (future), preview off', false, false);

// 3. On now: confirm dialog, then everyone sees it.
r = await save({ mode: 'on', start: future, end: later, preview: false, shot: '3-on-now.png' });
report.steps.push({ saved: 'on now', ...r, confirmDialog: report.lastDialog }); console.log(r.status, '| confirm:', report.lastDialog?.split('\n')[0]);
if (!report.lastDialog) fail('no confirmation before switching to On now');
await check('on now', true, true);

// 4. Off: nobody, even with an open window.
r = await save({ mode: 'off', start: past, end: later, preview: false, shot: '4-off.png' });
console.log(r.status);
await check('off', false, false);

// 5. Automatic with a window around now: everyone.
r = await save({ mode: 'auto', start: past, end: later, preview: false, shot: '5-auto-live.png' });
console.log(r.status);
await check('automatic (window around now)', true, true);

// 6. Validation: end before start is refused and nothing changes.
r = await save({ mode: 'auto', start: later, end: past, preview: false, shot: '6-validation.png' });
report.validation = r.err; console.log('validation:', r.err);
if (!r.err.some((e) => /after the start/i.test(e))) fail('end-before-start was not rejected');
await check('after refused save (still the window around now)', true, true);

fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
await browser.close();
console.log(report.failures.length ? 'FAILURES:\n' + report.failures.join('\n') : 'ALL CHECKS PASSED');
