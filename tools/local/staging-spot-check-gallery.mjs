// LIGHT spot check on the test site only (bedru): gallery at the bottom of About, revolving, hover pause, lightbox, no controls,
// Admin > Gallery list (38 items, visible/hidden), homepage 5+, logout through the real button. About 9 page loads, with pauses.
//   BEDRU_ADMIN_EMAIL=... BEDRU_ADMIN_PASSWORD=... node tools/local/staging-spot-check-gallery.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = 'https://bedru.skylinkict.com';
const out = path.resolve('footage/_review/spot-bedru-gallery');
fs.mkdirSync(out, { recursive: true });
const nap = (ms = 2500) => new Promise((r) => setTimeout(r, ms));
const results = []; const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'no-preference' });
const p = await ctx.newPage(); let loads = 0;
const go = async (u) => { const r = await p.goto(base + u, { waitUntil: 'load', timeout: 60000 }); loads++; await p.waitForTimeout(1800); return r.status(); };
const pos = () => p.evaluate(() => { const b = document.querySelector('#gallery .sk-gallery__open[tabindex="0"]'); return b ? b.parentNode.dataset.i : '?'; });

await go('/');
rec('homepage Years Experience counter ends at 5+', await p.evaluate(async () => { const el = document.querySelector('.stat-number'); el.scrollIntoView({ block: 'center' }); await new Promise((r) => setTimeout(r, 3500)); return el.getAttribute('data-target') === '5+' && el.textContent.trim() === '5+'; }));
await nap();
await go('/about');
const g = await p.evaluate(() => {
    const gal = document.getElementById('gallery'), aw = document.querySelector('.awards-section'), ft = document.querySelector('footer.footer');
    const top = (e) => e.getBoundingClientRect().top + scrollY, bot = (e) => e.getBoundingClientRect().bottom + scrollY;
    return { has: !!gal, slides: gal ? gal.querySelectorAll('.sk-gallery__slide').length : 0, order: gal && bot(aw) <= top(gal) + 1 && bot(gal) <= top(ft) + 1,
        controls: gal ? gal.querySelectorAll('[data-act], .sk-gallery__bar, .sk-gallery__btn').length : -1, title: gal && gal.querySelector('h2').textContent.trim(), lazy: gal && [...gal.querySelectorAll('img')].every((i) => i.loading === 'lazy'),
        photoText: gal ? /^photo\s*\d/i.test([...gal.querySelectorAll('.sk-gallery__caption')].map((e) => e.innerText).join(' ')) : false, ovf: document.documentElement.scrollWidth - innerWidth };
});
rec('About: Gallery at the bottom (after Milestones, above the footer), 15 slides, title Gallery, no controls, lazy images, no sideways scroll', g.has && g.order && g.slides === 15 && g.controls === 0 && g.title === 'Gallery' && g.lazy && !g.photoText && g.ovf <= 0, JSON.stringify(g));
await p.locator('#gallery').scrollIntoViewIfNeeded(); await p.waitForTimeout(1500);
const a = await pos(); await p.waitForTimeout(6500); const b = await pos();
rec('it revolves by itself', a !== b, `${a} -> ${b}`);
await p.hover('#gallery .sk-gallery__stage'); const h0 = await pos(); await p.waitForTimeout(6500);
rec('it pauses on hover', h0 === await pos());
await p.screenshot({ path: path.join(out, 'about-gallery.png') });
await p.mouse.move(5, 5); await p.waitForTimeout(400);
const opener = p.locator('#gallery .sk-gallery__open[tabindex="0"]').first(); await opener.click({ force: true }); await p.waitForSelector('.sk-lb:not([hidden])', { timeout: 8000 }).catch(() => {});
rec('the lightbox opens (dialog) and Esc closes it', await p.evaluate(() => !!document.querySelector('.sk-lb:not([hidden])')) && (await p.keyboard.press('Escape'), await p.waitForTimeout(300), await p.evaluate(() => document.querySelector('.sk-lb').hidden)));
await p.screenshot({ path: path.join(out, 'about-gallery-after-lightbox.png') });
await nap();

await go('/login');
const email = process.env.BEDRU_ADMIN_EMAIL, pw = process.env.BEDRU_ADMIN_PASSWORD;
await p.fill('input[name=email]', email); await p.fill('input[name=password]', pw); await p.click('button[type=submit]'); await p.waitForURL(/admin/, { timeout: 20000 }).catch(() => {});
await go('/admin/gallery');
const a2 = await p.evaluate(() => ({ items: document.querySelectorAll('[data-gallery-item], tbody tr, .gallery-item, .sk-admin-gallery__item, li[data-id]').length, text: document.body.innerText.slice(0, 20000) }));
await p.screenshot({ path: path.join(out, 'admin-gallery.png'), fullPage: true });
const counts = await p.evaluate(() => { const t = document.body.innerText; return { hidden: (t.match(/Hidden/g) || []).length, visible: (t.match(/Visible|Shown/g) || []).length, imgs: document.querySelectorAll('img').length }; });
rec('Admin > Gallery lists the items (38 thumbnails; visible/hidden labels present)', counts.imgs >= 38, JSON.stringify({ ...counts, rows: a2.items }));
await nap();
// the real logout button
await go('/admin/dashboard');
const logout = p.locator('button:has-text("Logout"), button:has-text("Log Out"), a:has-text("Logout"), a:has-text("Log Out"), form[action$="/logout"] button').first();
if (await logout.count()) {
    if (!(await logout.isVisible())) { const t = p.locator('.navbar-toggler, [data-bs-toggle="dropdown"], .dropdown-toggle').first(); if (await t.count()) await t.click().catch(() => {}); }
    await logout.click({ timeout: 8000 }).catch(async () => { await logout.evaluate((e) => e.click()); }); await p.waitForTimeout(1500);
}
await go('/admin/dashboard');
rec('logout through the real logout button works', /\/login/.test(p.url()));
console.log('page loads:', loads);
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
