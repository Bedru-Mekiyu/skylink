// LOCAL USE ONLY. Checks on the password-protected bedru test site (login from deploy/STAGING-ACCESS.txt, never printed):
//   1. News category filter = exactly All Content, News, Innovation, Events; each filter, search and pagination
//   2. Admin inbox: 3 test contact messages -> delete 1 with the button -> delete 2 with "Delete selected" ->
//      unread badge and list update -> the older Contact Messages page is consistent
// Screenshots: storage/app/staging-checks/news-filter/ and storage/app/staging-checks/inbox-delete/
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const out = path.resolve('storage/app/staging-checks');
const results = [];
const rec = (area, name, pass, note = '') => { results.push({ area, name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', area.padEnd(13), name, note ? '— ' + note : ''); };
const shot = async (p, dir, name) => { const d = path.join(out, dir); fs.mkdirSync(d, { recursive: true }); await p.screenshot({ path: path.join(d, name + '.png') }).catch(() => {}); };

const browser = await chromium.launch();
const mkCtx = async (w = 1440, h = 900) => { const c = await browser.newContext({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined, viewport: { width: w, height: h } }); await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort()); return c; };
const nav = async (p, u) => { for (let a = 0; a < 4; a++) { try { const r = await p.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 120000 }); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); return r; } catch (e) { if (a === 3) throw e; } } };

// ------------------------------------------------------------------ 1. news filter
for (const w of [1440, 390]) {
    const ctx = await mkCtx(w, w === 390 ? 844 : 900); const p = await ctx.newPage(); p.setDefaultTimeout(120000);
    await nav(p, '/news');
    const chips = async () => p.evaluate(() => [...document.querySelectorAll('nav[aria-label="News categories"] a.news-chip')].map((a) => a.textContent.replace(/\s+/g, ' ').trim()));
    const c = await chips();
    rec('news-filter', `${w}px chips are exactly All Content, News, Innovation, Events`, JSON.stringify(c) === JSON.stringify(['All Content', 'News', 'Innovation', 'Events']), JSON.stringify(c));
    await shot(p, 'news-filter', `all-content-${w}`);
    for (const [label, slug] of [['News', 'news'], ['Innovation', 'innovation'], ['Events', 'events']]) {
        await nav(p, `/news?category=${slug}`);
        const info = await p.evaluate(() => ({ tags: [...document.querySelectorAll('.news-tag')].map((t) => t.textContent.trim()), cards: document.querySelectorAll('#news-list article, #news-list .news-card').length, active: [...document.querySelectorAll('nav[aria-label="News categories"] a[aria-current="page"]')].map((a) => a.textContent.replace(/\s+/g, ' ').trim()) }));
        const foreign = info.tags.filter((t) => t && t !== label && !/^(News|Innovation|Events)$/.test(t) === false && t !== label);
        rec('news-filter', `${w}px filter "${label}": active chip is ${label}, list tags only ${label}`, info.active.length === 1 && info.active[0] === label && foreign.length === 0, `active=${info.active.join('|')} tags=${[...new Set(info.tags)].join('|')} cards=${info.cards}`);
        await shot(p, 'news-filter', `${slug}-${w}`);
    }
    if (w === 1440) {
        await nav(p, '/news?q=Sky');
        const hits = await p.evaluate(() => document.querySelector('#news-list h2, .news-results h2')?.textContent?.trim() ?? '');
        rec('news-filter', 'search works (result heading shown)', /result/i.test(hits), hits.slice(0, 80));
        await shot(p, 'news-filter', 'search-1440');
        await nav(p, '/news?page=2');
        const pg = await p.evaluate(() => ({ active: document.querySelector('.pagination .active')?.textContent?.trim() ?? '', links: document.querySelectorAll('.pagination a').length }));
        rec('news-filter', 'pagination works (page 2 active)', pg.active === '2', JSON.stringify(pg));
        await shot(p, 'news-filter', 'page2-1440');
        await nav(p, '/news?category=news&page=2');
        const pg2 = await p.evaluate(() => document.querySelector('.pagination .active')?.textContent?.trim() ?? '');
        rec('news-filter', 'filter + pagination together (News, page 2)', pg2 === '2', pg2);
        await nav(p, '/news?category=ict-support');
        const act = await p.evaluate(() => document.querySelectorAll('nav[aria-label="News categories"] a[aria-current="page"]').length);
        rec('news-filter', 'a service-type category in the URL is ignored (shows All Content)', act === 1, 'active chips: ' + act);
    }
    await ctx.close();
}

// ------------------------------------------------------------------ 2. inbox delete
const adminCtx = await mkCtx(); const admin = await adminCtx.newPage(); admin.setDefaultTimeout(120000);
admin.on('dialog', (d) => d.accept());
await nav(admin, '/login');
await admin.fill('input[name=email]', 'admin@gmail.com'); await admin.fill('input[name=password]', adminPw);
await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('button[type=submit]')]);
const badge = async () => admin.evaluate(() => { const b = document.querySelector('.sidebar .nav-link[href*="inbox"] .badge, .nav-link[href*="inbox"] .badge'); return b ? b.textContent.replace(/\D/g, '') : '0'; });
const unreadOnly = async () => { await nav(admin, '/admin/inbox'); return admin.evaluate(() => (document.querySelector('label[for=unread]')?.textContent.match(/\((\d+)\)/) || [])[1] ?? '?'); };

// start clean: mark everything read in the staging copy so the counts of this test are exact
await nav(admin, '/admin/inbox');
const markAll = await admin.$('form[action*="read-all"] button, button:has-text("Mark all as read")'); if (markAll) { await Promise.all([admin.waitForNavigation({ timeout: 120000 }).catch(() => {}), markAll.click()]); }
const base0 = Number(await unreadOnly());

const tag = 'STAGING TEST DELETE ' + Date.now();
const vctx = await mkCtx(); const v = await vctx.newPage(); v.setDefaultTimeout(120000);
for (const n of [1, 2, 3]) {
    await nav(v, '/contact'); await v.waitForTimeout(4500);
    await v.fill('input[name=name]', `${tag} ${n}`); await v.fill('input[name=email]', `del${n}@example.test`); await v.fill('textarea[name=message]', `${tag} message ${n}, please ignore.`);
    const ph = await v.$('input[name=phone]'); if (ph) await ph.fill('+251911000000');
    await Promise.all([v.waitForNavigation({ timeout: 120000 }).catch(() => {}), v.click('form button[type=submit]:visible')]);
}
await vctx.close();
const u3 = Number(await unreadOnly());
rec('inbox-delete', '3 test messages arrive, unread count +3', u3 === base0 + 3, `before ${base0}, now ${u3}`);
await shot(admin, 'inbox-delete', '1-list-with-3-new-1440');
const rows = await admin.evaluate((t) => [...document.querySelectorAll('tbody tr')].filter((r) => r.textContent.includes(t)).map((r) => ({ id: r.querySelector('.bulk-box')?.value, hasDeleteBtn: !!r.querySelector('button[form^="delete-message-"]'), text: r.textContent.replace(/\s+/g, ' ').slice(0, 50) })), tag);
rec('inbox-delete', 'each row has a checkbox and a Delete button', rows.length === 3 && rows.every((r) => r.id && r.hasDeleteBtn), JSON.stringify(rows.map((r) => r.id)));

// delete 1 with the row button (confirm popup is accepted by the dialog handler; check its text first)
let dialogText = '';
admin.removeAllListeners('dialog');
admin.on('dialog', async (d) => { dialogText = d.message(); await d.accept(); });
const first = rows[0];
await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click(`button[form="delete-message-${first.id}"]`)]);
await admin.waitForLoadState('load', { timeout: 60000 }).catch(() => {});
const flash1 = await admin.evaluate(() => document.querySelector('.alert-success')?.textContent.replace(/\s+/g, ' ').trim() ?? '');
rec('inbox-delete', 'Delete button asks for confirmation and shows "Message deleted."', /Delete this message\? This can.t be undone from the admin\./.test(dialogText) && /Message deleted/.test(flash1), `popup="${dialogText}" flash="${flash1}"`);
await shot(admin, 'inbox-delete', '2-after-single-delete-1440');
const u2 = Number(await unreadOnly());
rec('inbox-delete', 'after 1 delete: unread count is base+2 and the row is gone', u2 === base0 + 2 && !(await admin.content()).includes(`value="${first.id}"`), `unread ${u2}`);

// delete the other 2 with the checkboxes
const left = await admin.evaluate((t) => [...document.querySelectorAll('tbody tr')].filter((r) => r.textContent.includes(t)).map((r) => r.querySelector('.bulk-box')?.value), tag);
for (const id of left) await admin.check(`.bulk-box[value="${id}"]`);
const cnt = await admin.textContent('#bulk-count');
await shot(admin, 'inbox-delete', '3-two-selected-1440');
admin.removeAllListeners('dialog');
admin.on('dialog', async (d) => { dialogText = d.message(); await d.accept(); });
await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('#bulk-delete-btn')]);
await admin.waitForLoadState('load', { timeout: 60000 }).catch(() => {});
const flash2 = await admin.evaluate(() => document.querySelector('.alert-success')?.textContent.replace(/\s+/g, ' ').trim() ?? '');
rec('inbox-delete', '"Delete selected" (2) asks for confirmation and reports it', cnt.trim() === '2' && /2 selected messages\? This can.t be undone/.test(dialogText) && /2 messages deleted/.test(flash2), `count=${cnt} popup="${dialogText}" flash="${flash2}"`);
await shot(admin, 'inbox-delete', '4-after-bulk-delete-1440');
const u0 = Number(await unreadOnly());
const gone = !(await admin.content()).includes(tag);
rec('inbox-delete', 'unread count back to base, none of the 3 test messages listed', u0 === base0 && gone, `unread ${u0}`);
// sidebar badge excludes deleted ones too
const bdg = Number(await badge());
rec('inbox-delete', 'sidebar unread badge matches the list', bdg === base0 || (base0 === 0 && bdg === 0), `badge ${bdg}, list ${base0}`);

// older Contact Messages page is reachable and consistent (does not show the deleted messages)
await nav(admin, '/admin/contact-messages');
const oldPage = await admin.content();
rec('inbox-delete', 'older Contact Messages page opens and shows none of the deleted test messages', !oldPage.includes(tag), '');
await shot(admin, 'inbox-delete', '5-old-contact-messages-1440');

// 390 px: the list still works (cards layout, delete controls reachable)
const mctx = await mkCtx(390, 844); const m = await mctx.newPage(); m.setDefaultTimeout(120000);
await nav(m, '/login'); await m.fill('input[name=email]', 'admin@gmail.com'); await m.fill('input[name=password]', adminPw);
await Promise.all([m.waitForNavigation({ timeout: 120000 }), m.click('button[type=submit]')]);
await nav(m, '/admin/inbox');
const ov = await m.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
await shot(m, 'inbox-delete', '6-list-390');
rec('inbox-delete', '390px: no sideways scroll on the inbox list', ov <= 1, 'overflow ' + ov);
await mctx.close();

fs.writeFileSync(path.join(out, 'report-news-inbox.json'), JSON.stringify(results, null, 2));
await browser.close();
const bad = results.filter((r) => !r.pass);
console.log(bad.length ? `\n${bad.length} of ${results.length} FAILED` : `\nALL ${results.length} CHECKS PASSED`);
