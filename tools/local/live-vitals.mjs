// Mobile LCP, CLS and transferred weight of a page (Playwright, Pixel 7, no throttling): node tools/local/live-vitals.mjs <url>...
import { chromium, devices } from 'playwright-core';
const browser = await chromium.launch();
for (const url of process.argv.slice(2)) {
    const ctx = await browser.newContext({ ...devices['Pixel 7'] }); const p = await ctx.newPage();
    let bytes = 0; const big = []; const hdr = {};
    p.on('response', async (r) => { try { const len = Number(r.headers()['content-length'] || 0); const body = len || (await r.body().catch(() => Buffer.alloc(0))).length; bytes += body; if (body > 250000) big.push(`${Math.round(body / 1024)}KB ${r.url().slice(-70)}`); const u = r.url(); if (/\.(css|js|woff2?|webp|jpg|png)(\?|$)/.test(u) && u.includes('skylinkict.com') && Object.keys(hdr).length < 6) hdr[u.slice(-40)] = `${r.headers()['cache-control'] || 'no-cache-control'} | enc=${r.headers()['content-encoding'] || 'none'}`; } catch {} });
    await p.addInitScript(() => { window.__lcp = 0; window.__cls = 0; new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__lcp = e.startTime; }).observe({ type: 'largest-contentful-paint', buffered: true }); new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; }).observe({ type: 'layout-shift', buffered: true }); });
    await p.goto(url, { waitUntil: 'load', timeout: 90000 }); await p.waitForTimeout(4000);
    const m = await p.evaluate(() => ({ lcp: Math.round(window.__lcp), cls: +window.__cls.toFixed(3) }));
    console.log(url, JSON.stringify({ ...m, transferredKB: Math.round(bytes / 1024) }), '\n  big:', big.slice(0, 6).join(' ; '), '\n  headers:', JSON.stringify(hdr));
    await ctx.close(); await new Promise((r) => setTimeout(r, 3000));
}
await browser.close();
