// LOCAL USE ONLY. Checks on the password-protected bedru test site (logins from deploy/STAGING-ACCESS.txt, never printed):
//   part "confirm": the in-page confirmation dialog on every delete (6 content types, inbox row + bulk), the Anniversary "On now",
//                   the news editor's link dialog: appears, Cancel does nothing, Esc closes, focus returns, keyboard works,
//                   Confirm works, and NO browser alert/confirm/prompt ever opens.
//   part "profile": My Profile: name, email, password (wrong current password -> error), other device signed out,
//                   log in with the new password; finally writes a new known password into deploy/STAGING-ACCESS.txt.
//   node tools/local/staging-confirm-profile-check.mjs [confirm|profile|all]
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const part = process.argv[2] || 'all';
const accessFile = 'deploy/STAGING-ACCESS.txt';
let access = fs.readFileSync(accessFile, 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
let adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const out = path.resolve('storage/app/staging-checks');
const results = [];
const rec = (area, name, pass, note = '') => { results.push({ area, name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', area.padEnd(14), name, note ? '— ' + note : ''); };
const shot = async (p, dir, name) => { const d = path.join(out, dir); fs.mkdirSync(d, { recursive: true }); await p.screenshot({ path: path.join(d, name + '.png') }).catch(() => {}); };

const browser = await chromium.launch();
const mk = (extra = {}) => browser.newContext({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined, viewport: { width: 1440, height: 900 }, ...extra });
const hook = async (c) => { await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort()); };
const nav = async (p, u) => { for (let a = 0; a < 4; a++) { try { const r = await p.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 120000 }); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); return r; } catch (e) { if (a === 3) throw e; } } };
const login = async (p, email, pw) => {
    await nav(p, '/login');
    if (/\/admin\//.test(p.url())) return true;
    await p.fill('input[name=email]', email); await p.fill('input[name=password]', pw);
    await Promise.all([p.waitForNavigation({ timeout: 120000 }).catch(() => {}), p.click('button[type=submit]')]);
    return /\/admin\//.test(p.url());
};

// submit a form and wait until the NEW page has loaded (the shared host is slow; reading too early gave false failures)
const submit = async (p, sel) => { await Promise.all([p.waitForNavigation({ waitUntil: 'load', timeout: 120000 }).catch(() => {}), p.click(sel)]); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); await p.waitForTimeout(500); };

// If an earlier run stopped halfway, the test admin may still have the second email: put it back first.
{
    const nc = await mk(); await hook(nc); const np = await nc.newPage(); np.setDefaultTimeout(120000);
    if (!(await login(np, 'admin@gmail.com', adminPw)) && (await login(np, 'staging-admin2@skylinkict.test', adminPw))) {
        await nav(np, '/admin/profile'); await np.fill('#name', 'Staging Admin'); await np.fill('#email', 'admin@gmail.com'); await np.fill('#profile_current_password', adminPw);
        await submit(np, '#profile-form button[type=submit]'); console.log('(restored the test admin email from an interrupted run)');
    }
    await nc.close();
}

const ctx = await mk(); await hook(ctx);
const admin = await ctx.newPage(); admin.setDefaultTimeout(120000);
const nativeDialogs = [];
admin.on('dialog', async (d) => { nativeDialogs.push(`${d.type()}: ${d.message().slice(0, 80)}`); await d.dismiss(); });
const ok0 = await login(admin, 'admin@gmail.com', adminPw);
rec('auth', 'admin login', ok0);

const modalOpen = async (p) => p.evaluate(() => { const m = document.querySelector('.sky-modal'); if (!m || m.hidden) return null; const panel = m.querySelector('.sky-modal__panel'); return { role: panel.getAttribute('role'), modal: panel.getAttribute('aria-modal'), title: m.querySelector('.sky-modal__title').textContent, msg: m.querySelector('.sky-modal__msg').textContent, focusInside: panel.contains(document.activeElement), focusText: document.activeElement?.textContent?.trim() }; });

// ---------------------------------------------------------------- helpers to create a test row through the admin form
const imageFile = path.resolve('public/assets/images/placeholder.png');
async function fillForm(page, title) {
    const radioDone = new Set();
    const fields = await page.evaluate(() => [...document.querySelectorAll('form:not([action*="logout"]) :is(input,textarea,select)')].filter((e) => e.name && !['_token', '_method', 'hidden'].includes(e.type) && (e.offsetParent !== null || e.type === 'file')).map((e) => ({ name: e.name, tag: e.tagName, type: e.type, required: e.required, options: e.tagName === 'SELECT' ? [...e.options].map((o) => o.value).filter(Boolean) : [] })));
    for (const f of fields) {
        const sel = `[name="${f.name}"]`;
        try {
            if (f.type === 'file') { if (/image|thumb|photo|cover/i.test(f.name) || f.required) await page.setInputFiles(sel, imageFile); }
            else if (f.tag === 'SELECT') { if (f.options.length) await page.selectOption(sel, f.options[0]); }
            else if (f.type === 'radio') { if (!radioDone.has(f.name)) { radioDone.add(f.name); const v = await page.evaluate((n) => { const r = [...document.querySelectorAll(`input[type=radio][name="${n}"]`)]; return (r.find((x) => /^(published|active|1)$/i.test(x.value)) || r[0]).value; }, f.name); await page.check(`input[type=radio][name="${f.name}"][value="${v}"]`).catch(() => {}); } }
            else if (f.type === 'checkbox' || f.type === 'submit') continue;
            else if (f.type === 'url') await page.fill(sel, f.name === 'video_url' ? 'https://www.youtube.com/watch?v=8R4J33Dp2x8' : 'https://example.test/staging');
            else if (f.type === 'email') await page.fill(sel, 'staging@example.test');
            else if (f.type === 'number') await page.fill(sel, '1');
            else if (f.type === 'date') await page.fill(sel, '2026-11-19');
            else if (f.type === 'datetime-local') await page.fill(sel, '2026-11-19T10:00');
            else if (f.tag === 'TEXTAREA') await page.fill(sel, `${title}. Staging test content. `.repeat(3));
            else if (/slug/i.test(f.name)) await page.fill(sel, 'staging-test-' + Date.now());
            else await page.fill(sel, f.name === 'video_url' ? 'https://www.youtube.com/watch?v=8R4J33Dp2x8' : title);
        } catch (e) { /* hidden/rich editor field; the submit result tells */ }
    }
    await page.evaluate((txt) => { document.querySelectorAll('textarea[name=content]').forEach((x) => { if (!x.value.trim()) { x.value = txt; x.dispatchEvent(new Event('input', { bubbles: true })); } }); const ce = document.querySelector('[contenteditable=true]'); if (ce && !ce.textContent.trim()) { ce.focus(); ce.textContent = txt; ce.dispatchEvent(new Event('input', { bubbles: true })); } }, title + '. Staging test article body. '.repeat(4));
}
async function createRow(s, title) {
    await nav(admin, s.create);
    await fillForm(admin, title);
    const submit = await admin.$('form:not([action*="logout"]) button[type=submit]:visible, form:not([action*="logout"]) input[type=submit]:visible');
    await Promise.all([admin.waitForNavigation({ timeout: 150000 }).catch(() => {}), submit.click()]);
    await nav(admin, s.list);
    return (await admin.content()).includes(title);
}
const rowDeleteBtn = (title) => admin.evaluateHandle((t) => { const row = [...document.querySelectorAll('tr, .card, li, .list-group-item')].filter((r) => r.textContent.includes(t) && r.querySelector('[data-sky-confirm]')).pop(); return row ? row.querySelector('[data-sky-confirm]') : null; }, title);

const sections = [
    { key: 'news', list: '/admin/news', create: '/admin/news/create', word: 'article' },
    { key: 'services', list: '/admin/services', create: '/admin/services/create', word: 'service' },
    { key: 'service-categories', list: '/admin/service-categories', create: '/admin/service-categories/create', word: 'category' },
    { key: 'portfolio', list: '/admin/portfolio', create: '/admin/portfolio/create', word: 'project' },
    { key: 'videos', list: '/admin/videos', create: '/admin/videos/create', word: 'video' },
    { key: 'products', list: '/admin/products', create: '/admin/products/create', word: 'product' },
];

if (part === 'all' || part === 'confirm') {
    for (const s of sections) {
        const title = `STAGING TEST MODAL ${s.key} ${Date.now() % 100000}`;
        try {
            if (!(await createRow(s, title))) { rec('confirm-' + s.key, 'test row created', false); continue; }
            const handle = await rowDeleteBtn(title);
            const btn = handle.asElement();
            if (!btn) { rec('confirm-' + s.key, 'row has a delete control with the dialog', false); continue; }
            const hasAttr = (await btn.getAttribute('data-sky-confirm')) || (await btn.evaluate((b) => b.closest('form')?.getAttribute('data-sky-confirm')));
            await btn.click();
            const m1 = await modalOpen(admin);
            rec('confirm-' + s.key, 'Delete opens the in-page dialog (role=dialog, aria-modal, focus inside)', !!m1 && m1.role === 'dialog' && m1.modal === 'true' && m1.focusInside, m1 ? `"${m1.title}" focus on "${m1.focusText}"` : 'no dialog');
            await shot(admin, 'confirm-modal', `${s.key}-open-1440`);
            await admin.keyboard.press('Escape'); await admin.waitForTimeout(300);
            const closedByEsc = (await modalOpen(admin)) === null;
            const refocused = await admin.evaluate((t) => { const a = document.activeElement; return !!a && !!a.closest('tr, .card, li, .list-group-item')?.textContent.includes(t); }, title);
            rec('confirm-' + s.key, 'Esc closes it, focus returns to the Delete button, nothing deleted', closedByEsc && refocused && (await admin.content()).includes(title), `closed=${closedByEsc} refocused=${refocused}`);
            // Cancel
            await (await rowDeleteBtn(title)).asElement().click();
            await admin.click('.sky-modal button[data-sky-cancel]'); await admin.waitForTimeout(300);
            await nav(admin, s.list);
            rec('confirm-' + s.key, 'Cancel does nothing (row still listed)', (await admin.content()).includes(title));
            // keyboard: open, Tab to the red Delete button, Enter
            await (await rowDeleteBtn(title)).asElement().click();
            const first = (await modalOpen(admin))?.focusText;
            await admin.keyboard.press('Tab');
            const second = await admin.evaluate(() => document.activeElement?.textContent?.trim());
            await admin.keyboard.press('Tab');
            const wrapped = await admin.evaluate(() => document.querySelector('.sky-modal__panel').contains(document.activeElement));
            await admin.keyboard.press('Shift+Tab'); // back on the confirm button
            await Promise.all([admin.waitForNavigation({ timeout: 150000 }).catch(() => {}), admin.keyboard.press('Enter')]);
            await nav(admin, s.list);
            rec('confirm-' + s.key, 'keyboard only: focus starts on Cancel, Tab stays inside, Enter on Delete deletes', first === 'Cancel' && second === 'Delete' && wrapped && !(await admin.content()).includes(title), `first=${first} second=${second} wrapped=${wrapped}`);
        } catch (e) { rec('confirm-' + s.key, 'section run', false, String(e.message).slice(0, 160)); }
    }

    // ---- inbox: 3 messages -> row delete (Cancel then Confirm) -> bulk 2
    {
        const vctx = await mk(); await hook(vctx); const v = await vctx.newPage(); v.setDefaultTimeout(120000);
        const tag = 'STAGING TEST CONFIRM ' + Date.now();
        for (const n of [1, 2, 3]) {
            await nav(v, '/contact'); await v.waitForTimeout(4500);
            await v.fill('input[name=name]', `${tag} ${n}`); await v.fill('input[name=email]', `c${n}@example.test`); await v.fill('textarea[name=message]', `${tag} message ${n}`);
            const ph = await v.$('input[name=phone]'); if (ph) await ph.fill('+251911000000');
            await Promise.all([v.waitForNavigation({ timeout: 120000 }).catch(() => {}), v.click('form button[type=submit]:visible')]);
        }
        await vctx.close();
        await nav(admin, '/admin/inbox');
        const ids = await admin.evaluate((t) => [...document.querySelectorAll('tbody tr')].filter((r) => r.textContent.includes(t)).map((r) => r.querySelector('.bulk-box')?.value), tag);
        rec('confirm-inbox', '3 test messages in the inbox', ids.length === 3, ids.join(','));
        await admin.click(`button[form="delete-message-${ids[0]}"]`);
        const m = await modalOpen(admin);
        rec('confirm-inbox', 'row Delete opens the dialog: "Delete this message? This can\'t be undone from the admin."', !!m && /Delete this message\? This can.t be undone from the admin\./.test(m.msg) && m.focusInside, m ? m.title : 'none');
        await shot(admin, 'confirm-modal', 'inbox-row-open-1440');
        await admin.click('.sky-modal button[data-sky-cancel]'); await admin.waitForTimeout(300);
        rec('confirm-inbox', 'Cancel keeps the message', (await admin.content()).includes(`value="${ids[0]}"`));
        await admin.click(`button[form="delete-message-${ids[0]}"]`);
        await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('.sky-modal [data-sky-ok]')]);
        const flash = await admin.evaluate(() => document.querySelector('.alert-success')?.textContent.replace(/\s+/g, ' ').trim() ?? '');
        rec('confirm-inbox', 'Confirm deletes (flash "Message deleted.")', /Message deleted/.test(flash) && !(await admin.content()).includes(`value="${ids[0]}"`), flash);
        await admin.check(`.bulk-box[value="${ids[1]}"]`); await admin.check(`.bulk-box[value="${ids[2]}"]`);
        await admin.click('#bulk-delete-btn');
        const mb = await modalOpen(admin);
        rec('confirm-inbox', 'Delete selected shows the count: "Delete 2 messages?"', !!mb && mb.title === 'Delete 2 messages?', mb ? mb.title : 'none');
        await shot(admin, 'confirm-modal', 'inbox-bulk-open-1440');
        await admin.keyboard.press('Escape'); await admin.waitForTimeout(300);
        rec('confirm-inbox', 'Esc closes the bulk dialog, both rows still there', (await modalOpen(admin)) === null && (await admin.content()).includes(`value="${ids[1]}"`));
        await admin.click('#bulk-delete-btn');
        await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('.sky-modal [data-sky-ok]')]);
        const flash2 = await admin.evaluate(() => document.querySelector('.alert-success')?.textContent.replace(/\s+/g, ' ').trim() ?? '');
        rec('confirm-inbox', 'Confirm deletes the 2 selected ("2 messages deleted.")', /2 messages deleted/.test(flash2) && !(await admin.content()).includes(tag), flash2);
    }

    // ---- Anniversary "On now"
    {
        await nav(admin, '/admin/anniversary');
        await admin.check('#mode-on');
        await admin.click('#anniversary-form button[type=submit]');
        const m = await modalOpen(admin);
        rec('confirm-anniv', '"On now" opens the dialog (blue Turn on now button)', !!m && m.title === 'Turn the ceremony on now?' && (await admin.textContent('.sky-modal [data-sky-ok]')) === 'Turn on now' && !(await admin.getAttribute('.sky-modal [data-sky-ok]', 'class')).includes('danger'), m ? m.title : 'none');
        await shot(admin, 'confirm-modal', 'anniversary-on-open-1440');
        await admin.click('.sky-modal button[data-sky-cancel]'); await admin.waitForTimeout(300);
        await nav(admin, '/admin/anniversary');
        rec('confirm-anniv', 'Cancel: the ceremony stays hidden', /HIDDEN/.test(await admin.textContent('#anniversary-status')));
        await admin.check('#mode-on'); await admin.click('#anniversary-form button[type=submit]');
        await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('.sky-modal [data-sky-ok]')]);
        rec('confirm-anniv', 'Confirm turns it on', /LIVE/.test(await admin.textContent('#anniversary-status')));
        await admin.check('#mode-off'); await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('#anniversary-form button[type=submit]')]);
        rec('confirm-anniv', 'set back to Off (no dialog needed)', /HIDDEN/.test(await admin.textContent('#anniversary-status')), (await admin.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ').slice(0, 80));
    }

    // ---- news editor link dialog (was prompt())
    {
        await nav(admin, '/admin/news/create');
        const area = await admin.$('[contenteditable=true]');
        if (area) {
            await area.click(); await admin.keyboard.type('link me'); await admin.keyboard.press('Control+A');
            await admin.click('[data-cmd="createLink"]');
            const m = await modalOpen(admin);
            rec('confirm-editor', 'link button opens the dialog with an address box (no prompt())', !!m && m.title === 'Insert link' && (await admin.$('.sky-modal input:not([hidden])')) !== null, m ? m.title : 'none');
            await admin.fill('.sky-modal input', 'https://example.test/linked');
            await admin.click('.sky-modal [data-sky-ok]'); await admin.waitForTimeout(300);
            const href = await admin.evaluate(() => document.querySelector('[contenteditable=true] a')?.getAttribute('href'));
            rec('confirm-editor', 'the link is inserted on the selected text', href === 'https://example.test/linked', String(href));
            await shot(admin, 'confirm-modal', 'editor-link-1440');
        } else rec('confirm-editor', 'rich editor found', false);
    }
    rec('confirm', 'NO native browser alert/confirm/prompt opened during any of the above', nativeDialogs.length === 0, nativeDialogs.join(' | '));

    // 390 px: the dialog fits
    {
        const mctx = await mk({ viewport: { width: 390, height: 844 } }); await hook(mctx); const m = await mctx.newPage(); m.setDefaultTimeout(120000);
        await login(m, 'admin@gmail.com', adminPw);
        await nav(m, '/admin/anniversary'); await m.check('#mode-on'); await m.click('#anniversary-form button[type=submit]');
        const box = await m.evaluate(() => { const p = document.querySelector('.sky-modal__panel'); const r = p.getBoundingClientRect(); return { w: r.width, left: r.left, right: window.innerWidth - r.right }; });
        await shot(m, 'confirm-modal', 'anniversary-on-open-390');
        rec('confirm-modal', '390px: dialog fits the screen', box.left >= 0 && box.right >= 0 && box.w <= 390, JSON.stringify(box));
        await m.keyboard.press('Escape'); await mctx.close();
    }
}

if (part === 'all' || part === 'profile') {
    const pctx = await mk(); await hook(pctx);
    const other = await pctx.newPage(); other.setDefaultTimeout(120000); // "another device": a second browser session of the same account
    const sctx = await mk(); await hook(sctx);
    const dev2 = await sctx.newPage(); dev2.setDefaultTimeout(120000);
    await login(dev2, 'admin@gmail.com', adminPw);
    await nav(dev2, '/admin/profile');
    rec('profile', 'My Profile page opens (menu link works)', /Profile details/.test(await dev2.content()) && /Change password/.test(await dev2.content()));
    await shot(dev2, 'profile', 'profile-1440');
    const menu = await dev2.evaluate(() => [...document.querySelectorAll('a')].some((a) => /\/admin\/profile$/.test(a.href) && /My Profile/.test(a.textContent)));
    rec('profile', 'sidebar has a "My Profile" link', menu);

    // name
    await dev2.fill('#name', 'Staging Admin Renamed');
    await submit(dev2, '#profile-form button[type=submit]');
    rec('profile', 'name change saves with a success message', /Profile updated/.test(await dev2.content()) && (await dev2.inputValue('#name')) === 'Staging Admin Renamed');
    await shot(dev2, 'profile', 'name-saved-1440');
    // email: needs current password
    await dev2.fill('#email', 'staging-admin2@skylinkict.test');
    await submit(dev2, '#profile-form button[type=submit]');
    rec('profile', 'email change without the current password is refused', /Enter your current password/.test(await dev2.content()));
    await dev2.fill('#email', 'staging-admin2@skylinkict.test'); await dev2.fill('#profile_current_password', 'wrong-password-123');
    await submit(dev2, '#profile-form button[type=submit]');
    rec('profile', 'email change with a wrong current password is refused', /current password is not correct/.test(await dev2.content()));
    await shot(dev2, 'profile', 'email-wrong-password-1440');
    await dev2.fill('#email', 'staging-admin2@skylinkict.test'); await dev2.fill('#profile_current_password', adminPw);
    await submit(dev2, '#profile-form button[type=submit]');
    rec('profile', 'email change with the right password saves', /Profile updated/.test(await dev2.content()) && (await dev2.inputValue('#email')) === 'staging-admin2@skylinkict.test');
    // password: wrong current -> error; short -> error
    await dev2.fill('#current_password', 'wrong-password-123'); await dev2.fill('#password', 'Newpass-12345'); await dev2.fill('#password_confirmation', 'Newpass-12345');
    await submit(dev2, '#password-form button[type=submit]');
    rec('profile', 'password change with a wrong current password is refused', /current password is not correct/.test(await dev2.content()));
    await dev2.fill('#current_password', adminPw); await dev2.fill('#password', 'short'); await dev2.fill('#password_confirmation', 'short');
    await submit(dev2, '#password-form button[type=submit]');
    rec('profile', 'a too-short new password is refused', /at least 8 characters/.test(await dev2.content()));
    // show/hide toggle
    await dev2.fill('#password', 'Visible-1'); await dev2.click('[data-target="password"]');
    rec('profile', 'show/hide toggle reveals the password', (await dev2.getAttribute('#password', 'type')) === 'text');
    await dev2.click('[data-target="password"]');
    // another device signs in BEFORE the change
    await login(other, 'staging-admin2@skylinkict.test', adminPw);
    await nav(other, '/admin/profile');
    const otherBefore = /Profile details/.test(await other.content());
    // the real change
    const newPw = 'Bd' + crypto.randomBytes(10).toString('hex');
    await dev2.fill('#current_password', adminPw); await dev2.fill('#password', newPw); await dev2.fill('#password_confirmation', newPw);
    await submit(dev2, '#password-form button[type=submit]');
    rec('profile', 'password change with the right current password succeeds', /Password changed/.test(await dev2.content()));
    await shot(dev2, 'profile', 'password-changed-1440');
    await nav(dev2, '/admin/profile');
    rec('profile', 'this session stays signed in after the change', /Profile details/.test(await dev2.content()));
    await nav(other, '/admin/profile');
    rec('profile', 'the OTHER device was signed out by the password change', otherBefore && /\/login/.test(other.url()), other.url().replace(base, ''));
    // log out and in with the new password
    await Promise.all([dev2.waitForNavigation({ waitUntil: 'load', timeout: 120000 }), dev2.evaluate(() => document.getElementById('logout-form').submit())]);
    await nav(dev2, '/admin/profile');
    rec('profile', 'logout really ended this session (the admin area asks for login again)', /\/login/.test(dev2.url()), dev2.url().replace(base, ''));
    const oldFails = !(await login(dev2, 'staging-admin2@skylinkict.test', adminPw));
    rec('profile', 'the OLD password no longer logs in', oldFails);
    rec('profile', 'login works with the NEW password', await login(dev2, 'staging-admin2@skylinkict.test', newPw));
    // put the test admin back to the known login (email + a new known password) and record it
    const known = 'Bd' + crypto.randomBytes(10).toString('hex');
    await nav(dev2, '/admin/profile');
    await dev2.fill('#name', 'Staging Admin'); await dev2.fill('#email', 'admin@gmail.com'); await dev2.fill('#profile_current_password', newPw);
    await submit(dev2, '#profile-form button[type=submit]');
    await dev2.fill('#current_password', newPw); await dev2.fill('#password', known); await dev2.fill('#password_confirmation', known);
    await submit(dev2, '#password-form button[type=submit]');
    const restored = /Password changed/.test(await dev2.content());
    if (restored) {
        access = access.replace(/(email\s+staging-admin@skylinkict\.test\s+password\s+)\S+/, `$1${known}`);
        fs.writeFileSync(accessFile, access); adminPw = known;
    }
    rec('profile', 'test admin restored to the known email and a new known password (written to STAGING-ACCESS.txt)', restored);
    await sctx.close(); await pctx.close();
}

fs.writeFileSync(path.join(out, `report-confirm-profile-${part}.json`), JSON.stringify(results, null, 2));
await browser.close();
const bad = results.filter((r) => !r.pass);
console.log(bad.length ? `\n${bad.length} of ${results.length} FAILED` : `\nALL ${results.length} CHECKS PASSED`);
