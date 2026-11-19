// LOCAL USE ONLY. Drives Admin > Anniversary Ceremony and checks what a guest and an admin see, for every
// mode, with Admin preview on and off. A saved mode must win over ANNIVERSARY_FORCE, so run it against a
// server with force ON and one with force OFF; the expected results are the same.
//   php -d display_errors=Off -S 127.0.0.1:8096 ...            (uses the .env value of ANNIVERSARY_FORCE)
//   ANNIVERSARY_FORCE=false php -d display_errors=Off -S 127.0.0.1:8097 ...
//   ADMIN_EMAIL=... ADMIN_PASSWORD=... node tools/local/verify-anniversary-admin.mjs http://127.0.0.1:8096 force-on
// Screenshots and report.json go to storage/app/ui-fixes/anniversary-admin/<label>/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8096').replace(/\/$/, '');
const label = process.argv[3] || 'run';
const out = path.resolve('storage/app/ui-fixes/anniversary-admin', label);
fs.mkdirSync(out, { recursive: true });
const report = { steps: [], failures: [] };
const fail = (m) => { report.failures.push(m); console.error('FAIL', m); };
const PREVIEW_LABEL = "Admin preview: visitors can't see this";

const browser = await chromium.launch();
const mk = async () => { const c = await browser.newContext({ viewport: { width: 1440, height: 900 } }); await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort()); return c; };
const adminCtx = await mk();
const admin = await adminCtx.newPage();
const guest = await (await mk()).newPage(); // a separate, logged-out profile with empty sessionStorage: the "private window"
admin.on('dialog', (d) => d.accept());

await admin.goto(base + '/login');
await admin.fill('input[name=email]', process.env.ADMIN_EMAIL);
await admin.fill('input[name=password]', process.env.ADMIN_PASSWORD);
await Promise.all([admin.waitForNavigation(), admin.click('button[type=submit]')]);

const look = async (page, url) => {
    // Fresh sessionStorage each time, like a new private window (the intro plays once per session).
    await page.goto(base + url, { waitUntil: 'domcontentloaded' });
    await page.evaluate(() => sessionStorage.clear());
    await page.goto(base + url, { waitUntil: 'domcontentloaded' });
    const html = await page.content();
    return {
        intro: !!(await page.$('#anniv5-intro')),
        seal: !!(await page.$('[data-anniv5-seal]')),
        anyTrace: html.includes('anniv5'),
        label: html.includes(PREVIEW_LABEL),
    };
};

const save = async ({ mode, start, end, preview }) => {
    await admin.goto(base + '/admin/anniversary');
    await admin.check(`#mode-${mode}`);
    await admin.fill('#starts_at', start);
    await admin.fill('#ends_at', end);
    await (preview ? admin.check('#admin_preview') : admin.uncheck('#admin_preview'));
    await Promise.all([admin.waitForNavigation(), admin.click('#anniversary-form button[type=submit]')]);
    return (await admin.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
};

const iso = (d) => d.toISOString().slice(0, 16);
const addis = (d) => iso(new Date(d.getTime() + 3 * 3600 * 1000)); // Addis Ababa is UTC+3, no daylight saving
const now = Date.now();
const day = 86400e3;
const windows = {
    future: [addis(new Date(now + 10 * day)), addis(new Date(now + 20 * day))],
    live: [addis(new Date(now - 2 * day)), addis(new Date(now + 20 * day))],
};

const scenarios = [
    ['off', 'future', false, false], ['off', 'future', true, false],
    ['on', 'future', false, true], ['on', 'future', true, true],
    ['auto', 'future', false, false], ['auto', 'future', true, false],
    ['auto', 'live', false, true], ['auto', 'live', true, true],
];

for (const [mode, win, preview, visitorsSee] of scenarios) {
    const name = `${mode}/${win}/preview-${preview ? 'on' : 'off'}`;
    const status = await save({ mode, start: windows[win][0], end: windows[win][1], preview });
    const row = { name, status, guest: {}, admin: {} };
    const adminSees = visitorsSee || preview;
    const labelExpected = preview && !visitorsSee;
    for (const u of ['/', '/about', '/news']) {
        const g = (row.guest[u] = await look(guest, u));
        const a = (row.admin[u] = await look(admin, u));
        if ((g.seal && g.anyTrace) !== visitorsSee) fail(`${name}: guest on ${u}: expected ${visitorsSee ? 'ceremony' : 'nothing'}`);
        if (!visitorsSee && (g.intro || g.seal || g.anyTrace || g.label)) fail(`${name}: guest on ${u} has anniversary traces`);
        if (g.label) fail(`${name}: guest on ${u} sees the admin preview label`);
        if (adminSees !== (a.seal && a.anyTrace)) fail(`${name}: admin on ${u}: expected ${adminSees ? 'ceremony' : 'nothing'}`);
        if (a.label !== labelExpected) fail(`${name}: admin on ${u}: preview label ${a.label ? 'shown' : 'missing'}, expected ${labelExpected ? 'shown' : 'hidden'}`);
        if (!adminSees && (a.anyTrace || a.label)) fail(`${name}: admin on ${u} has anniversary traces though nothing should show`);
    }
    if (labelExpected && !/Visitors: HIDDEN\. You: seeing preview/.test(status)) fail(`${name}: status line does not explain the preview: ${status}`);
    if (name === 'off/future/preview-on') await admin.screenshot({ path: path.join(out, 'admin-status-preview.png') });
    if (name === 'off/future/preview-off') await admin.screenshot({ path: path.join(out, 'admin-status-off.png') });
    report.steps.push(row);
    console.log(name.padEnd(28), 'guest sees:', String(visitorsSee).padEnd(5), 'admin sees:', String(adminSees).padEnd(5), 'label:', labelExpected);
}

// Final state the owner asked for: Automatic, Nov 23 2026 00:00 - Dec 7 2026 00:00, preview off.
report.finalStatus = await save({ mode: 'auto', start: '2026-11-23T00:00', end: '2026-12-07T00:00', preview: false });
console.log('final:', report.finalStatus);

fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
await browser.close();
console.log(report.failures.length ? 'FAILURES:\n' + report.failures.join('\n') : 'ALL CHECKS PASSED');
