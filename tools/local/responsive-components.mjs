// LOCAL USE ONLY. Component checks at phone/tablet/laptop widths on a local or the bedru site:
// mobile menu open/close + links, the four marquees (running; Our Services centre highlight), the news filter chips,
// the confirmation dialog on small screens, the admin sidebar menu, and the horizontal scroll of admin tables.
//   node tools/local/responsive-components.mjs [--base http://127.0.0.1:8261] [--browser chromium|chrome|msedge|firefox|webkit]
import { chromium, firefox, webkit } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const args = process.argv.slice(2);
const opt = (n, d) => { const i = args.indexOf('--' + n); return i >= 0 ? args[i + 1] : d; };
const base = opt('base', 'http://127.0.0.1:8261').replace(/\/$/, '');
const browserName = opt('browser', 'chromium');
const remote = /bedru\.skylinkict\.com/.test(base);
const access = remote ? fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8') : '';
const creds = remote ? { username: 'staging', password: access.match(/user\s+staging\s+password\s+(\S+)/)[1] } : undefined;
const adminEmail = remote ? 'staging-admin@skylinkict.test' : process.env.ADMIN_EMAIL;
const adminPw = remote ? access.match(/email\s+staging-admin@skylinkict\.test\s+password\s+(\S+)/)[1] : process.env.ADMIN_PASSWORD;
const out = path.resolve('storage/app/staging-checks/components-' + browserName); fs.mkdirSync(out, { recursive: true });
const results = [];
const rec = (area, name, pass, note = '') => { results.push({ area, name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', area.padEnd(12), name, note ? '- ' + note : ''); };

const launcher = { chromium, chrome: chromium, msedge: chromium, firefox, webkit }[browserName];
const browser = await launcher.launch(['chrome', 'msedge'].includes(browserName) ? { channel: browserName } : {});
const ctx = await browser.newContext({ httpCredentials: creds, viewport: { width: 390, height: 844 } });
await ctx.route(/tawk\.to/, (r) => r.abort());
const page = await ctx.newPage(); page.setDefaultTimeout(90000);
const go = async (u) => { await page.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 120000 }); await page.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); await page.waitForTimeout(1200); };
const shot = (n) => page.screenshot({ path: path.join(out, n + '.png') }).catch(() => {});

// ---- public mobile menu
for (const w of [320, 390, 768, 1024]) {
    await page.setViewportSize({ width: w, height: w < 700 ? 800 : 900 });
    await go('/');
    const toggler = await page.$('.navbar-toggler');
    const togglerVisible = toggler && (await toggler.isVisible());
    if (!togglerVisible) { rec('menu', `${w}px: full menu shown inline (no toggler)`, (await page.$$eval('.navbar-nav .nav-link', (a) => a.filter((x) => x.offsetParent).length)) >= 5); continue; }
    await toggler.click(); await page.waitForTimeout(500);
    const open = await page.evaluate(() => { const c = document.querySelector('.navbar-collapse'); const links = [...document.querySelectorAll('.navbar-collapse .nav-link')].filter((a) => a.offsetParent); const inView = links.every((a) => { const r = a.getBoundingClientRect(); return r.left >= 0 && r.right <= window.innerWidth + 1; }); return { shown: c.classList.contains('show'), n: links.length, inView }; });
    rec('menu', `${w}px: toggler opens the menu, ${open.n} links fully on screen`, open.shown && open.n >= 5 && open.inView, JSON.stringify(open));
    await shot(`menu-open-${w}`);
    await toggler.click(); await page.waitForTimeout(600);
    rec('menu', `${w}px: toggler closes it again`, !(await page.evaluate(() => document.querySelector('.navbar-collapse').classList.contains('show'))));
    await toggler.click(); await page.waitForTimeout(500);
    const hrefs = await page.$$eval('.navbar-collapse a.nav-link[href]', (a) => a.map((x) => x.getAttribute('href')).filter((h) => h && !h.startsWith('#') && !h.startsWith('javascript')));
    let bad = [];
    for (const h of [...new Set(hrefs)].slice(0, 8)) { const r = await ctx.request.get(h.startsWith('http') ? h : base + h).catch(() => null); if (!r || r.status() >= 400) bad.push(`${h} ${r?.status()}`); }
    rec('menu', `${w}px: every menu link answers (${new Set(hrefs).size})`, bad.length === 0, bad.join(', '));
}

// ---- marquees
for (const w of [390, 768, 1440]) {
    await page.setViewportSize({ width: w, height: 900 });
    await go('/');
    for (let y = 0; y < 6000; y += 600) { await page.evaluate((y) => window.scrollTo(0, y), y); await page.waitForTimeout(150); }
    const sv = await page.evaluate(() => { const box = document.querySelector('.services-marquee'); if (!box) return null; box.scrollIntoView(); const track = box.querySelector('.services-track'); const cs = track && getComputedStyle(track); const r = box.getBoundingClientRect(); return { running: cs && cs.animationName !== 'none' && cs.animationPlayState === 'running', mobileScroll: getComputedStyle(box).overflowX, center: document.querySelectorAll('.service-card.is-center').length, boxW: Math.round(r.width), vw: window.innerWidth }; });
    await page.waitForTimeout(1500);
    const center = await page.evaluate(() => document.querySelectorAll('.service-card.is-center').length);
    rec('marquee', `${w}px Our Services: ${sv && sv.running ? 'moving' : 'scrollable row'}, centre highlight (${center})`, !!sv && sv.boxW <= sv.vw && (sv.running || sv.mobileScroll === 'auto') && (center >= 1 || sv.mobileScroll === 'auto'), JSON.stringify(sv));
    const others = await page.evaluate(() => ['.testimonial-track, .testimonials-track, [class*=testimonial] [class*=track]', '.products-track'].map((s) => { const t = document.querySelector(s); if (!t) return [s, null]; const cs = getComputedStyle(t); return [s.split(',')[0], cs.animationName !== 'none']; }));
    for (const [s, moving] of others) rec('marquee', `${w}px ${s}`, moving === null ? true : moving, moving === null ? 'not found (skipped)' : String(moving));
    await shot(`home-marquee-${w}`);
}
await go('/news');
for (const w of [390, 768, 1440]) {
    await page.setViewportSize({ width: w, height: 900 }); await page.waitForTimeout(600);
    const nv = await page.evaluate(() => { const t = document.querySelector('.news-videos__track'); if (!t) return null; const cs = getComputedStyle(t); return { moving: cs.animationName !== 'none', n: t.children.length }; });
    rec('marquee', `${w}px News bottom row`, !!nv && (nv.moving || w <= 600), JSON.stringify(nv));
    const chips = await page.$$eval('nav[aria-label="News categories"] a.news-chip', (a) => a.map((x) => x.textContent.replace(/\s+/g, ' ').trim()));
    rec('news', `${w}px filter shows the four options`, JSON.stringify(chips) === JSON.stringify(['All Content', 'News', 'Innovation', 'Events']), chips.join(' | '));
    const over = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    rec('news', `${w}px no sideways scroll`, over <= 1, `+${over}px`);
}

// ---- admin: sidebar menu, confirmation dialog, table scroll
if (adminEmail) {
    await page.setViewportSize({ width: 390, height: 844 });
    await go('/login');
    await page.fill('input[name=email]', adminEmail); await page.fill('input[name=password]', adminPw);
    await Promise.all([page.waitForNavigation({ timeout: 120000 }).catch(() => {}), page.click('button[type=submit]')]);
    for (const w of [320, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width: w, height: 900 });
        await go('/admin/dashboard');
        const tg = await page.$('.navbar-toggler');
        if (tg && (await tg.isVisible())) {
            await tg.click(); await page.waitForTimeout(700);
            const open = await page.evaluate(() => { const o = document.querySelector('.offcanvas.show, #sidebarMenu.show'); if (!o) return null; const r = o.getBoundingClientRect(); return { w: Math.round(r.width), left: Math.round(r.left), links: [...o.querySelectorAll('.nav-link')].filter((a) => a.offsetParent).length }; });
            rec('admin-menu', `${w}px: sidebar opens as a drawer with its links`, !!open && open.links >= 6 && open.left >= 0 && open.w <= w, JSON.stringify(open));
            await shot(`admin-menu-${w}`);
            await page.keyboard.press('Escape'); await page.waitForTimeout(500);
        } else {
            rec('admin-menu', `${w}px: sidebar always visible`, await page.evaluate(() => { const s = document.querySelector('.sidebar'); return !!s && s.getBoundingClientRect().width > 200; }));
        }
    }
    for (const w of [320, 390]) {
        await page.setViewportSize({ width: w, height: 800 });
        await go('/admin/anniversary');
        await page.check('#mode-on'); await page.click('#anniversary-form button[type=submit]');
        await page.waitForTimeout(500); // let the opening animation (starts at 98% scale) finish
        const fit = await page.evaluate(() => { const p = document.querySelector('.sky-modal__panel'); if (!p) return null; const r = p.getBoundingClientRect(); const btn = [...p.querySelectorAll('button')].every((b) => { const x = b.getBoundingClientRect(); return x.height >= 44 && x.left >= 0 && x.right <= window.innerWidth; }); return { l: Math.round(r.left), r: Math.round(window.innerWidth - r.right), t: Math.round(r.top), b: Math.round(window.innerHeight - r.bottom), buttons44: btn }; });
        rec('modal', `${w}px confirmation dialog fits the screen, buttons >= 44 px`, !!fit && fit.l >= 0 && fit.r >= 0 && fit.t >= 0 && fit.b >= 0 && fit.buttons44, JSON.stringify(fit));
        await shot(`modal-${w}`);
        await page.keyboard.press('Escape');
    }
    for (const w of [390, 768, 1024]) {
        await page.setViewportSize({ width: w, height: 900 });
        await go('/admin/inbox');
        const sc = await page.evaluate(() => { const wr = document.querySelector('.table-wrapper'); return { page: document.documentElement.scrollWidth - window.innerWidth, wrapperScrolls: wr ? wr.scrollWidth > wr.clientWidth : null }; });
        rec('admin-table', `${w}px inbox: page does not scroll sideways (table ${sc.wrapperScrolls ? 'scrolls inside its box' : 'fits'})`, sc.page <= 1, JSON.stringify(sc));
    }
}
fs.writeFileSync(path.join(out, `report-components-${browserName}.json`), JSON.stringify(results, null, 2));
await browser.close();
const bad = results.filter((r) => !r.pass);
console.log(bad.length ? `\n${bad.length} of ${results.length} FAILED` : `\nALL ${results.length} COMPONENT CHECKS PASSED (${browserName})`);
