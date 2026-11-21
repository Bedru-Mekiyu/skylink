// LIVE light public check on https://skylinkict.com: pages, images/videos, console, failed requests, news video pop-up.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
const base = 'https://skylinkict.com';
const out = 'footage/_review/live-check'; fs.mkdirSync(out, { recursive: true });
const results = []; const rec = (n, pass, note = '') => { results.push(pass); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1366, height: 800 } });
const p = await ctx.newPage();
let bad = [], errs = [];
p.on('response', (r) => { if (r.status() >= 400) bad.push(r.status() + ' ' + r.url().slice(0, 110)); });
p.on('console', (m) => m.type() === 'error' && errs.push(m.text().slice(0, 140)));
p.on('requestfailed', (r) => bad.push('FAILED ' + r.url().slice(0, 110)));
const open = async (path, name) => { bad = []; errs = []; const r = await p.goto(base + path, { waitUntil: 'load', timeout: 90000 }); await p.waitForTimeout(2500); await p.screenshot({ path: `${out}/${name}.png` }); return r; };
let r = await open('/', 'home');
const home = await p.evaluate(() => ({ imgBroken: [...document.images].filter((i) => i.complete && i.naturalWidth === 0 && i.src).length, anniv: document.body.innerText.slice(0, 2500).match(/anniversar|years/i) ? true : false, ovf: document.documentElement.scrollWidth - innerWidth, footer: /Copyright © 2026/.test(document.body.innerText), mixed: [...document.querySelectorAll('[src^="http://"],link[href^="http://"]')].length }));
rec('home 200, footer 2026, no broken images, no mixed content', r.status() === 200 && home.footer && home.imgBroken === 0 && home.mixed === 0, JSON.stringify(home));
rec('home: no 4xx/5xx or console errors', bad.length === 0 && errs.length === 0, [...bad, ...errs].slice(0, 4).join(' | '));
for (const [path, name] of [['/about', 'about'], ['/services', 'services'], ['/products', 'products'], ['/portfolio', 'portfolio'], ['/news', 'news'], ['/contact', 'contact']]) {
    r = await open(path, name);
    const m = await p.evaluate(() => ({ imgBroken: [...document.images].filter((i) => i.complete && i.naturalWidth === 0 && i.src && !i.loading?.includes('lazy')).length, ovf: document.documentElement.scrollWidth - innerWidth }));
    rec(`${path} 200, no broken images, no sideways scroll`, r.status() === 200 && m.imgBroken === 0 && m.ovf <= 0, JSON.stringify(m));
    rec(`${path}: no 4xx/5xx or console errors`, bad.length === 0 && errs.length === 0, [...bad, ...errs].slice(0, 4).join(' | '));
    if (path === '/portfolio') {
        const v = await p.evaluate(() => { const x = document.querySelector('video'); return x ? { ready: x.readyState, src: x.currentSrc.slice(-40), paused: x.paused, w: x.videoWidth } : null; });
        rec('portfolio banner video present', !!v, JSON.stringify(v));
    }
    if (path === '/news') {
        const href = await p.evaluate(() => document.querySelector('a[href*="/news/"]')?.getAttribute('href'));
        const op = p.locator('[data-news-player-open]').first();
        if (await op.count()) { await op.click(); await p.waitForTimeout(8000); rec('news video pop-up opens', await p.evaluate(() => !!document.querySelector('#newsPlayer iframe, #newsPlayer video'))); await p.keyboard.press('Escape'); }
        if (href) { bad = []; errs = []; const a = await p.goto(href.startsWith('http') ? href : base + href, { waitUntil: 'load' }); await p.waitForTimeout(1500); rec('a news article opens', a.status() === 200 && bad.length === 0, href); }
    }
}
await browser.close();
process.exit(results.every(Boolean) ? 0 : 1);
