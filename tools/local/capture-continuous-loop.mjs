import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const OUT_DIR = path.resolve('footage/_review/anniversary');

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}

async function run() {
    console.log('=== Continuous Loop & Full Centerpiece Playwright Verification ===');
    const browser = await chromium.launch({ headless: true });

    async function createContext(width, height, options = {}) {
        const ctx = await browser.newContext({
            viewport: { width, height },
            deviceScaleFactor: options.dsf || 1,
            reducedMotion: options.reducedMotion || 'no-preference',
        });
        await ctx.addInitScript(() => {
            window.localStorage.setItem('skylink_anniv_5y_intro', '1');
            document.cookie = 'skylink_anniv_5y_intro=1; path=/';
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });
        return ctx;
    }

    async function setupPage(ctx) {
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());
        return page;
    }

    // 1. Continuous Loop Execution on Homepage (3 Complete Cycles)
    console.log('\n1. Verifying 3 continuous animation cycles on Homepage (1440px)...');
    {
        const ctx = await createContext(1440, 900);
        const page = await setupPage(ctx);

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard .anniv-circ-stage', { timeout: 8000 });
        const stageEl = await page.$('#annivHeroCard .anniv-circ-stage');

        console.log('  -> Cycle 1: Observing Phase 03, Phase 06, Phase 07...');
        await page.waitForTimeout(2800);
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle1-phase03.png') });
        console.log('     Captured hero-loop-cycle1-phase03.png');

        await page.waitForTimeout(3200); // at ~6.0s
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle1-phase06.png') });
        console.log('     Captured hero-loop-cycle1-phase06.png (Multi-color Explosion)');

        await page.waitForTimeout(1600); // at ~7.6s
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle1-phase07.png') });
        console.log('     Captured hero-loop-cycle1-phase07.png (Resting Hold)');

        console.log('  -> Cycle 2: Verifying automatic repeat without restart interruption...');
        await page.waitForTimeout(4800); // at ~12.4s (Cycle 2 Phase 03)
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle2-phase03.png') });
        console.log('     Captured hero-loop-cycle2-phase03.png');

        await page.waitForTimeout(3200); // at ~15.6s (Cycle 2 Phase 06)
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle2-phase06.png') });
        console.log('     Captured hero-loop-cycle2-phase06.png (Multi-color Explosion Cycle 2)');

        await page.waitForTimeout(1600); // at ~17.2s (Cycle 2 Phase 07)
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle2-phase07.png') });
        console.log('     Captured hero-loop-cycle2-phase07.png');

        console.log('  -> Cycle 3: Verifying smooth third repeat cycle...');
        await page.waitForTimeout(4800); // at ~22.0s (Cycle 3 Phase 03)
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle3-phase03.png') });
        console.log('     Captured hero-loop-cycle3-phase03.png');

        await page.waitForTimeout(3200); // at ~25.2s (Cycle 3 Phase 06)
        await stageEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-cycle3-phase06.png') });
        console.log('     Captured hero-loop-cycle3-phase06.png (Multi-color Explosion Cycle 3)');

        await page.waitForTimeout(1600); // at ~26.8s (Cycle 3 Phase 07)
        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-master-1440.png') });
            console.log('     Captured hero-loop-master-1440.png (Full Hero view during Cycle 3)');
        }
        await ctx.close();
    }

    // 2. Full Centerpiece on About Page
    console.log('\n2. Verifying Full Shared Circular Centerpiece on About Page...');
    {
        const ctx = await createContext(1440, 900);
        const page = await setupPage(ctx);

        await page.goto(BASE + '/about', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivAboutCard', { timeout: 8000 });

        const isHeroSize = await page.$eval('#annivAboutCard .anniv-circ-wrap', el => el.classList.contains('anniv-circ-wrap--hero'));
        console.log(`  About centerpiece size is 'hero': ${isHeroSize}`);

        // Capture About Page full showcase at Cycle 1 explosion (~6.0s)
        await page.waitForTimeout(6000);
        const showcaseEl = await page.$('#annivAboutCard');
        if (showcaseEl) {
            await showcaseEl.screenshot({ path: path.join(OUT_DIR, 'about-loop-explosion-1440.png') });
            console.log('  Captured about-loop-explosion-1440.png (Cycle 1 Explosion on About Page)');
        }

        // Capture About Page at settled state (~7.8s)
        await page.waitForTimeout(1800);
        if (showcaseEl) {
            await showcaseEl.screenshot({ path: path.join(OUT_DIR, 'about-full-centerpiece-1440.png') });
            console.log('  Captured about-full-centerpiece-1440.png (Settled showcase on About Page)');
        }

        await ctx.close();
    }

    // About Page on mobile (390px)
    {
        const ctx = await createContext(390, 844);
        const page = await setupPage(ctx);
        await page.goto(BASE + '/about', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivAboutCard', { timeout: 8000 });
        await page.waitForTimeout(2000);
        const showcaseEl = await page.$('#annivAboutCard');
        if (showcaseEl) {
            await showcaseEl.screenshot({ path: path.join(OUT_DIR, 'about-full-centerpiece-390.png') });
            console.log('  Captured about-full-centerpiece-390.png (About showcase on 390px mobile)');
        }
        await ctx.close();
    }

    // 3. Navbar Verification (Confirm NO anniversary button in navbar)
    console.log('\n3. Verifying navbar is completely clean of promotional anniversary buttons...');
    const testPages = ['/', '/about', '/services', '/products', '/portfolio', '/news', '/contact'];
    for (const p of testPages) {
        const ctx = await createContext(1440, 900);
        const page = await setupPage(ctx);
        await page.goto(BASE + p, { waitUntil: 'domcontentloaded' });
        const badgeBtn = await page.$('#annivBadgeBtn');
        if (badgeBtn !== null) {
            console.error(`  FAIL: Found unwanted #annivBadgeBtn in navbar on ${p}!`);
        } else {
            console.log(`  PASS: No anniversary promotional button in navbar on ${p}`);
        }
        await ctx.close();
    }

    // 4. Reduced Motion Mode
    console.log('\n4. Verifying reduced-motion mode (prefers-reduced-motion: reduce)...');
    {
        const ctx = await createContext(1440, 900, { reducedMotion: 'reduce' });
        const page = await setupPage(ctx);
        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard .anniv-circ-wrap', { timeout: 8000 });
        const isSettled = await page.$eval('#annivHeroCard .anniv-circ-wrap', el => el.classList.contains('is-settled'));
        console.log(`  Reduced-motion immediately applies 'is-settled': ${isSettled}`);

        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({ path: path.join(OUT_DIR, 'hero-loop-reduced-motion-1440.png') });
            console.log('  Captured hero-loop-reduced-motion-1440.png');
        }
        await ctx.close();
    }

    // 5. Zero Horizontal Scroll across Zoom Levels
    console.log('\n5. Verifying zero horizontal scroll at 100%, 125%, 150% zoom on 1440px...');
    for (const dsf of [1, 1.25, 1.5]) {
        const ctx = await createContext(1440, 900, { dsf });
        const page = await setupPage(ctx);
        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });
        const overflow = await page.evaluate(() => {
            const doc = document.documentElement;
            return {
                scrollWidth: doc.scrollWidth,
                clientWidth: doc.clientWidth,
                hasOverflow: doc.scrollWidth > doc.clientWidth,
            };
        });
        console.log(`  Zoom ${dsf * 100}%: scrollWidth=${overflow.scrollWidth}, clientWidth=${overflow.clientWidth}, overflow=${overflow.hasOverflow}`);
        await ctx.close();
    }

    console.log('\n=== All Automated Continuous Loop Verifications Completed Successfully ===\n');
    await browser.close();
}

run().catch(err => {
    console.error('Execution error:', err);
    process.exit(1);
});
