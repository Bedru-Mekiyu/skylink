// LOCAL USE ONLY. Records the first-visit intro at five widths, with a frame-rate probe, a 4x CPU
// throttle run, a memory check and an overflow check on the headline.
//   ANNIVERSARY_FORCE=true php artisan serve --port=8081
//   node tools/local/capture-intro-upgrade.mjs http://127.0.0.1:8081 after
// Writes to storage/app/anniversary-captures/intro-upgrade/<label>/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8081').replace(/\/$/, '');
const label = process.argv[3] || 'after';
const out = path.resolve('storage/app/anniversary-captures/intro-upgrade', label);
fs.mkdirSync(out, { recursive: true });

const sizes = [[1440, 900], [1024, 768], [768, 1024], [390, 844], [320, 640]];
const report = { base, label, runs: [], failures: [] };
const fail = (m) => { report.failures.push(m); console.error('FAIL', m); };

const browser = await chromium.launch({ args: ['--enable-precise-memory-info', '--js-flags=--expose-gc'] });
await fetch(base + '/').catch(() => {});

const initProbe = () => {
    window.__phases = []; window.__dts = [];
    new MutationObserver(() => {
        const el = document.getElementById('anniv5-intro');
        const p = el && el.getAttribute('data-phase');
        if (p && window.__phases[window.__phases.length - 1] !== p) window.__phases.push(p);
    }).observe(document, { subtree: true, attributes: true, attributeFilter: ['data-phase'] });
    let prev = 0;
    const tick = (n) => {
        if (prev && document.getElementById('anniv5-intro')) window.__dts.push(n - prev);
        prev = n;
        if (document.getElementById('anniv5-intro') || !window.__dts.length) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
};

async function runIntro([w, h], { throttle = 1, video = true, shots = true } = {}) {
    const ctx = await browser.newContext({
        viewport: { width: w, height: h },
        ...(video ? { recordVideo: { dir: out, size: { width: w, height: h } } } : {}),
    });
    await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    await ctx.addInitScript(initProbe);
    const page = await ctx.newPage();
    const cdp = await ctx.newCDPSession(page);
    if (throttle > 1) await cdp.send('Emulation.setCPUThrottlingRate', { rate: throttle });
    const tag = `${w}${throttle > 1 ? `-cpu${throttle}x` : ''}`;
    const started = Date.now();
    await page.goto(base + '/', { waitUntil: 'commit' });
    const run = { width: w, throttle, shots: [] };
    if (shots) {
        const measure = () => page.evaluate(() => {
            const h = document.querySelector('.anniv5-intro__headline');
            if (!h) return null;
            const r = h.getBoundingClientRect();
            const k = document.querySelector('.anniv5-intro__kicker').getBoundingClientRect();
            return { left: Math.round(r.left), right: Math.round(r.right), width: innerWidth, fits: r.left >= 0 && r.right <= innerWidth && k.left >= 0 && k.right <= innerWidth, scrollOverflow: h.scrollWidth > h.clientWidth + 1 };
        });
        for (const [phase, delay, name] of [['n3', 100, 'countdown'], ['build', 300, 'buildup'], ['five', 150, 'detonation'], ['five', 500, 'burst'], ['title', 0, 'headline-rising'], ['title', 2200, 'headline']]) {
            if (await page.waitForFunction((p) => window.__phases.includes(p), phase, { timeout: 30000, polling: 20 }).then(() => true, () => false)) { // history, not current: fast machines pass a phase before we ask
                if (phase === 'title' && !run.introHeadline) run.introHeadline = await measure();
                if (await page.evaluate(() => !document.getElementById('anniv5-intro'))) break;
                await page.waitForTimeout(delay);
                await page.screenshot({ path: path.join(out, `${tag}-${name}.png`) }).catch(() => {});
                run.shots.push(name);
            }
        }
    }
    await page.waitForSelector('#anniv5-intro', { state: 'attached', timeout: 5000 }).catch(() => {});
    await page.waitForSelector('#anniv5-intro', { state: 'detached', timeout: 90000 }).catch(() => fail(`${tag}: intro did not finish`));
    run.seconds = (Date.now() - started) / 1000;
    run.phases = await page.evaluate(() => window.__phases);
    const dts = await page.evaluate(() => window.__dts);
    if (dts.length) {
        const s = [...dts].sort((a, b) => a - b);
        const avg = dts.reduce((a, b) => a + b, 0) / dts.length;
        run.frames = { count: dts.length, avgMs: +avg.toFixed(1), fps: +(1000 / avg).toFixed(1), p95Ms: +s[Math.floor(s.length * 0.95)].toFixed(1), worstMs: +s[s.length - 1].toFixed(1), over20ms: dts.filter((d) => d > 20).length };
    }
    // Leftovers: no canvas, no overlay in the DOM.
    run.leftovers = await page.evaluate(() => ({ overlay: !!document.getElementById('anniv5-intro'), introCanvas: document.querySelectorAll('.anniv5-intro__sky').length }));
    if (run.leftovers.overlay || run.leftovers.introCanvas) fail(`${tag}: intro DOM not released`);
    // Headline on the homepage panel.
    run.panel = await page.evaluate(() => {
        const t = [...document.querySelectorAll('.anniv5--feature .anniv5__title')].find((e) => e.getBoundingClientRect().width > 0);
        if (!t) return null;
        const r = t.getBoundingClientRect();
        return { left: Math.round(r.left), right: Math.round(r.right), width: innerWidth, fits: r.left >= 0 && r.right <= innerWidth + 0.5, text: t.textContent.trim() };
    });
    if (video && throttle === 1) {
        // Second view in the same session must not replay.
        const again = await page.reload({ waitUntil: 'domcontentloaded' }).then(() => page.evaluate(() => !!document.getElementById('anniv5-intro')));
        run.replayedInSession = again;
        if (again) fail(`${tag}: intro replayed within the session`);
    }
    const v = page.video();
    await ctx.close();
    if (v) fs.renameSync(await v.path(), path.join(out, `intro-${tag}.webm`));
    report.runs.push(run);
    console.log(JSON.stringify({ tag, seconds: run.seconds, frames: run.frames, phases: run.phases?.join('>') }));
}

const only = process.env.ONLY_W ? Number(process.env.ONLY_W) : 0; // re-record a single width
for (const size of sizes.filter((z) => !only || z[0] === only)) await runIntro(size);
if (only) { fs.writeFileSync(path.join(out, `report-${only}.json`), JSON.stringify(report, null, 2)); await browser.close(); console.log(JSON.stringify(report.runs.map((r) => [r.introHeadline, r.panel]))); process.exit(0); }
await runIntro([1440, 900], { video: false, shots: false }); // frame rate without the recorder's overhead
await runIntro([1440, 900], { throttle: 4, video: false, shots: false });
await runIntro([390, 844], { throttle: 4, video: false, shots: false });

// Skip button and Escape.
for (const mode of ['skip', 'escape']) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    const page = await ctx.newPage();
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#anniv5-intro[data-phase="n2"]', { timeout: 10000 }).catch(() => {});
    if (mode === 'skip') await page.click('.anniv5-intro__skip'); else await page.keyboard.press('Escape');
    const gone = await page.waitForSelector('#anniv5-intro', { state: 'detached', timeout: 3000 }).then(() => true, () => false);
    report[`${mode}Works`] = gone;
    if (!gone) fail(`${mode} did not dismiss the intro`);
    await ctx.close();
}

// Reduced motion: no intro at all.
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
    await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    const page = await ctx.newPage();
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1200);
    report.reducedMotionIntroVisible = await page.evaluate(() => { const e = document.getElementById('anniv5-intro'); return !!e && getComputedStyle(e).display !== 'none'; });
    if (report.reducedMotionIntroVisible) fail('reduced motion still shows the intro');
    await ctx.close();
}

// Memory: heap before the intro vs after it is gone (forced GC).
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    const page = await ctx.newPage();
    const cdp = await ctx.newCDPSession(page);
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#anniv5-intro[data-phase="launch"]', { timeout: 10000 }).catch(() => {});
    const heap = async () => { await cdp.send('HeapProfiler.collectGarbage'); return page.evaluate(() => performance.memory.usedJSHeapSize); };
    const during = await page.waitForSelector('#anniv5-intro[data-phase="title"]', { timeout: 12000 }).then(async () => page.evaluate(() => performance.memory.usedJSHeapSize), () => null);
    await page.waitForSelector('#anniv5-intro', { state: 'detached', timeout: 12000 }).catch(() => {});
    await page.waitForTimeout(1500);
    const after = await heap();
    report.memory = { duringIntroMB: during && +(during / 1048576).toFixed(1), afterIntroMB: +(after / 1048576).toFixed(1) };
    await ctx.close();
}

fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
await browser.close();
console.log(report.failures.length ? 'FAILURES:\n' + report.failures.join('\n') : 'ALL CHECKS PASSED');
