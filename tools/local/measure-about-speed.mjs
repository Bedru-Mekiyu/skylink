// LOCAL / bedru helper: load time, bytes and request count of /about (3 cold loads, median), and LCP. Used before/after the gallery import.
//   BASE=http://127.0.0.1:8000 LABEL=before node tools/local/measure-about-speed.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const label = process.env.LABEL || 'run';
const runs = Number(process.env.RUNS || 3);
const pause = Number(process.env.PAUSE || 1500);
const browser = await chromium.launch();
const res = [];
for (let i = 0; i < runs; i++) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' }); // reduced motion: no anniversary intro, no carousel timers
    const p = await ctx.newPage();
    let bytes = 0, reqs = 0, galleryImgs = 0;
    p.on('response', async (r) => { reqs++; const len = Number(r.headers()['content-length'] || 0); bytes += len; if (/\/storage\/gallery\//.test(r.url())) galleryImgs++; });
    await p.addInitScript(() => { window.__lcp = 0; new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__lcp = e.startTime; }).observe({ type: 'largest-contentful-paint', buffered: true }); });
    await p.goto(base + '/about', { waitUntil: 'load' });
    await p.waitForTimeout(1500);
    const m = await p.evaluate(() => { const n = performance.getEntriesByType('navigation')[0]; return { load: Math.round(n.loadEventEnd), dcl: Math.round(n.domContentLoadedEventEnd), lcp: Math.round(window.__lcp), cls: 0 }; });
    res.push({ ...m, kb: Math.round(bytes / 1024), reqs, galleryImgs });
    await ctx.close();
    await new Promise((r) => setTimeout(r, pause));
}
await browser.close();
const med = (k) => res.map((r) => r[k]).sort((a, b) => a - b)[Math.floor(res.length / 2)];
const out = { label, base, median: { load: med('load'), dcl: med('dcl'), lcp: med('lcp'), kb: med('kb'), reqs: med('reqs'), galleryImgsAtLoad: med('galleryImgs') }, runs: res };
console.log(JSON.stringify(out));
fs.mkdirSync('footage/_review/gallery-import', { recursive: true });
fs.writeFileSync(path.join('footage/_review/gallery-import', `speed-${label}.json`), JSON.stringify(out, null, 1));
