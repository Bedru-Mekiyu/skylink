// LOCAL USE ONLY. Checks and records the continuous marquees (home "Our Services", News "Videos" row).
//   node tools/local/capture-marquee.mjs http://127.0.0.1:8000
// Writes screenshots, short videos and report.json to storage/app/ui-fixes/marquee/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const out = path.resolve('storage/app/ui-fixes/marquee');
fs.mkdirSync(out, { recursive: true });
const report = { runs: {}, failures: [] };
const fail = (m) => { report.failures.push(m); console.error('FAIL', m); };

const targets = {
    services: { url: '/', row: '.services-marquee', track: '.services-track', card: '.service-card', title: 'h4', expected: 7 },
    news: { url: '/news', row: '.news-videos__row', track: '.news-videos__track', card: '.news-videos__track > li', title: '.news-video__title', expected: null },
};

const browser = await chromium.launch();
const pageErrors = [];

async function open(ctx, t, tag, scale = 1) {
    const page = await ctx.newPage();
    page.on('pageerror', (e) => pageErrors.push(`${tag}: ${e.message}`));
    await page.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    await page.addInitScript(() => sessionStorage.setItem('skylink.anniv5.introSeen', '1'));
    await page.goto(base + t.url, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector(t.row, { state: 'attached', timeout: 20000 });
    await page.locator(t.row).first().evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(1500);
    return page;
}
const x = (page, sel) => page.locator(sel).first().evaluate((e) => new DOMMatrixReadOnly(getComputedStyle(e).transform).m41);

for (const [name, t] of Object.entries(targets)) {
    for (const [w, h] of [[1440, 900], [1024, 768], [768, 1024], [390, 844], [320, 640]]) {
        const tag = `${name}-${w}`;
        const ctx = await browser.newContext({ viewport: { width: w, height: h }, recordVideo: (w === 1440 || w === 390) ? { dir: out, size: { width: w, height: h } } : undefined });
        const page = await open(ctx, t, tag);
        const r = (report.runs[tag] = {});
        await page.screenshot({ path: path.join(out, `${tag}.png`) });

        // No controls left in the section.
        r.buttonsInSection = await page.locator(t.row).first().evaluate((e) => e.closest('section').querySelectorAll('button, .carousel-nav, .services-dot').length);
        if (r.buttonsInSection) fail(`${tag}: ${r.buttonsInSection} control(s) still in the section`);

        // Moves continuously: translateX strictly decreasing over 3s of 100ms samples (no stops).
        const xs = []; const t0 = Date.now();
        for (let i = 0; i < 30; i++) { xs.push(await x(page, t.track)); await page.waitForTimeout(100); }
        const steps = xs.slice(1).map((v, i) => v - xs[i]);
        r.pxPerSecond = +((xs[0] - xs[xs.length - 1]) / ((Date.now() - t0) / 1000)).toFixed(1);
        r.stops = steps.filter((d) => d > -0.5).length;
        if (r.stops > 1) fail(`${tag}: ${r.stops} samples did not move`);

        // Seamless loop: the second copy starts exactly half a track after the first; one set is wider than the screen.
        r.seam = await page.locator(t.track).first().evaluate((tr, sel) => {
            const cards = [...document.querySelectorAll(sel)];
            const dupIndex = cards.findIndex((c) => c.classList.contains('marquee-dup'));
            const half = tr.getBoundingClientRect().width / 2;
            return { cards: cards.length, firstCopy: dupIndex, offsetOk: Math.abs((cards[dupIndex].offsetLeft - cards[0].offsetLeft) - half) < 1, halfWidth: Math.round(half), viewport: innerWidth };
        }, t.card);
        if (!r.seam.offsetOk) fail(`${tag}: loop seam is not exact`);
        if (r.seam.halfWidth < r.seam.viewport) fail(`${tag}: one set is narrower than the screen (gap)`);
        if (t.expected && r.seam.firstCopy !== t.expected) fail(`${tag}: expected ${t.expected} cards per copy, found ${r.seam.firstCopy}`);

        // All titles of the first copy are there.
        r.titles = await page.locator(t.card).evaluateAll((els, sel) => els.filter((e) => !e.classList.contains('marquee-dup')).map((e) => e.querySelector(sel)?.textContent.trim()), t.title);

        // Hover pauses (like the testimonials and Featured Products); touch screens have no hover.
        if (w >= 992) {
            await page.locator(t.row).first().hover();
            const a = await x(page, t.track); await page.waitForTimeout(1200); const b = await x(page, t.track);
            r.hoverPauses = Math.abs(a - b) < 0.5;
            if (!r.hoverPauses) fail(`${tag}: hover did not pause`);
        }
        r.pageOverflowX = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
        if (r.pageOverflowX > 0) fail(`${tag}: page scrolls sideways by ${r.pageOverflowX}px`);

        // Let the video show at least a loop's worth of the movement (kept short: one loop is 29s / 77s).
        if (w === 1440 || w === 390) await page.waitForTimeout(name === 'services' ? 32000 : 12000);

        const v = page.video();
        await ctx.close();
        if (v) fs.renameSync(await v.path(), path.join(out, `${tag}.webm`));
        console.log(tag, JSON.stringify({ px: r.pxPerSecond, stops: r.stops, seam: r.seam, buttons: r.buttonsInSection, hover: r.hoverPauses, overflow: r.pageOverflowX }));
    }

    // A card link opens its page.
    {
        const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
        const page = await open(ctx, t, `${name}-link`);
        const link = name === 'news' ? `${t.card} a:visible` : null;
        if (link) {
            await page.locator(t.row).first().hover(); // paused, so the card holds still
            const href = await page.locator(`${t.track} > li:not(.marquee-dup) a`).first().getAttribute('href');
            await Promise.all([
                page.waitForURL((u) => u.pathname === new URL(href, base).pathname, { timeout: 15000 }).catch(() => {}),
                page.locator(`${t.track} > li:not(.marquee-dup) a`).first().evaluate((e) => e.click()),
            ])
            report.runs[`${name}-link`] = { href, landedOn: page.url() };
            if (!page.url().includes(href.replace(/^https?:\/\/[^/]+/, ''))) fail(`${name}: card link did not open ${href}`);
        } else {
            report.runs[`${name}-link`] = 'no card links in this section (cards are not links)';
        }
        await ctx.close();
    }

    // Reduced motion: still, one copy.
    {
        const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
        const page = await open(ctx, t, `${name}-reduced`);
        const a = await x(page, t.track); await page.waitForTimeout(2000); const b = await x(page, t.track);
        const dupsVisible = await page.locator(`${t.card}.marquee-dup`).first().isVisible().catch(() => false);
        report.runs[`${name}-reduced`] = { moved: Math.abs(a - b) > 0.5, duplicatesVisible: dupsVisible };
        if (Math.abs(a - b) > 0.5 || dupsVisible) fail(`${name}: reduced motion still moves or shows duplicates`);
        await page.screenshot({ path: path.join(out, `${name}-reduced-motion.png`) });
        await ctx.close();
    }
}
report.pageErrors = pageErrors;
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
await browser.close();
console.log(report.failures.length ? 'FAILURES:\n' + report.failures.join('\n') : 'ALL CHECKS PASSED', pageErrors);
