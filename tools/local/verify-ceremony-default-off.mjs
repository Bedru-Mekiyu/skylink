// LOCAL USE ONLY. On a FRESH database (nothing saved) the ceremony must be hidden for guests even inside Nov 23 - Dec 7
// (run the server with a frozen clock, see deploy/README-local-checks in the guide), and follow the admin's choice after.
//   ADMIN_EMAIL=... ADMIN_PASSWORD=... node tools/local/verify-ceremony-default-off.mjs http://127.0.0.1:8211
import { chromium } from 'playwright-core';

const base = (process.argv[2] || 'http://127.0.0.1:8211').replace(/\/$/, '');
const fails = [];
const ok = (c, m) => { console.log(c ? 'ok  ' : 'FAIL', m); if (!c) fails.push(m); };

const browser = await chromium.launch();
const mk = async () => { const c = await browser.newContext({ viewport: { width: 1440, height: 900 } }); await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort()); return c; };
const guest = await (await mk()).newPage();
const admin = await (await mk()).newPage();
admin.on('dialog', (d) => d.accept());

const guestSees = async () => {
    const out = {};
    for (const u of ['/', '/about', '/news']) {
        await guest.goto(base + u, { waitUntil: 'domcontentloaded' });
        await guest.evaluate(() => sessionStorage.clear());
        await guest.goto(base + u, { waitUntil: 'domcontentloaded' });
        out[u] = (await guest.content()).includes('anniv5');
    }
    return out;
};

await admin.goto(base + '/login');
await admin.fill('input[name=email]', process.env.ADMIN_EMAIL);
await admin.fill('input[name=password]', process.env.ADMIN_PASSWORD);
await Promise.all([admin.waitForNavigation(), admin.click('button[type=submit]')]);

// 1. fresh database
await admin.goto(base + '/admin/anniversary');
const fresh = (await admin.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
ok(fresh === 'Currently HIDDEN for visitors (Off). Choose Automatic or On now to enable.', 'fresh status line: ' + fresh);
ok(await admin.isChecked('#mode-off'), 'Off is preselected');
ok((await admin.inputValue('#starts_at')) === '2026-11-23T00:00' && (await admin.inputValue('#ends_at')) === '2026-12-07T00:00', 'dates pre-filled Nov 23 - Dec 7');
let g = await guestSees();
ok(Object.values(g).every((v) => !v), 'fresh DB, clock inside the dates: guest sees no ceremony ' + JSON.stringify(g));
await admin.screenshot({ path: 'storage/app/ui-fixes/default-off/admin-fresh.png' });

const save = async (mode) => {
    await admin.goto(base + '/admin/anniversary');
    await admin.check('#mode-' + mode);
    await Promise.all([admin.waitForNavigation(), admin.click('#anniversary-form button[type=submit]')]);
    return (await admin.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
};
// 2. On now -> visible
let s = await save('on');
g = await guestSees();
ok(/LIVE/.test(s) && Object.values(g).every(Boolean), 'On now: guest sees it ' + JSON.stringify(g) + ' | ' + s);
// 3. back to Off -> hidden
s = await save('off');
g = await guestSees();
ok(/HIDDEN/.test(s) && Object.values(g).every((v) => !v), 'Off again: hidden ' + JSON.stringify(g) + ' | ' + s);
// 4. Automatic with the default dates and the clock inside them -> visible
s = await save('auto');
g = await guestSees();
ok(/LIVE/.test(s) && Object.values(g).every(Boolean), 'Automatic (clock inside dates): guest sees it | ' + s);

await browser.close();
console.log(fails.length ? `\n${fails.length} FAILED` : '\nALL CHECKS PASSED');
process.exit(fails.length ? 1 : 0);
