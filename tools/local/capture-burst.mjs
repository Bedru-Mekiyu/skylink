// LOCAL USE ONLY. Visual + performance QA for the anniversary seal burst.
// Needs a running site with the campaign active (dates, or ANNIVERSARY_FORCE=true outside production).
//   node tools/local/capture-burst.mjs http://127.0.0.1:8080 before|after [--perf]
// Writes videos (3 loops), burst-moment stills, report.json to storage/app/anniversary-captures/burst-upgrade/<tag>/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const tag = process.argv[3] || 'after';
const perf = process.argv.includes('--perf');
const out = path.resolve('storage/app/anniversary-captures/burst-upgrade', tag);
fs.mkdirSync(out, { recursive: true });
const report = { base, tag, runs: [], perf: null, reducedMotion: null, failures: [] };
const fail = (m) => { report.failures.push(m); console.error('FAIL', m); };

const browser = await chromium.launch();
const prep = async (ctx) => {
    await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    await ctx.addInitScript(() => { try { sessionStorage.setItem('skylink.anniv5.introSeen', '1'); } catch (e) {} });
};
// Position in the seal's 8s loop, read from the CSS animation the burst is synced to.
const cycleMs = (page) => page.evaluate(() => {
    const five = [...document.querySelectorAll('.anniv5-mark__n--5')].find((e) => e.getBoundingClientRect().width);
    const a = five && five.getAnimations().find((x) => x.animationName === 'anniv5-five');
    if (!a) return null;
    const d = a.effect.getTiming().duration;
    return ((a.currentTime % d) + d) % d;
});
const waitForCycle = async (page, at) => {
    const t = await cycleMs(page);
    if (t === null) return false;
    await page.waitForTimeout(((at - t) + 8000) % 8000);
    return true;
};

const runs = [
    ['/', 1440, 900], ['/', 1024, 768], ['/', 768, 1024], ['/', 390, 844], ['/', 320, 700],
    ['/about', 1440, 900], ['/services', 1440, 900], ['/news', 390, 844], ['ARTICLE', 390, 844],
];
let article = '/news';
{
    const ctx = await browser.newContext(); await prep(ctx); const p = await ctx.newPage();
    await p.goto(base + '/news', { waitUntil: 'domcontentloaded' });
    article = await p.evaluate(() => [...document.querySelectorAll('a[href*="/news/"]')].map((a) => new URL(a.href).pathname)[0]) || '/news';
    await ctx.close();
}

for (const [uriRaw, w, h] of runs) {
    const uri = uriRaw === 'ARTICLE' ? article : uriRaw;
    const name = (uri === '/' ? 'home' : uri.slice(1).replace(/\//g, '-').slice(0, 40)) + '-' + w;
    const video = w === 1440 || w === 390 ? { dir: out, size: { width: w, height: h } } : undefined;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, recordVideo: video });
    await prep(ctx);
    const page = await ctx.newPage();
    await page.goto(base + uri, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load', { timeout: 15000 }).catch(() => {});
    await page.evaluate(() => document.fonts.ready);
    const seal = page.locator('[data-anniv5-seal]:visible').first();
    await seal.scrollIntoViewIfNeeded();
    await page.waitForTimeout(300);
    const box = await seal.boundingBox();
    const pad = uri === '/' ? 220 : 0;
    const clip = { x: 0, y: Math.max(0, box.y - pad), width: w, height: Math.min(h - Math.max(0, box.y - pad), box.height + pad + 40) };
    for (const [at, label] of [[3700, 'anticipation'], [4060, 'impact'], [4350, 'burst'], [4900, 'crackle'], [6000, 'glitter']]) {
        if (!(await waitForCycle(page, at))) { fail(`${name}: seal animation not found`); break; }
        await page.screenshot({ path: path.join(out, `${name}-${label}.png`), clip });
    }
    // 3 full loops on video, sampling sideways scroll and anything drawn over the text.
    const checks = await page.evaluate(async () => {
        const res = { overflowX: 0, fxOverText: false, canvases: 0 };
        const t0 = performance.now();
        while (performance.now() - t0 < 24500) {
            res.overflowX = Math.max(res.overflowX, document.documentElement.scrollWidth - window.innerWidth);
            for (const c of document.querySelectorAll('.anniv5__fx')) {
                if (!c.getBoundingClientRect().width) continue;
                const r = c.getBoundingClientRect();
                const seal = c.closest('[data-anniv5-seal]');
                for (const t of seal.querySelectorAll('.anniv5__copy p, .anniv5__copy h2')) {
                    const q = t.getBoundingClientRect();
                    if (r.left < q.right && r.right > q.left && r.top < q.bottom && r.bottom > q.top) res.fxOverText = true;
                }
                // Hero text on the homepage.
                for (const t of document.querySelectorAll('.hero-content h1, .hero-content p, .hero-content a, .navbar')) {
                    const q = t.getBoundingClientRect();
                    if (q.width && r.left < q.right && r.right > q.left && r.top < q.bottom && r.bottom > q.top) res.fxOverText = true;
                }
            }
            res.canvases = document.querySelectorAll('.anniv5__fx').length;
            await new Promise((r) => setTimeout(r, 250));
        }
        return res;
    });
    const row = { uri, width: w, ...checks };
    report.runs.push(row);
    if (row.overflowX > 0) fail(`${name}: sideways scroll ${row.overflowX}px`);
    if (row.fxOverText) fail(`${name}: burst layer overlaps text`);
    const v = page.video();
    await ctx.close();
    if (v) fs.renameSync(await v.path(), path.join(out, `${name}-3-loops.webm`));
}

if (perf) {
    // Mobile proxy: 4x CPU throttling, 390px, 10 loops. Frame times during bursts + JS heap growth.
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
    await prep(ctx);
    const page = await ctx.newPage();
    const cdp = await ctx.newCDPSession(page);
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load', { timeout: 15000 }).catch(() => {});
    await page.locator('[data-anniv5-seal]:visible').first().scrollIntoViewIfNeeded();
    await cdp.send('Performance.enable');
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    await cdp.send('HeapProfiler.collectGarbage');
    const heap0 = (await cdp.send('Runtime.getHeapUsage')).usedSize;
    report.perf = await page.evaluate(async () => {
        const frames = []; let last = performance.now(); let run = true;
        const loop = (now) => { frames.push(now - last); last = now; if (run) requestAnimationFrame(loop); };
        requestAnimationFrame(loop);
        let long = 0; try { new PerformanceObserver((l) => { long += l.getEntries().length; }).observe({ type: 'longtask', buffered: false }); } catch (e) {}
        await new Promise((r) => setTimeout(r, 80000)); // 10 loops
        run = false;
        const sorted = frames.slice(1).sort((a, b) => a - b);
        const pct = (p) => Math.round(sorted[Math.floor(sorted.length * p)] * 10) / 10;
        return { frames: sorted.length, p50: pct(0.5), p95: pct(0.95), p99: pct(0.99), over50ms: sorted.filter((f) => f > 50).length, longTasks: long };
    });
    await cdp.send('HeapProfiler.collectGarbage');
    report.perf.heapGrowthKB = Math.round(((await cdp.send('Runtime.getHeapUsage')).usedSize - heap0) / 1024);
    report.perf.particlesAfter = await page.evaluate(() => (window.__anniv5Fx || []).map((f) => f.particleCount()));
    await ctx.close();
}

// Reduced motion: still gold 5, nothing animating, no burst layer.
{
    const ctx = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1440, height: 900 } });
    await prep(ctx);
    const page = await ctx.newPage();
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load', { timeout: 15000 }).catch(() => {});
    const seal = page.locator('[data-anniv5-seal]:visible').first();
    await seal.scrollIntoViewIfNeeded();
    await page.waitForTimeout(4500);
    report.reducedMotion = await page.evaluate(() => {
        const s = [...document.querySelectorAll('[data-anniv5-seal]')].find((e) => e.offsetParent);
        return {
            fiveOpacity: getComputedStyle(s.querySelector('.anniv5-mark__n--5')).opacity,
            runningAnimations: s.getAnimations({ subtree: true }).filter((a) => a.playState === 'running').length,
            fxCanvases: s.querySelectorAll('.anniv5__fx').length,
        };
    });
    await seal.screenshot({ path: path.join(out, 'reduced-motion-home-1440.png') });
    if (report.reducedMotion.fiveOpacity !== '1' || report.reducedMotion.runningAnimations || report.reducedMotion.fxCanvases) fail('reduced motion is not still');
    await ctx.close();
}

await browser.close();
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
console.log(`${report.runs.length} runs, ${report.failures.length} failures. perf: ${JSON.stringify(report.perf)}. ${out}`);
