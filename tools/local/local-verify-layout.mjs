// LOCAL USE ONLY. WebKit (Safari engine) at 390 px, the About banner and team cards from 768 to 1920 px, and 125% / 150% scaling.
//   node tools/local/local-verify-layout.mjs
import { chromium, webkit } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const out = path.resolve('storage/app/staging-checks/about-gallery');
const outT = path.resolve('storage/app/staging-checks/typography-webkit');
fs.mkdirSync(out, { recursive: true }); fs.mkdirSync(outT, { recursive: true });
const results = []; const rec = (n, p, note = '') => { results.push({ n, p, note }); console.log(p ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };

// ---- WebKit at 390 ----
let wk = null; try { wk = await webkit.launch(); } catch (e) { rec('WebKit (Safari engine) available', false, String(e.message).split('\n')[0]); }
if (wk) {
    const ctx = await wk.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 }); const p = await ctx.newPage();
    for (const [name, url] of [['home', '/'], ['about', '/about'], ['contact', '/contact'], ['news', '/news'], ['products', '/products'], ['services', '/services'], ['portfolio', '/portfolio']]) {
        await p.goto(base + url, { waitUntil: 'domcontentloaded', timeout: 90000 }); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); await p.waitForTimeout(1500);
        const m = await p.evaluate(() => ({ ovf: document.documentElement.scrollWidth - innerWidth, fonts: ['Inter', 'Plus Jakarta Sans'].map((f) => f + ':' + document.fonts.check('16px "' + f + '"')), body: getComputedStyle(document.body).fontSize, family: getComputedStyle(document.body).fontFamily.split(',')[0], small: [...document.querySelectorAll('body *')].filter((e) => [...e.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim()) && e.getBoundingClientRect().width && getComputedStyle(e).display !== 'none' && parseFloat(getComputedStyle(e).fontSize) < 13).length }));
        rec(`WebKit 390 ${name}: no sideways scroll, Inter + Jakarta loaded, nothing under 13px`, m.ovf <= 0 && m.fonts.every((x) => x.endsWith('true')) && m.small === 0, JSON.stringify({ ovf: m.ovf, body: m.body, small: m.small }));
        await p.screenshot({ path: path.join(outT, `${name}-390.png`), timeout: 20000 }).catch(() => {});
    }
    await ctx.close(); await wk.close();
}

// ---- About banner, team cards, 768 to 1920 and scaling ----
const b = await chromium.launch();
for (const [w, h, label] of [[768, 1024, '768'], [1024, 768, '1024'], [1366, 768, '1366'], [1440, 900, '1440'], [1920, 1080, '1920'], [1536, 864, '125% scaling (1920x1080 screen)'], [1280, 720, '150% scaling (1920x1080 screen)'], [844, 390, 'phone landscape']]) {
    const c = await b.newContext({ viewport: { width: w, height: h }, reducedMotion: 'no-preference' }); const p = await c.newPage();
    await p.goto(base + '/about', { waitUntil: 'domcontentloaded', timeout: 90000 }); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); await p.waitForTimeout(1500);
    const m = await p.evaluate(() => {
        const hero = document.querySelector('.about-hero'), r = hero.getBoundingClientRect(), nx = hero.nextElementSibling.getBoundingClientRect();
        const cards = [...document.querySelectorAll('.team-card__photo')].map((e) => { const x = e.getBoundingClientRect(); return { ratio: +(x.width / x.height).toFixed(2), radius: getComputedStyle(e).borderRadius }; });
        const nav = document.querySelector('nav, .navbar, header'); const nb = nav ? nav.getBoundingClientRect() : { bottom: 0 };
        const names = [...document.querySelectorAll('.team-card h3')].map((e) => e.textContent.trim());
        return { h: Math.round(r.height), pct: Math.round((r.height / innerHeight) * 100), gap: Math.round(nx.top - r.bottom), ovf: document.documentElement.scrollWidth - innerWidth, cards, names, old: /Jemil|Gragn|Networking Administration/.test(document.body.innerHTML), titleVisible: (() => { const t = document.querySelector('.about-hero h1'); const x = t.getBoundingClientRect(); return x.top >= nb.bottom - 2 && x.bottom <= r.bottom + 2; })() };
    });
    rec(`About @${label} (${w}x${h}): banner ${m.h}px = ${m.pct}% of the screen, title inside, no gap, no sideways scroll, 4:5 rounded team cards, Hanan present, old name gone`, m.gap === 0 && m.ovf <= 0 && m.cards.length === 4 && m.cards.every((x) => x.ratio === 0.8 && x.radius === '12px') && m.names.includes('Hanan Nesru') && !m.old && m.titleVisible, JSON.stringify({ gap: m.gap, ovf: m.ovf, title: m.titleVisible }));
    await p.screenshot({ path: path.join(out, `local-about-${w}x${h}.png`), timeout: 30000 }).catch(() => {});
    await c.close();
}
await b.close();
fs.writeFileSync(path.join(out, 'local-layout-report.json'), JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.p); console.log(`\n${results.length - failed.length}/${results.length} passed`); process.exit(failed.length ? 1 : 0);
