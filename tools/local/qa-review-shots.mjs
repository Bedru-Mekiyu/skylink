// LOCAL/BEDRU review helper: full-page screenshots + console errors + failed requests + simple text checks.
//   BASE=http://127.0.0.1:8000 OUT=footage/_review/qa-local node tools/local/qa-review-shots.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
const base = process.env.BASE || 'http://127.0.0.1:8000';
const out = path.resolve(process.env.OUT || 'footage/_review/qa');
fs.mkdirSync(out, { recursive: true });
const pages = (process.env.PAGES || '/,/about').split(',');
const browser = await chromium.launch();
for (const [w, h, tag] of [[1440, 900, 'desktop'], [390, 844, 'phone']]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h } });
    for (const pg of pages) {
        const p = await ctx.newPage(); const errs = [], bad = [];
        p.on('console', (m) => { if (m.type() === 'error') errs.push(m.text().slice(0, 160)); });
        p.on('response', (r) => { if (r.status() >= 400) bad.push(r.status() + ' ' + r.url().slice(0, 120)); });
        await p.goto(base + pg, { waitUntil: 'load' }); await p.waitForTimeout(2500);
        const info = await p.evaluate(async () => {
            for (let y = 0; y < document.body.scrollHeight; y += 700) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 120)); }
            window.scrollTo(0, 0);
            const broken = [...document.images].filter((i) => i.complete && i.naturalWidth === 0 && i.currentSrc).map((i) => i.currentSrc.slice(-80));
            const txt = document.body.innerText;
            const hits = ['lorem', 'TODO', 'Full Name', 'undefined', 'NaN', 'Photo 1', 'placeholder'].filter((k) => new RegExp(k, 'i').test(txt));
            return { broken, hits, overflowX: document.documentElement.scrollWidth - innerWidth, h: document.body.scrollHeight, nav: performance.getEntriesByType('navigation')[0]?.duration | 0 };
        });
        console.log(tag, pg, JSON.stringify({ errs, bad, ...info }));
        await p.waitForTimeout(800);
        await p.screenshot({ path: path.join(out, `${pg === '/' ? 'home' : pg.slice(1).replace(/\W+/g, '-')}-${tag}.png`), fullPage: true });
        await p.close();
    }
    await ctx.close();
}
await browser.close();
