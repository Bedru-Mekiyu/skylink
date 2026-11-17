import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const OUT_DIR = path.resolve('footage/_review/anniversary');

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}

async function run() {
    console.log('Launching browser for Anniversary capture...');
    const browser = await chromium.launch({ headless: true });

    const viewports = [
        { name: 'desktop-1440', width: 1440, height: 900 },
        { name: 'tablet-768', width: 768, height: 1024 },
        { name: 'mobile-390', width: 390, height: 844 },
    ];

    // =========================================================================
    // 1. CAPTURE BADGE ON TWO PAGES & OPEN PANEL & HOMEPAGE SECTION
    // =========================================================================
    for (const vp of viewports) {
        console.log(`\nCapturing views for ${vp.name}...`);
        const context = await browser.newContext({
            viewport: { width: vp.width, height: vp.height },
            deviceScaleFactor: 1,
        });

        // Set seen cookie/storage beforehand so intro doesn't block badge/section captures
        await context.addInitScript(() => {
            window.localStorage.setItem('skylink_anniv_5y_intro', '1');
            document.cookie = 'skylink_anniv_5y_intro=1; path=/';
        });

        const page = await context.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());

        // A. Homepage: Badge in navbar
        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivBadgeBtn', { timeout: 8000 });
        await page.waitForTimeout(300);
        await page.screenshot({
            path: path.join(OUT_DIR, `badge-home-${vp.name}.png`),
            clip: { x: 0, y: 0, width: vp.width, height: 140 }
        });
        console.log(`  Saved badge-home-${vp.name}.png`);

        // B. About page: Badge in navbar
        await page.goto(BASE + '/about', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivBadgeBtn', { timeout: 8000 });
        await page.waitForTimeout(300);
        await page.screenshot({
            path: path.join(OUT_DIR, `badge-about-${vp.name}.png`),
            clip: { x: 0, y: 0, width: vp.width, height: 140 }
        });
        console.log(`  Saved badge-about-${vp.name}.png`);

        // C. Open Celebration Panel Modal
        await page.click('#annivBadgeBtn');
        await page.waitForSelector('#annivCelebrationModal:not([hidden])', { timeout: 3000 });
        await page.waitForTimeout(400); // Allow pop animation
        await page.screenshot({
            path: path.join(OUT_DIR, `modal-open-${vp.name}.png`),
        });
        console.log(`  Saved modal-open-${vp.name}.png`);

        // D. Homepage Section "Our 5 Years"
        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        const section = await page.$('#anniversary-story');
        if (section) {
            await section.scrollIntoViewIfNeeded();
            await page.waitForTimeout(500);
            await section.screenshot({
                path: path.join(OUT_DIR, `section-home-${vp.name}.png`)
            });
            console.log(`  Saved section-home-${vp.name}.png`);
        }

        await context.close();
    }

    // =========================================================================
    // 2. CAPTURE 6-FRAME INTRO SEQUENCE (at 1440px and 390px)
    // =========================================================================
    console.log('\nCapturing 6-frame cinematic intro sequence...');
    const introTimes = [
        { label: 'frame1-0.4s-navy-grid', ms: 400 },
        { label: 'frame2-1.6s-numeral-trace', ms: 1600 },
        { label: 'frame3-2.6s-years-reveal', ms: 2600 },
        { label: 'frame4-3.2s-brand-title', ms: 3200 },
        { label: 'frame5-4.2s-tagline-shine', ms: 4200 },
        { label: 'frame6-5.2s-page-reveal', ms: 5200 },
    ];

    for (const shot of introTimes) {
        const ctx = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 1,
        });

        // Mask navigator.webdriver so the test runner can capture the real visitor experience
        await ctx.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });

        const p = await ctx.newPage();
        await p.route(/tawk\.to/, r => r.abort());
        await p.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());

        await p.goto(BASE + '/', { waitUntil: 'commit' });
        await p.waitForSelector('#annivIntroOverlay:not([style*="display: none"])', { timeout: 8000 });
        await p.waitForTimeout(shot.ms);

        await p.screenshot({
            path: path.join(OUT_DIR, `intro-${shot.label}-1440.png`)
        });
        console.log(`  Saved intro-${shot.label}-1440.png`);
        await ctx.close();
    }

    // Mobile 390px frame of the signature numeral & title
    {
        const ctx = await browser.newContext({
            viewport: { width: 390, height: 844 },
            deviceScaleFactor: 1,
        });

        await ctx.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });

        const p = await ctx.newPage();
        await p.route(/tawk\.to/, r => r.abort());
        await p.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await p.goto(BASE + '/', { waitUntil: 'commit' });
        await p.waitForSelector('#annivIntroOverlay:not([style*="display: none"])', { timeout: 8000 });
        await p.waitForTimeout(3200);
        await p.screenshot({
            path: path.join(OUT_DIR, `intro-frame4-brand-title-390.png`)
        });
        console.log(`  Saved intro-frame4-brand-title-390.png`);
        await ctx.close();
    }

    // =========================================================================
    // 3. FRAME-RATE PROFILING WITH 4X CPU SLOWDOWN
    // =========================================================================
    console.log('\nRunning intro frame-rate profiling with 4x CPU slowdown...');
    {
        const ctx = await browser.newContext({
            viewport: { width: 1440, height: 900 }
        });

        await ctx.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });

        const p = await ctx.newPage();
        await p.route(/tawk\.to/, r => r.abort());
        await p.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());

        const client = await ctx.newCDPSession(p);
        await client.send('Emulation.setCPUThrottlingRate', { rate: 4 });

        // Inject rAF frame delta measurement
        await p.addInitScript(() => {
            window.__frameDeltas = [];
            let last = performance.now();
            function track(now) {
                const delta = now - last;
                last = now;
                window.__frameDeltas.push(delta);
                if (window.__frameDeltas.length < 350) {
                    requestAnimationFrame(track);
                }
            }
            requestAnimationFrame(track);
        });

        await p.goto(BASE + '/', { waitUntil: 'commit' });
        await p.waitForSelector('#annivIntroOverlay:not([style*="display: none"])', { timeout: 8000 });
        await p.waitForTimeout(5600);

        const deltas = await p.evaluate(() => window.__frameDeltas || []);
        await ctx.close();

        const activeDeltas = deltas.slice(2);
        const droppedFrames = activeDeltas.filter(d => d > 33.3).length; // below 30 fps
        const avgDelta = activeDeltas.reduce((a, b) => a + b, 0) / (activeDeltas.length || 1);
        const avgFps = 1000 / avgDelta;

        const report = {
            totalFramesSampled: activeDeltas.length,
            avgFrameDurationMs: avgDelta.toFixed(2),
            effectiveAverageFps: avgFps.toFixed(1),
            droppedFramesAbove33ms: droppedFrames,
            droppedPercentage: ((droppedFrames / (activeDeltas.length || 1)) * 100).toFixed(1) + '%'
        };

        console.log('Frame-rate Performance Report (4x CPU Throttling):', report);
        fs.writeFileSync(path.join(OUT_DIR, 'fps-report.json'), JSON.stringify(report, null, 2));
    }

    await browser.close();
    console.log('\nAll captures and profiling completed successfully!');
}

run().catch(err => {
    console.error('Capture error:', err);
    process.exit(1);
});
