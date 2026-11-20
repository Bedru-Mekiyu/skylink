// LOCAL USE ONLY. Admin > Partners, the homepage marquee, the team cards and the About banner on the STAGING site (bedru).
//   node tools/local/staging-partners-about-check.mjs <folder with test-logo.png and not-an-image.png (a text file named .png)>
// Pauses between requests on purpose: the host blocks an IP that hammers it. Login from deploy/STAGING-ACCESS.txt (never printed).
// The test partner is created and then hidden; remove it with the printed id (see the hand-over notes). Screenshots -> storage/app/staging-checks/{partners,about-gallery}/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const dir = path.resolve(process.argv[2] || '.');
const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const outP = path.resolve('storage/app/staging-checks/partners');
const outA = path.resolve('storage/app/staging-checks/about-gallery');
fs.mkdirSync(outP, { recursive: true }); fs.mkdirSync(outA, { recursive: true });
const results = [];
const rec = (name, pass, note = '') => { results.push({ name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', name, note ? '- ' + note : ''); };
const nap = (ms = 1500) => new Promise((r) => setTimeout(r, ms));

const browser = await chromium.launch();
const ctxOpts = (extra = {}) => ({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined, viewport: { width: 1440, height: 900 }, ...extra });
const admin = await (await browser.newContext(ctxOpts())).newPage(); admin.setDefaultTimeout(120000);
const go = async (p, u) => { await nap(); const r = await p.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 120000 }); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); return r; };

await go(admin, '/login');
await admin.fill('input[name=email]', 'admin@gmail.com'); await admin.fill('input[name=password]', adminPw);
await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('button[type=submit]')]);
rec('admin login', /\/admin\//.test(admin.url()));

// guests and register
const guest = await (await browser.newContext(ctxOpts())).newPage();
for (const u of ['/admin/partners', '/admin/partners/create']) { await nap(); await guest.goto(base + u, { waitUntil: 'domcontentloaded' }); rec(`guest blocked from ${u}`, /\/login/.test(guest.url()), guest.url().replace(base, '')); }
await nap(); const reg = await guest.goto(base + '/register', { waitUntil: 'domcontentloaded' }); rec('public registration is disabled (/register is 404)', reg.status() === 404, String(reg.status()));

// list
await go(admin, '/admin/partners');
const rows = await admin.locator('#partner-table tbody tr[data-id]').count();
await admin.waitForTimeout(2500); // lazy thumbnails
rec('list shows 20 per page with paging', rows === 20 && (await admin.locator('.pagination').count()) > 0, rows + ' rows');
await admin.screenshot({ path: path.join(outP, 'admin-list.png') });
const smallText = await admin.evaluate(() => [...document.querySelectorAll('.sidebar-heading')].map((e) => getComputedStyle(e).fontSize));
rec('admin sidebar headings are 13px or more', smallText.every((s) => parseFloat(s) >= 13), smallText.join(','));

// invalid upload
await go(admin, '/admin/partners/create');
await admin.fill('#name', 'ZZ Test Partner');
// NOTE: a file that contains PHP code ("<?php") is stopped by the host's firewall with a CAPTCHA before it reaches the app
// (seen on bedru; it also flags the IP). The app's own check for that case is covered by PartnerTest. Here: a text file named .png.
await admin.setInputFiles('#logo', path.join(dir, 'not-an-image.png'));
await Promise.all([admin.waitForNavigation().catch(() => {}), admin.click('#partner-submit')]);
const txt = (await admin.textContent('body')).replace(/\s+/g, ' ');
rec('a non-image renamed to .png is rejected by the app', /Only JPG, PNG or WebP|not allowed/i.test(txt) && /partners\/create|partners$/.test(admin.url()), (txt.match(/Only JPG[^.]*\./) || [''])[0]);
await admin.screenshot({ path: path.join(outP, 'admin-invalid-rejected.png') });

// valid upload
await go(admin, '/admin/partners/create');
await admin.fill('#name', 'ZZ Test Partner');
await admin.fill('#website_url', 'https://example.com/');
await admin.check('#featured');
await admin.setInputFiles('#logo', path.join(dir, 'test-logo.png'));
await admin.waitForSelector('#pv-logo:not([hidden])');
await admin.screenshot({ path: path.join(outP, 'admin-form-preview.png') });
await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('#partner-submit')]);
const okMsg = (await admin.textContent('body')).includes('ZZ Test Partner');
rec('valid logo accepted, partner added', okMsg && /partners$/.test(admin.url()));

// homepage: new partner featured first, with size attributes and webp
await nap();
const home = await browser.newContext(ctxOpts()); const hp = await home.newPage(); hp.setDefaultTimeout(120000);
await hp.goto(base + '/', { waitUntil: 'load', timeout: 120000 }); await nap(2500);
const info = await hp.evaluate(() => {
    const imgs = [...document.querySelectorAll('.partners-track .partner-logo')];
    const first = imgs[0];
    const a = first?.closest('a');
    return {
        n: imgs.length, firstSrc: first?.currentSrc || first?.src, firstW: first?.getAttribute('width'), firstH: first?.getAttribute('height'),
        picture: !!first?.closest('picture')?.querySelector('source[type="image/webp"]'), link: a ? [a.target, a.rel, a.getAttribute('aria-label')] : null,
        allSized: imgs.every((i) => i.getAttribute('width') && i.getAttribute('height')),
        h: first && Math.round(first.getBoundingClientRect().height), ovf: document.documentElement.scrollWidth - innerWidth,
    };
});
rec('homepage shows the new partner first (featured) as WebP in <picture>', /partners\/[A-Za-z0-9]{40}\.(webp|png)$/.test(info.firstSrc || '') && info.picture, info.firstSrc);
rec('linked logo opens in a new tab with rel noopener noreferrer + accessible name', info.link && info.link[0] === '_blank' && info.link[1] === 'noopener noreferrer' && /opens in a new tab/.test(info.link[2] || ''), JSON.stringify(info.link));
rec('every logo has width and height', info.allSized);
rec('logo box is 90px high, no sideways scroll', info.h === 90 && info.ovf <= 0, `h=${info.h} ovf=${info.ovf}`);
const sec = hp.locator('.partners-section');
await sec.scrollIntoViewIfNeeded(); await nap(800);
await sec.screenshot({ path: path.join(outP, 'home-marquee-after.png') });
// reduced motion: static grid
const rm = await browser.newContext(ctxOpts({ reducedMotion: 'reduce' })); const rp = await rm.newPage();
await rp.goto(base + '/', { waitUntil: 'load', timeout: 120000 }); await nap(1500);
const anim = await rp.evaluate(() => getComputedStyle(document.querySelector('.partners-track')).animationName);
const wraps = await rp.evaluate(() => getComputedStyle(document.querySelector('.partners-track')).flexWrap);
rec('reduced motion: marquee off, static wrapping grid', anim === 'none' && wraps === 'wrap', `animation=${anim} flex-wrap=${wraps}`);
await rp.locator('.partners-section').scrollIntoViewIfNeeded(); await rp.locator('.partners-section').screenshot({ path: path.join(outP, 'home-marquee-reduced-motion.png') });
await rm.close();

// hide with in-page modal, then homepage no longer shows it
await go(admin, '/admin/partners');
await admin.fill('#q', 'ZZ Test'); await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Search")')]);
const id = await admin.locator('#partner-table tbody tr[data-id]').first().getAttribute('data-id');
console.log('TEST PARTNER ID', id);
let nativeDialog = false; admin.on('dialog', (d) => { nativeDialog = true; d.dismiss(); });
await admin.click(`tr[data-id="${id}"] form[action$="/toggle"] button`);
await admin.waitForSelector('.sky-modal [data-sky-ok]', { state: 'visible' });
await admin.screenshot({ path: path.join(outP, 'admin-hide-confirm.png') });
await Promise.all([admin.waitForNavigation(), admin.click('.sky-modal [data-sky-ok]')]);
rec('hide asks in an in-page dialog (no alert) and hides', !nativeDialog && /hidden from the homepage/.test(await admin.textContent('body')));
await nap(); await hp.reload({ waitUntil: 'load' }); await nap(1500);
rec('hidden partner is gone from the homepage', !(await hp.evaluate(() => document.body.innerHTML.includes('example.com'))));

// About page: team + banner at several widths (paced)
const widths = [[320, 640], [360, 740], [390, 844], [768, 1024], [1024, 768], [1440, 900], [1920, 1080]];
for (const [w, h] of widths) {
    await hp.setViewportSize({ width: w, height: h });
    await nap(); await hp.goto(base + '/about', { waitUntil: 'load', timeout: 120000 }); await nap(1500);
    const m = await hp.evaluate(() => {
        const hero = document.querySelector('.about-hero'); if (!hero) return { missing: true, title: document.title }; const r = hero.getBoundingClientRect();
        const photos = [...document.querySelectorAll('.team-card__photo')].map((e) => { const b = e.getBoundingClientRect(); return { ratio: +(b.width / b.height).toFixed(2), radius: getComputedStyle(e).borderRadius }; });
        const names = [...document.querySelectorAll('.team-card h3')].map((e) => e.textContent.trim());
        const next = hero.nextElementSibling.getBoundingClientRect().top - r.bottom;
        return { hero: Math.round(r.height), pct: Math.round((r.height / innerHeight) * 100), photos, names, gap: Math.round(next), ovf: document.documentElement.scrollWidth - innerWidth, old: /Jemil|Gragn|Networking Administration/.test(document.body.innerHTML) };
    });
    if (m.missing) { rec(`About @${w}x${h}: page did not load (${m.title}) - host firewall?`, false); continue; }
    const okRatio = m.photos.length === 4 && m.photos.every((p) => p.ratio === 0.8 && p.radius === '12px');
    rec(`About @${w}x${h}: banner ${m.hero}px (${m.pct}%), team cards 4:5 rounded, Hanan present, no old name, no gap/overflow`, okRatio && m.names.includes('Hanan Nesru') && !m.old && m.gap === 0 && m.ovf <= 0, JSON.stringify({ gap: m.gap, ovf: m.ovf }));
    await hp.screenshot({ path: path.join(outA, `bedru-about-${w}x${h}.png`) });
}
await hp.setViewportSize({ width: 1440, height: 900 });
await nap(); await hp.goto(base + '/about', { waitUntil: 'load' }); await nap(1000);
await hp.locator('.team-section .row.g-4').first().scrollIntoViewIfNeeded(); await nap(800);
await hp.locator('.team-section .row.g-4').first().screenshot({ path: path.join(outA, 'bedru-team-cards.png') });

await browser.close();
fs.writeFileSync(path.join(outP, 'report.json'), JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
