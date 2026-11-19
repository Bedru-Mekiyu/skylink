// LOCAL USE ONLY. Checks and records the "center card" highlight of the homepage Our Services marquee.
//   node tools/local/capture-services-highlight.mjs http://127.0.0.1:8000
// Writes screenshots, videos and report.json to storage/app/ui-fixes/services-center-highlight/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const out = path.resolve('storage/app/ui-fixes/services-center-highlight');
fs.mkdirSync(out, { recursive: true });
const report = { widths: {}, failures: [] };
const fail = (m) => { report.failures.push(m); console.error('FAIL', m); };
const browser = await chromium.launch();

async function open(ctx) {
    const page = await ctx.newPage();
    await page.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    await page.addInitScript(() => sessionStorage.setItem('skylink.anniv5.introSeen', '1'));
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('.services-marquee', { state: 'attached', timeout: 20000 });
    await page.locator('.services-marquee').evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(1500);
    return page;
}

// In-page probe: every frame, how many cards are highlighted and whether it is the one nearest the middle.
const probe = (page, loops, rate) => page.evaluate(async ([loops, rate]) => {
    const box = document.querySelector('.services-marquee'), track = box.querySelector('.services-track');
    const anim = track.getAnimations()[0];
    anim.playbackRate = rate;
    const dur = anim.effect.getComputedTiming().duration / rate;
    const cards = [...track.children];
    const s = { frames: 0, notExactlyOne: 0, notNearest: 0, titles: new Set(), changes: 0, maxFrameMs: 0, longFrames: 0, order: [] };
    let prev = null, last = performance.now(), loopsDone = 0, lastProg = 0;
    await new Promise((res) => {
        const end = performance.now() + dur * loops;
        (function f(now) {
            const dt = now - last; last = now; s.frames++; s.maxFrameMs = Math.max(s.maxFrameMs, dt); if (dt > 24) s.longFrames++;
            // Both copies of a service carry the class; only the copy on screen counts.
            const bx = box.getBoundingClientRect();
            const on = cards.filter((c) => { const r = c.getBoundingClientRect(); return c.classList.contains('is-center') && r.right > bx.left && r.left < bx.right; });
            if (on.length !== 1) s.notExactlyOne++;
            const mid = box.getBoundingClientRect().left + box.clientWidth / 2;
            const dists = cards.map((c) => { const r = c.getBoundingClientRect(); return Math.abs(r.left + r.width / 2 - mid); });
            const nearest = dists.indexOf(Math.min(...dists));
            // Allow one frame of lag at a hand-over: the highlighted card must be one of the two nearest.
            const idx = on.length ? cards.indexOf(on[0]) : -1;
            const two = dists.map((d, i) => [d, i]).sort((a, b) => a[0] - b[0]).slice(0, 2).map((x) => x[1]);
            if (!two.includes(idx)) s.notNearest++;
            if (on[0]) { s.titles.add(on[0].querySelector('h4').textContent); if (on[0]?.querySelector('h4').textContent !== prev) { s.changes++; s.order.push(on[0].querySelector('h4').textContent); prev = on[0].querySelector('h4').textContent; } }
            if (now < end) requestAnimationFrame(f); else res();
        })(performance.now());
    });
    return { ...s, titles: [...s.titles], order: s.order.slice(0, 20), loopSeconds: +dur.toFixed(1) };
}, [loops, rate]);

// WCAG contrast of the highlighted card's title, paragraph and icon against its background.
const contrast = (page) => page.evaluate(() => {
    const lum = ([r, g, b]) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
    const parse = (s) => s.match(/[\d.]+/g).map(Number);
    const blend = (fg, bg) => { const a = fg[3] ?? 1; return [0, 1, 2].map((i) => Math.round(fg[i] * a + bg[i] * (1 - a))); };
    const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return +((x + 0.05) / (y + 0.05)).toFixed(2); };
    const card = document.querySelector('.service-card.is-center');
    if (!card) return null;
    // The card is a gradient: measure against both stops and keep the worse ratio.
    const cs0 = getComputedStyle(card);
    const stops = [cs0.getPropertyValue('--hl-from').trim(), cs0.getPropertyValue('--hl-to').trim()].map((h) => [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16)));
    const res = { gradient: stops.map((c) => '#' + c.map((v) => v.toString(16).padStart(2, '0')).join('')) };
    const worst = (fn) => Math.min(...stops.map(fn));
    for (const [k, el] of [['title', card.querySelector('h4')], ['paragraph', card.querySelector('p')]]) {
        const cs = getComputedStyle(el); const col = parse(cs.color); const op = Number(cs.opacity);
        res[k] = worst((bg) => ratio(blend([col[0], col[1], col[2], (col[3] ?? 1) * op], bg), bg));
    }
    const ic = parse(getComputedStyle(card.querySelector('.service-image i')).color);
    const imgOver = parse(getComputedStyle(card.querySelector('.service-image')).backgroundColor);
    res.icon = worst((bg) => { const ib = blend(imgOver, bg); return ratio(blend(ic, ib), ib); });
    return res;
});

for (const [w, h] of [[1440, 900], [1024, 768], [768, 1024], [390, 844], [320, 640]]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, recordVideo: (w === 1440 || w === 390) ? { dir: out, size: { width: w, height: h } } : undefined });
    const page = await open(ctx);
    const r = (report.widths[w] = {});
    await page.waitForTimeout(800);
    await page.screenshot({ path: path.join(out, `highlight-${w}.png`) });

    // Two full loops, played 6x faster so the whole loop (including the wrap point) is covered quickly.
    r.twoLoops = await probe(page, 2, 6);
    if (r.twoLoops.notExactlyOne) fail(`${w}: ${r.twoLoops.notExactlyOne} frame(s) without exactly one highlighted card`);
    if (r.twoLoops.notNearest) fail(`${w}: ${r.twoLoops.notNearest} frame(s) highlighted a card that was not nearest the middle`);
    if (r.twoLoops.titles.length < 7) fail(`${w}: only ${r.twoLoops.titles.length} of 7 services were highlighted`);
    await page.evaluate(() => { document.querySelector('.services-track').getAnimations()[0].playbackRate = 1; });

    await page.mouse.move(0, 0);
    await page.waitForTimeout(900); // let the 0.5s colour transition finish before measuring
    r.contrast = await contrast(page);
    for (const k of ['title', 'paragraph']) if (r.contrast && r.contrast[k] < 4.5) fail(`${w}: ${k} contrast ${r.contrast[k]} < 4.5`);
    if (r.contrast && r.contrast.icon < 3) fail(`${w}: icon contrast ${r.contrast.icon} < 3`);

    r.pageOverflowX = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    if (r.pageOverflowX > 0) fail(`${w}: page scrolls sideways`);

    // Hover on a card that is not highlighted keeps the old hover look and pauses.
    if (w >= 992) {
        const idx = await page.evaluate(() => [...document.querySelectorAll('.services-track .service-card')].findIndex((c) => !c.classList.contains('is-center') && c.getBoundingClientRect().left > 100 && c.getBoundingClientRect().right < innerWidth - 100));
        if (idx >= 0) {
            // The card is moving, so point at it instead of waiting for it to be stable.
            const box = await page.locator('.services-track .service-card').nth(idx).boundingBox();
            await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
            await page.waitForTimeout(900);
            r.hoverBackground = await page.locator('.services-track .service-card').nth(idx).evaluate((e) => getComputedStyle(e).backgroundColor);
            if (r.hoverBackground !== 'rgb(74, 144, 226)' && r.hoverBackground !== 'rgb(30, 64, 175)') fail(`${w}: unexpected hover background ${r.hoverBackground}`);
            await page.mouse.move(0, 0);
        }
    }
    if (w === 1440 || w === 390) await page.waitForTimeout(30000); // real-time video, about a loop
    const v = page.video();
    await ctx.close();
    if (v) fs.renameSync(await v.path(), path.join(out, `highlight-${w}.webm`));
    console.log(w, JSON.stringify({ ...r.twoLoops, order: undefined, titles: r.twoLoops.titles.length }), JSON.stringify(r.contrast));
}

// 4x CPU throttle: frame times while highlighting.
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await open(ctx);
    const cdp = await ctx.newCDPSession(page);
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    const t = await probe(page, 1, 6);
    await page.evaluate(() => { document.querySelector('.services-track').getAnimations()[0].playbackRate = 1; });
    report.cpu4x = { frames: t.frames, longFrames: t.longFrames, maxFrameMs: +t.maxFrameMs.toFixed(1), notExactlyOne: t.notExactlyOne };
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 1 });
    await ctx.close();
}

// Reduced motion: nothing is highlighted, nothing animates.
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
    const page = await open(ctx);
    await page.waitForTimeout(2500);
    report.reducedMotion = { highlighted: await page.locator('.service-card.is-center').count(), animating: await page.evaluate(() => document.querySelector('.services-track').getAnimations().length) };
    if (report.reducedMotion.highlighted || report.reducedMotion.animating) fail('reduced motion still highlights or animates');
    await page.screenshot({ path: path.join(out, 'reduced-motion.png') });
    await ctx.close();
}
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
await browser.close();
console.log(report.failures.length ? 'FAILURES:\n' + report.failures.join('\n') : 'ALL CHECKS PASSED', JSON.stringify(report.cpu4x));
