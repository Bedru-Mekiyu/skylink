// LOCAL USE ONLY. Visual QA for the 5-year anniversary campaign.
// Needs a running site with the campaign active (dates, or ANNIVERSARY_FORCE=true outside production).
//   node tools/local/capture-anniversary.mjs http://127.0.0.1:8081
// Writes videos, screenshots and report.json to storage/app/anniversary-captures/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const out = path.resolve('storage/app/anniversary-captures');
fs.mkdirSync(path.join(out, 'shots'), { recursive: true });

const pages = ['/', '/about', '/services', '/services/category/software-service', '/products', '/portfolio', '/news', '/contact'];
const widths = [1440, 1024, 768, 390, 320];
const report = { base, intro: {}, pages: [], reducedMotion: {}, failures: [] };
const fail = (msg) => { report.failures.push(msg); console.error('FAIL', msg); };

const browser = await chromium.launch();
await fetch(base + '/').catch(() => {}); // warm the dev server so the first timed run is not a cold start

// 1. Intro + three seal loops on video (desktop and phone).
for (const [label, viewport] of [['desktop', { width: 1440, height: 900 }], ['phone', { width: 390, height: 844 }]]) {
    const ctx = await browser.newContext({ viewport, recordVideo: { dir: out, size: viewport } });
    const page = await ctx.newPage();
    await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    // Record every phase change in the page itself: screenshots under video encoding are too slow to be the proof.
    await ctx.addInitScript(() => {
        window.__phases = [];
        new MutationObserver(() => {
            const el = document.getElementById('anniv5-intro');
            const p = el && el.getAttribute('data-phase');
            if (p && window.__phases[window.__phases.length - 1] !== p) window.__phases.push(p);
        }).observe(document, { subtree: true, attributes: true, attributeFilter: ['data-phase'] });
    });
    await page.goto(base + '/', { waitUntil: 'commit' });
    const started = Date.now();
    for (const [phase, delay, name] of [['launch', 150, 'ignition'], ['launch', 900, 'launch'], ['n2', 60, 'countdown'], ['five', 180, 'burst'], ['title', 450, 'title']]) {
        // Best effort: on a loaded machine a beat can pass before the screenshot is taken.
        if (await page.waitForSelector(`#anniv5-intro[data-phase="${phase}"]`, { timeout: 4000 }).then(() => true, () => false)) {
            await page.waitForTimeout(delay);
            await page.screenshot({ path: path.join(out, 'shots', `intro-${label}-${name}.png`) });
        }
    }
    await page.waitForSelector('#anniv5-intro', { state: 'detached', timeout: 8000 }).catch(() => fail(`${label}: intro did not dismiss itself`));
    const phases = await page.evaluate(() => window.__phases);
    report.intro[label] = { secondsUntilDismissed: (Date.now() - started) / 1000, phases };
    if (phases.join() !== 'launch,n1,n2,n3,n4,five,title,out') fail(`${label}: intro beats out of order: ${phases.join()}`);
    const seal = page.locator('[data-anniv5-seal]:visible').first();
    await seal.scrollIntoViewIfNeeded();
    await page.waitForTimeout(24500); // three 8s loops
    await seal.screenshot({ path: path.join(out, 'shots', `seal-${label}-settled.png`) });
    const video = page.video();
    await ctx.close();
    fs.renameSync(await video.path(), path.join(out, `intro-and-loops-${label}.webm`));

    // Same session: the intro must not replay on the next page.
    const ctx2 = await browser.newContext({ viewport });
    const p2 = await ctx2.newPage();
    await p2.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await p2.keyboard.press('Escape');
    await p2.waitForSelector('#anniv5-intro', { state: 'detached', timeout: 2000 }).catch(() => fail(`${label}: Esc did not skip the intro`));
    await p2.goto(base + '/about', { waitUntil: 'domcontentloaded' });
    if (await p2.locator('#anniv5-intro:not([hidden])').count()) fail(`${label}: intro replayed in the same session`);
    await ctx2.close();
}

// 2. Every public page at every width: seal present, no sideways scroll, no clipped seal text, CLS.
const ctx = await browser.newContext();
// Same as check-layout.mjs: the one-thread dev server stalls on videos and the chat widget.
await ctx.route(/tawk\.to/, (r) => r.abort());
await ctx.route(/\.(mp4|webm)(\?|$)/i, (r) => r.abort());
await ctx.addInitScript(() => {
    try { sessionStorage.setItem('skylink.anniv5.introSeen', '1'); } catch (e) {}
    window.__cls = 0;
    new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; })
        .observe({ type: 'layout-shift', buffered: true });
});
for (const w of widths) {
    const page = await ctx.newPage();
    await page.setViewportSize({ width: w, height: w > 800 ? 900 : 780 });
    for (const uri of pages) {
        const res = await page.goto(base + uri, { waitUntil: 'domcontentloaded' });
        await page.waitForLoadState('load', { timeout: 15000 }).catch(() => {});
        await page.evaluate(() => document.fonts.ready);
        await page.waitForTimeout(700);
        const info = await page.evaluate(() => {
            const seal = [...document.querySelectorAll('[data-anniv5-seal]')].find((s) => s.offsetParent);
            const r = seal && seal.getBoundingClientRect();
            const clipped = seal ? [...seal.querySelectorAll('p, h2')].filter((e) => e.scrollWidth > e.clientWidth + 1).length : -1;
            return {
                seal: !!seal, sealHeight: r ? Math.round(r.height) : 0, clipped,
                overflowX: document.documentElement.scrollWidth - window.innerWidth,
                cls: Math.round(window.__cls * 1000) / 1000,
                navbarCta: !!document.querySelector('.navbar'),
            };
        });
        const row = { uri, width: w, status: res.status(), ...info };
        report.pages.push(row);
        if (row.status !== 200) fail(`${uri}@${w}: HTTP ${row.status}`);
        if (!row.seal) fail(`${uri}@${w}: seal not visible`);
        if (row.overflowX > 0) fail(`${uri}@${w}: page scrolls sideways by ${row.overflowX}px`);
        if (row.clipped > 0) fail(`${uri}@${w}: seal text clipped`);
        const name = (uri === '/' ? 'home' : uri.slice(1).replace(/\//g, '-')) + `-${w}.png`;
        await page.screenshot({ path: path.join(out, 'shots', name) });
    }
    await page.close();
}
await ctx.close();

// 3. Reduced motion: no intro, settled still seal.
const rm = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1440, height: 900 } });
const rmPage = await rm.newPage();
await rm.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
await rmPage.goto(base + '/services', { waitUntil: 'domcontentloaded' });
report.reducedMotion = await rmPage.evaluate(() => ({
    introShown: !!document.querySelector('#anniv5-intro:not([hidden])'),
    fiveOpacity: getComputedStyle(document.querySelector('.anniv5-mark__n--5')).opacity,
}));
if (report.reducedMotion.introShown) fail('reduced motion: intro still shown');
if (report.reducedMotion.fiveOpacity !== '1') fail('reduced motion: seal not settled on 5');
await rmPage.locator('[data-anniv5-seal]').first().screenshot({ path: path.join(out, 'shots', 'seal-reduced-motion.png') });
await rm.close();

await browser.close();
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
console.log(`${report.pages.length} page checks, ${report.failures.length} failures. Output: ${out}`);
process.exit(report.failures.length ? 1 : 0);
