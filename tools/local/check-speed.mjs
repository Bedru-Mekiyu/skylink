// Speed check: loads every page like a mid-range phone on a slow mobile
// connection with an empty cache, and reports transferred size, requests,
// FCP, LCP, CLS and blocking time (median of the runs).
// LOCAL USE ONLY. Needs the site running (start-site.bat).
//
//   node tools/local/check-speed.mjs [--label=before] [--runs=3] [--pages=home,about]
//
// Throttling (Chrome DevTools, same as Lighthouse's mobile "slow 4G" with
// DevTools throttling): 562.5 ms latency, 1.47 Mbit/s down, 0.68 Mbit/s up,
// CPU 4x slower. Each run stops at 60 s; slower pages show ">60 s".
// Note: the local PHP server does not compress files or send cache headers,
// so the live site with htaccess-speed-append.txt will be faster than this.
// Results: footage/_review/speed/<label>.md and <label>.json
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
import { BASE, PAGES, args, siteIsUp, stamp } from './pages.mjs';

const opt = args();
const label = opt.label || 'run-' + stamp();
const runs = Math.max(1, parseInt(opt.runs || '3', 10));
const CAP = 60000;
const outDir = path.resolve('footage/_review/speed');
fs.mkdirSync(outDir, { recursive: true });
const pages = opt.pages ? PAGES.filter(([n]) => opt.pages.split(',').includes(n)) : PAGES;
const siteHost = new URL(BASE).host;

if (!(await siteIsUp())) {
    console.error(`The site is not running at ${BASE}. Start it first with start-site.bat.`);
    process.exit(2);
}

// Collects paint, layout-shift and long-task timings from the first moment.
const PERF_INIT = () => {
    window.__perf = { lcp: 0, cls: 0, fcp: 0, long: [] };
    const obs = (type, fn) => { try { new PerformanceObserver(l => l.getEntries().forEach(fn)).observe({ type, buffered: true }); } catch {} };
    obs('largest-contentful-paint', e => { window.__perf.lcp = e.startTime; });
    obs('layout-shift', e => { if (!e.hadRecentInput) window.__perf.cls += e.value; });
    obs('paint', e => { if (e.name === 'first-contentful-paint') window.__perf.fcp = e.startTime; });
    obs('longtask', e => { window.__perf.long.push([e.startTime, e.duration]); });
};

const browser = await chromium.launch();
async function once(url) {
    const ctx = await browser.newContext({ viewport: { width: 412, height: 823 }, deviceScaleFactor: 1.75, isMobile: true, hasTouch: true });
    await ctx.addInitScript(PERF_INIT);
    const page = await ctx.newPage();
    const cdp = await ctx.newCDPSession(page);
    await cdp.send('Network.enable');
    await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
    await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 562.5, downloadThroughput: 1474.56 * 1024 / 8, uploadThroughput: 675 * 1024 / 8 });
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    const urls = new Map();
    let bytes = 0, own = 0, requests = 0, biggest = [];
    cdp.on('Network.requestWillBeSent', e => { if (!urls.has(e.requestId)) requests++; urls.set(e.requestId, e.request.url); });
    cdp.on('Network.loadingFinished', e => {
        bytes += e.encodedDataLength;
        const u = urls.get(e.requestId) || '';
        if (u.includes(siteHost)) own += e.encodedDataLength;
        biggest.push([e.encodedDataLength, u]);
    });
    const t0 = Date.now();
    let loadMs = null;
    try {
        await page.goto(BASE + url, { waitUntil: 'load', timeout: CAP });
        loadMs = Date.now() - t0;
    } catch { /* capped */ }
    await page.waitForTimeout(loadMs === null ? 0 : 3000);
    const p = await page.evaluate(() => window.__perf).catch(() => ({ lcp: 0, cls: 0, fcp: 0, long: [] }));
    const tbt = p.long.filter(([s]) => s >= p.fcp).reduce((a, [, d]) => a + Math.max(0, d - 50), 0);
    biggest = biggest.sort((a, b) => b[0] - a[0]).slice(0, 3).map(([b, u]) => `${Math.round(b / 1024)} KB ${u.replace(BASE, '').split('?')[0].slice(-60)}`);
    await ctx.close();
    return { kb: bytes / 1024, ownKb: own / 1024, requests, fcp: p.fcp, lcp: p.lcp, cls: p.cls, tbt, load: loadMs, capped: loadMs === null, biggest };
}

const median = a => { const s = [...a].sort((x, y) => x - y); return s[Math.floor((s.length - 1) / 2)]; };
const results = [];
for (const [name, url] of pages) {
    const r = [];
    for (let i = 0; i < runs; i++) { r.push(await once(url)); process.stdout.write('.'); }
    const m = k => median(r.map(x => x[k] ?? CAP));
    results.push({ page: name, url, runs: r, kb: m('kb'), ownKb: m('ownKb'), requests: m('requests'), fcp: m('fcp'), lcp: m('lcp'), cls: m('cls'), tbt: m('tbt'), load: m('load'), capped: r.some(x => x.capped), biggest: r[0].biggest });
}
await browser.close();

const s = ms => ms >= CAP ? '>60 s' : (ms / 1000).toFixed(1) + ' s';
const lines = [`# Speed check: ${label}`, '', `Phone 412x823, slow 4G (562.5 ms, 1.47 Mbit/s), CPU 4x slower, empty cache, median of ${runs} run(s). Local server, no compression. ${new Date().toISOString()}`, '',
    '| Page | Transferred (all / this site) | Requests | FCP | LCP | CLS | Blocking time | Load | Biggest download |', '|---|---|---|---|---|---|---|---|---|'];
for (const r of results) {
    lines.push(`| ${r.page} | ${Math.round(r.kb).toLocaleString('en')} KB / ${Math.round(r.ownKb).toLocaleString('en')} KB${r.capped ? ' (stopped at 60 s)' : ''} | ${r.requests} | ${s(r.fcp)} | ${r.lcp ? s(r.lcp) : 'n/a'} | ${r.cls.toFixed(3)} | ${Math.round(r.tbt)} ms | ${r.capped ? '>60 s' : s(r.load)} | ${r.biggest[0] || ''} |`);
}
fs.writeFileSync(path.join(outDir, label + '.json'), JSON.stringify(results, null, 1));
fs.writeFileSync(path.join(outDir, label + '.md'), lines.join('\n') + '\n');
console.log('\n' + lines.join('\n'));
console.log(`\nSaved in ${path.join(outDir, label + '.md')}`);
