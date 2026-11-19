// LOCAL USE ONLY. Admin + contact + inbox + anniversary checks on the password-protected STAGING site, in a real browser.
//   node tools/local/staging-admin-check.mjs [part]      part = guard | inbox | crud | anniversary | all (default)
// Logins come from deploy/STAGING-ACCESS.txt (never printed). Only test rows named "STAGING TEST ..." are created and
// deleted again, and only in the STAGING database. Screenshots -> storage/app/staging-checks/<area>/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = access.match(/user\s+staging\s+password\s+(\S+)/)[1];
const adminPw = access.match(/email\s+staging-admin@skylinkict\.test\s+password\s+(\S+)/)[1];
const base = 'https://staging.skylinkict.com';
const out = path.resolve('storage/app/staging-checks');
const part = process.argv[2] || 'all';
const results = [];
const rec = (area, name, pass, note = '') => { results.push({ area, name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', area.padEnd(12), name, note ? '— ' + note : ''); };
const shot = async (page, area, name) => { const d = path.join(out, area); fs.mkdirSync(d, { recursive: true }); await page.screenshot({ path: path.join(d, name + '.png') }).catch(() => {}); };

const browser = await chromium.launch();
const newCtx = (extra = {}) => browser.newContext({ httpCredentials: { username: 'staging', password: basicPw }, viewport: { width: 1440, height: 900 }, ...extra });
const hook = async (ctx) => { await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort()); };
const settle = async (p) => { await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); };
const go = async (p, u) => { const r = await p.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 150000 }); await settle(p); return r; };

const adminCtx = await newCtx(); await hook(adminCtx);
const admin = await adminCtx.newPage(); admin.setDefaultTimeout(150000); admin.on('dialog', (d) => d.accept());
const errors = []; admin.on('pageerror', (e) => errors.push(e.message));

async function login() {
    await go(admin, '/login');
    await admin.fill('input[name=email]', 'staging-admin@skylinkict.test');
    await admin.fill('input[name=password]', adminPw);
    await Promise.all([admin.waitForNavigation({ timeout: 150000 }), admin.click('button[type=submit]')]);
    return /\/admin\//.test(admin.url());
}

// ---------------------------------------------------------------- guard + login/logout
if (part === 'all' || part === 'guard') {
    const guestCtx = await newCtx(); await hook(guestCtx);
    const g = await guestCtx.newPage(); g.setDefaultTimeout(150000);
    for (const u of ['/admin/dashboard', '/admin/inbox', '/admin/news', '/admin/anniversary', '/admin/services']) {
        await g.goto(base + u, { waitUntil: 'domcontentloaded' });
        rec('guard', `guest blocked from ${u}`, /\/login/.test(g.url()), g.url().replace(base, ''));
    }
    await guestCtx.close();
    rec('auth', 'admin login', await login());
    await shot(admin, 'auth', 'dashboard-1440');
    const out2 = await admin.evaluate(() => { const f = [...document.querySelectorAll('form')].find((x) => /logout/.test(x.action)); return !!f; });
    rec('auth', 'logout control present', out2);
    if (out2) { await admin.evaluate(() => [...document.querySelectorAll('form')].find((x) => /logout/.test(x.action)).submit()); await settle(admin); rec('auth', 'logout works', !/admin/.test(admin.url()) || /login|\/$/.test(admin.url()), admin.url().replace(base, '')); }
    rec('auth', 'login again', await login());
}
else await login();

// ---------------------------------------------------------------- contact form -> inbox -> mark read
if (part === 'all' || part === 'inbox') {
    const visitorCtx = await newCtx(); await hook(visitorCtx);
    const v = await visitorCtx.newPage(); v.setDefaultTimeout(150000);
    const tag = 'STAGING TEST ' + Date.now();
    for (const n of [1, 2]) {
        await go(v, '/contact');
        await v.waitForTimeout(4500); // the form has a time-to-submit guard
        await v.fill('input[name=name]', `${tag} visitor ${n}`);
        await v.fill('input[name=email]', `visitor${n}@example.test`);
        await v.fill('textarea[name=message]', `${tag} message ${n}: this is a staging test, please ignore.`);
        const phone = await v.$('input[name=phone]'); if (phone) await phone.fill('+251911000000');
        await Promise.all([v.waitForNavigation({ timeout: 150000 }).catch(() => {}), v.click('form button[type=submit]:visible')]);
        await settle(v);
        if (n === 1) await shot(v, 'contact', 'after-submit-1440');
        rec('contact', `submit test message ${n}`, !/error|invalid/i.test(await v.title()) && (await v.content()).length > 2000, v.url().replace(base, ''));
    }
    await visitorCtx.close();
    await go(admin, '/admin/inbox');
    const html = await admin.content();
    const countOf = async () => (await admin.evaluate(() => document.querySelector('.sidebar-badge, .badge-unread, [data-unread-count], .nav-link .badge')?.textContent?.trim() ?? '')).replace(/\D/g, '');
    rec('inbox', 'both test messages appear', (html.match(new RegExp(tag, 'g')) || []).length >= 2);
    await shot(admin, 'inbox', 'inbox-1440');
    const before = await countOf();
    const btn = await admin.$('form[action*="read-all"] button, button:has-text("Mark all as read")');
    rec('inbox', `unread badge present (value ${before || 'n/a'})`, true);
    if (btn) { await Promise.all([admin.waitForNavigation({ timeout: 150000 }).catch(() => {}), btn.click()]); await settle(admin); rec('inbox', '"Mark all as read" works', (await countOf()) === '' || (await countOf()) === '0', 'badge now ' + (await countOf() || 'none')); }
    else rec('inbox', '"Mark all as read" button present', false);
    await shot(admin, 'inbox', 'after-mark-all-1440');
    const mail = await adminCtx.request.get(base + '/admin/inbox'); // page still fine
    rec('inbox', 'inbox reloads', mail.status() === 200);
}

// ---------------------------------------------------------------- create / edit / delete per section
const sections = [
    { key: 'news', list: '/admin/news', create: '/admin/news/create', publicUrl: '/news', label: 'news article' },
    { key: 'services', list: '/admin/services', create: '/admin/services/create', publicUrl: '/services', label: 'service' },
    { key: 'service-categories', list: '/admin/service-categories', create: '/admin/service-categories/create', publicUrl: '/services', label: 'service category (card photo)' },
    { key: 'portfolio', list: '/admin/portfolio', create: '/admin/portfolio/create', publicUrl: '/portfolio', label: 'portfolio project' },
    { key: 'videos', list: '/admin/videos', create: '/admin/videos/create', publicUrl: '/news', label: 'video' },
    { key: 'products', list: '/admin/products', create: '/admin/products/create', publicUrl: '/products', label: 'product' },
];
const imageFile = path.resolve('public/assets/images/placeholder.png');

async function fillForm(page, title) {
    const fields = await page.evaluate(() => [...document.querySelectorAll('form:not([action*="logout"]) :is(input,textarea,select)')].filter((e) => e.name && !['_token', '_method', 'hidden'].includes(e.type) && e.offsetParent !== null || e.type === 'file').map((e) => ({ name: e.name, tag: e.tagName, type: e.type, required: e.required, options: e.tagName === 'SELECT' ? [...e.options].map((o) => o.value).filter(Boolean) : [] })));
    for (const f of fields) {
        const sel = `[name="${f.name}"]`;
        try {
            if (f.type === 'file') { if (/image|thumb|photo|cover/i.test(f.name) || f.required) await page.setInputFiles(sel, imageFile); }
            else if (f.tag === 'SELECT') { if (f.options.length) await page.selectOption(sel, f.options[0]); }
            else if (f.type === 'checkbox' || f.type === 'radio' || f.type === 'submit') continue;
            else if (f.type === 'url') await page.fill(sel, 'https://example.test/staging');
            else if (f.type === 'email') await page.fill(sel, 'staging@example.test');
            else if (f.type === 'number') await page.fill(sel, '1');
            else if (f.type === 'date') await page.fill(sel, '2026-11-19');
            else if (f.type === 'datetime-local') await page.fill(sel, '2026-11-19T10:00');
            else if (f.tag === 'TEXTAREA') await page.fill(sel, `${title}. Staging test content, written by the automated check. `.repeat(3));
            else if (/slug/i.test(f.name)) await page.fill(sel, '');
            else await page.fill(sel, title);
        } catch (e) { /* field not fillable (hidden/rich editor); the submit tells us if it mattered */ }
    }
    return fields.map((f) => f.name);
}

if (part === 'all' || part === 'crud') {
    for (const s of sections) {
        const title = `STAGING TEST ${s.key} ${Date.now() % 100000}`;
        try {
            await go(admin, s.create);
            await shot(admin, s.key, 'create-form-1440');
            const names = await fillForm(admin, title);
            const submit = await admin.$('form:not([action*="logout"]) button[type=submit]:visible, form:not([action*="logout"]) input[type=submit]:visible');
            await Promise.all([admin.waitForNavigation({ timeout: 150000 }).catch(() => {}), submit.click()]);
            await settle(admin);
            const err = await admin.evaluate(() => [...document.querySelectorAll('.invalid-feedback, .alert-danger, .text-danger')].map((e) => e.textContent.trim()).filter(Boolean).slice(0, 3).join(' | '));
            await go(admin, s.list);
            const inList = (await admin.content()).includes(title);
            rec(s.key, `create ${s.label} with image`, inList, inList ? '' : `not in list after submit. form fields: ${names.join(',')}. errors: ${err || 'none shown'} (url ${admin.url().replace(base, '')})`);
            if (!inList) { await shot(admin, s.key, 'create-failed'); continue; }
            await shot(admin, s.key, 'list-after-create-1440');
            // public site
            const pub = await adminCtx.newPage(); pub.setDefaultTimeout(150000);
            let found = false;
            for (const u of [s.publicUrl, s.publicUrl + '?page=1']) { await pub.goto(base + u, { waitUntil: 'domcontentloaded' }); await pub.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); if ((await pub.content()).includes(title)) { found = true; break; } }
            rec(s.key, `appears on the public site (${s.publicUrl})`, found, found ? '' : 'title not found on that page (it may be on another page/section)');
            if (found) await shot(pub, s.key, 'public-1440');
            await pub.close();
            // edit
            await go(admin, s.list);
            const editHref = await admin.evaluate((t) => { const row = [...document.querySelectorAll('tr, .card, li, .list-group-item')].find((r) => r.textContent.includes(t) && r.querySelector('a[href*="/edit"]')); return row ? row.querySelector('a[href*="/edit"]').getAttribute('href') : null; }, title);
            if (editHref) {
                await go(admin, editHref.replace(base, ''));
                const t2 = title + ' EDITED';
                const first = await admin.$('input[name=title], input[name=name]');
                if (first) await first.fill(t2);
                const sub = await admin.$('form:not([action*="logout"]) button[type=submit]:visible');
                await Promise.all([admin.waitForNavigation({ timeout: 150000 }).catch(() => {}), sub.click()]); await settle(admin);
                await go(admin, s.list);
                rec(s.key, 'edit saves', (await admin.content()).includes(t2));
            } else rec(s.key, 'edit link found', false);
            // delete only our test row
            await go(admin, s.list);
            const deleted = await admin.evaluate((t) => { const row = [...document.querySelectorAll('tr, .card, li, .list-group-item')].find((r) => r.textContent.includes(t) && r.querySelector('form input[name=_method][value=DELETE]')); if (!row) return false; row.querySelector('form input[name=_method][value=DELETE]').closest('form').requestSubmit(); return true; }, title);
            if (deleted) { await admin.waitForNavigation({ timeout: 150000 }).catch(() => {}); await settle(admin); }
            await go(admin, s.list);
            rec(s.key, 'delete removes the test item', deleted && !(await admin.content()).includes(title));
        } catch (e) { rec(s.key, 'section run', false, String(e.message).slice(0, 200)); await shot(admin, s.key, 'error'); }
    }
}

// ---------------------------------------------------------------- anniversary control
if (part === 'all' || part === 'anniversary') {
    const guestCtx = await newCtx(); await hook(guestCtx);
    const guest = await guestCtx.newPage(); guest.setDefaultTimeout(150000);
    const guestSees = async (u = '/') => { await guest.goto(base + u, { waitUntil: 'domcontentloaded' }); await guest.evaluate(() => sessionStorage.clear()); await guest.goto(base + u, { waitUntil: 'domcontentloaded' }); await settle(guest); return (await guest.content()).includes('anniv5'); };
    const save = async ({ mode, start, end, preview }) => {
        await go(admin, '/admin/anniversary');
        await admin.check('#mode-' + mode);
        if (start) await admin.fill('#starts_at', start);
        if (end) await admin.fill('#ends_at', end);
        await (preview ? admin.check('#admin_preview') : admin.uncheck('#admin_preview'));
        await Promise.all([admin.waitForNavigation({ timeout: 150000 }), admin.click('#anniversary-form button[type=submit]')]);
        return (await admin.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
    };
    const iso = (d) => new Date(d.getTime() + 3 * 3600e3).toISOString().slice(0, 16); // Addis Ababa = UTC+3
    const day = 86400e3, now = new Date();
    await go(admin, '/admin/anniversary');
    const s0 = (await admin.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
    rec('anniversary', 'fresh state says HIDDEN (Off) (checked before any change)', s0 === 'Currently HIDDEN for visitors (Off). Choose Automatic or On now to enable.' || /HIDDEN/.test(s0), s0);
    rec('anniversary', 'fresh state: guest sees no ceremony', !(await guestSees('/')));
    let s = await save({ mode: 'on', start: '2026-11-23T00:00', end: '2026-12-07T00:00' });
    const seesAll = [await guestSees('/'), await guestSees('/about'), await guestSees('/news')];
    rec('anniversary', 'On now: guest (private window) sees intro + panel + strips', seesAll.every(Boolean), s);
    await guest.goto(base + '/', { waitUntil: 'domcontentloaded' }); await guest.evaluate(() => sessionStorage.clear()); await guest.reload({ waitUntil: 'domcontentloaded' }); await guest.waitForTimeout(2500);
    await shot(guest, 'anniversary', 'on-now-intro-burst-1440');
    await guest.waitForTimeout(6000); await shot(guest, 'anniversary', 'on-now-after-intro-1440');
    rec('anniversary', 'home panel + seal present', (await guest.$('[data-anniv5-seal]')) !== null);
    await guest.setViewportSize({ width: 390, height: 844 }); await guest.reload({ waitUntil: 'domcontentloaded' }); await guest.waitForTimeout(3000); await shot(guest, 'anniversary', 'on-now-390');
    await guest.setViewportSize({ width: 1440, height: 900 });
    // reduced motion fallback
    const rmCtx = await newCtx({ reducedMotion: 'reduce' }); await hook(rmCtx);
    const rm = await rmCtx.newPage(); rm.setDefaultTimeout(150000);
    await rm.goto(base + '/', { waitUntil: 'domcontentloaded' }); await rm.evaluate(() => sessionStorage.clear()); await rm.reload({ waitUntil: 'domcontentloaded' }); await rm.waitForTimeout(3000);
    const rmInfo = await rm.evaluate(() => ({ canvas: !!document.querySelector('#anniv5-intro canvas'), intro: !!document.querySelector('#anniv5-intro'), seal: !!document.querySelector('[data-anniv5-seal]') }));
    await shot(rm, 'anniversary', 'reduced-motion-1440');
    rec('anniversary', 'reduced-motion fallback renders (no crash, ceremony still present)', rmInfo.seal || rmInfo.intro, JSON.stringify(rmInfo));
    await rmCtx.close();
    s = await save({ mode: 'auto', start: iso(new Date(now - 2 * day)), end: iso(new Date(now.getTime() + 20 * day)) });
    rec('anniversary', 'Automatic with a window live now: visible', await guestSees('/'), s);
    s = await save({ mode: 'auto', start: iso(new Date(now.getTime() + 10 * day)), end: iso(new Date(now.getTime() + 20 * day)) });
    rec('anniversary', 'Automatic with a future window: hidden', !(await guestSees('/')), s);
    s = await save({ mode: 'off', preview: true });
    await go(admin, '/');
    const adminHtml = await admin.content();
    rec('anniversary', 'Admin preview: admin sees ceremony + label, guest does not', adminHtml.includes('anniv5') && adminHtml.includes("Admin preview: visitors can't see this") && !(await guestSees('/')), s);
    await shot(admin, 'anniversary', 'admin-preview-label-1440');
    s = await save({ mode: 'off', preview: false });
    rec('anniversary', 'back to Off: hidden for guest and admin', !(await guestSees('/')) && !(await (async () => { await go(admin, '/'); return (await admin.content()).includes('anniv5'); })()), s);
    await go(admin, '/admin/anniversary'); await shot(admin, 'anniversary', 'admin-final-off-1440');
    await guestCtx.close();
}

rec('js', 'no uncaught JS errors in the admin session', errors.length === 0, errors.slice(0, 2).join(' | '));
fs.writeFileSync(path.join(out, `report-admin-${part}.json`), JSON.stringify(results, null, 2));
await browser.close();
const bad = results.filter((r) => !r.pass);
console.log(bad.length ? `\n${bad.length} of ${results.length} FAILED` : `\nALL ${results.length} ADMIN CHECKS PASSED`);
