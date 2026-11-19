// LOCAL USE ONLY. Checks and records the auto-rotating cards (home "Our Services", News bottom row).
//   node tools/local/capture-ui-fixes.mjs http://127.0.0.1:8000 services|news
// Writes screenshots, videos and <target>-report.json to storage/app/ui-fixes/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const target = process.argv[3] || 'services';
const out = path.resolve('storage/app/ui-fixes');
fs.mkdirSync(out, { recursive: true });
const report = { target, widths: {}, failures: [] };
const fail = (m) => { report.failures.push(m); console.error('FAIL', m); };

const cfg = {
    services: { url: '/', root: '.services-container', pause: '.services-pause', next: '.carousel-next',
        active: () => document.querySelector('.services-container .service-card.center h4')?.textContent },
    news: { url: '/news', root: '.news-videos__row', pause: '.news-videos__pause', next: '.news-videos__next',
        active: () => { const r = document.querySelector('.news-videos__row'); return r ? Math.round(r.scrollLeft) : null; } },
}[target];

const browser = await chromium.launch();
await fetch(base + cfg.url).catch(() => {});
const consoleErrors = [];

async function open(ctx, width) {
    const page = await ctx.newPage();
    page.on('pageerror', (e) => consoleErrors.push(`${width}: ${e.message}`));
    await page.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    await page.addInitScript(() => sessionStorage.setItem('skylink.anniv5.introSeen', '1'));
    await page.goto(base + cfg.url, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector(cfg.root, { state: 'attached', timeout: 20000 });
    await page.locator(cfg.root).first().scrollIntoViewIfNeeded();
    await page.waitForTimeout(1500);
    return page;
}
const sample = (page, ms, every = 400) => page.evaluate(([fnSrc, ms, every]) => new Promise((res) => {
    const fn = new Function('return (' + fnSrc + ')()'); const seen = []; const t0 = Date.now();
    const id = setInterval(() => { seen.push(fn()); if (Date.now() - t0 > ms) { clearInterval(id); res(seen); } }, every);
}), [cfg.active.toString(), ms, every]);
const distinct = (a) => [...new Set(a)];
// The fixed navbar can cover a control that was only scrolled to the edge: centre it first.
const clickC = async (page, sel) => { const l = page.locator(sel).first(); await l.evaluate((e) => { e.scrollIntoView({ block: 'center' }); e.click(); }); };

for (const [width, height] of [[1440, 900], [1024, 768], [768, 1024], [390, 844], [320, 640]]) {
    const ctx = await browser.newContext({ viewport: { width, height }, hasTouch: width < 992, recordVideo: (width === 1440 || width === 390) ? { dir: out, size: { width, height } } : undefined });
    const page = await open(ctx, width);
    const r = (report.widths[width] = {});
    await page.screenshot({ path: path.join(out, `${target}-${width}.png`) });

    // 1. Auto-rotates (several different active cards over ~12s).
    const seen = await sample(page, 12000);
    r.autoplayStates = distinct(seen).length;
    if (r.autoplayStates < 3) fail(`${target}@${width}: did not rotate (${distinct(seen).join(' | ')})`);

    // 2. No sideways page scroll.
    r.pageOverflowX = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    if (r.pageOverflowX > 0) fail(`${target}@${width}: page scrolls sideways by ${r.pageOverflowX}px`);

    // 3. Pause button stops it, and pressing it again resumes.
    if (await page.locator(cfg.pause).count()) {
        await clickC(page, cfg.pause);
        await page.mouse.move(0, 0);
        const paused = await sample(page, 6000);
        r.pausedStates = distinct(paused).length;
        if (r.pausedStates > 1) fail(`${target}@${width}: pause button did not stop it`);
        await clickC(page, cfg.pause);
        await page.mouse.move(0, 0);
        const resumed = await sample(page, 9000);
        r.resumedStates = distinct(resumed).length;
        if (r.resumedStates < 2) fail(`${target}@${width}: did not resume`);
    } else fail(`${target}@${width}: no pause button`);

    // 4. Hover pauses (desktop only).
    if (width >= 992) {
        await page.locator(cfg.root).first().hover();
        const hovered = await sample(page, 6000);
        r.hoverStates = distinct(hovered).length;
        if (r.hoverStates > 1) fail(`${target}@${width}: hover did not pause`);
    }
    // 5. Arrow moves it by hand.
    if (await page.locator(cfg.next).first().isVisible()) {
        const before = await page.evaluate(cfg.active);
        await clickC(page, cfg.next);
        await page.waitForTimeout(1200);
        r.nextWorks = (await page.evaluate(cfg.active)) !== before;
        if (!r.nextWorks) fail(`${target}@${width}: next arrow does nothing`);
    }
    const v = page.video();
    await ctx.close();
    if (v) fs.renameSync(await v.path(), path.join(out, `${target}-${width}.webm`));
    console.log(width, JSON.stringify(r));
}

// Reduced motion: no autoplay.
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
    const page = await open(ctx, 'reduced');
    const seen = distinct(await sample(page, 8000));
    report.reducedMotionStates = seen.length;
    if (seen.length > 1) fail(`${target}: reduced motion still autoplays`);
    await page.screenshot({ path: path.join(out, `${target}-reduced-motion.png`) });
    await ctx.close();
}
report.consoleErrors = consoleErrors;
fs.writeFileSync(path.join(out, `${target}-report.json`), JSON.stringify(report, null, 2));
await browser.close();
console.log(report.failures.length ? 'FAILURES:\n' + report.failures.join('\n') : 'ALL CHECKS PASSED', consoleErrors);
