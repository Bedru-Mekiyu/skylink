// LIVE: About page after gallery import + marketing card: gallery visible, photos load, team layout, console clean.
import { chromium, devices } from 'playwright-core';
import fs from 'node:fs';
const out = 'footage/_review/live-check'; fs.mkdirSync(out, { recursive: true });
const results = []; const rec = (n, pass, note = '') => { results.push(pass); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
for (const [name, opts] of [['desktop', { viewport: { width: 1366, height: 800 } }], ['phone', { ...devices['Pixel 7'] }]]) {
    const ctx = await browser.newContext(opts); const p = await ctx.newPage();
    const bad = [], errs = [];
    p.on('response', (r) => { if (r.status() >= 400 && r.url().includes('skylinkict.com')) bad.push(r.status() + ' ' + r.url().slice(0, 100)); });
    p.on('console', (m) => m.type() === 'error' && !/tawk/i.test(m.text()) && errs.push(m.text().slice(0, 120)));
    const r = await p.goto('https://skylinkict.com/about', { waitUntil: 'load', timeout: 90000 });
    await p.locator('.sk-gallery, #gallery, [data-sk-gallery]').first().scrollIntoViewIfNeeded().catch(() => {});
    await p.waitForTimeout(4000);
    const m = await p.evaluate(() => {
        const g = document.querySelector('.sk-gallery, #gallery, [data-sk-gallery]');
        const imgs = g ? [...g.querySelectorAll('img')] : [];
        const team = [...document.querySelectorAll('.team-card h3')].map((e) => e.textContent.trim());
        const cards = [...document.querySelectorAll('.team-card')].map((c) => Math.round(c.getBoundingClientRect().top + scrollY));
        return { gallery: !!g, imgs: imgs.length, broken: imgs.filter((i) => i.complete && i.naturalWidth === 0 && !i.loading?.includes('lazy')).length, team, rows: [...new Set(cards.map((t) => Math.round(t / 40)))].length, brokenTeam: [...document.querySelectorAll('.team-card img')].filter((i) => i.complete && i.naturalWidth === 0).length, ovf: document.documentElement.scrollWidth - innerWidth, controls: document.querySelectorAll('.sk-gallery__bar').length };
    });
    await p.locator('.sk-gallery, #gallery').first().screenshot({ path: `${out}/about-gallery-${name}.png` }).catch(() => {});
    await p.locator('.team-section').first().screenshot({ path: `${out}/about-team-${name}.png` }).catch(() => {});
    rec(`${name}: About 200, gallery visible with photos, none broken`, r.status() === 200 && m.gallery && m.imgs > 0 && m.broken === 0, JSON.stringify({ imgs: m.imgs, broken: m.broken, controls: m.controls }));
    rec(`${name}: team has 5 cards incl. Frezer Maru, no broken portrait, no sideways scroll`, m.team.length === 5 && m.team.includes('Frezer Maru') && m.brokenTeam === 0 && m.ovf <= 0, JSON.stringify({ team: m.team, rows: m.rows, ovf: m.ovf }));
    rec(`${name}: no 4xx/5xx on our host, no console errors (Tawk excluded)`, bad.length === 0 && errs.length === 0, [...bad, ...errs].slice(0, 3).join(' | '));
    await ctx.close(); await new Promise((r) => setTimeout(r, 3000));
}
await browser.close();
process.exit(results.every(Boolean) ? 0 : 1);
