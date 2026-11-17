import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const OUT_DIR = path.resolve('footage/_review/anniversary');

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}

async function run() {
    console.log('Launching browser for Cinematic Motion captures...');
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
            // mask webdriver for realistic tests
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });
        return ctx;
    }

    // 1. Kinetic Sequence captures at 1440px (mid-sequence at ~2.5s and post-burst at ~4.2s)
    console.log('\nCapturing kinetic sequence at 1440px...');
    {
        const ctx = await createContext(1440, 900);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });

        // Capture Scene 2: 01-05 orbital flight (~2.2s into load)
        await page.waitForTimeout(2200);
        const stageEl = await page.$('#annivMotionStage');
        if (stageEl) {
            await stageEl.screenshot({
                path: path.join(OUT_DIR, 'hero-motion-scene2-sequence-1440.png'),
            });
            console.log('  Saved hero-motion-scene2-sequence-1440.png');
        }

        // Capture Scene 3: Convergence & burst (~3.9s)
        await page.waitForTimeout(1700);
        if (stageEl) {
            await stageEl.screenshot({
                path: path.join(OUT_DIR, 'hero-motion-scene3-burst-1440.png'),
            });
            console.log('  Saved hero-motion-scene3-burst-1440.png');
        }

        // Wait until settled state (>= 5.8s)
        await page.waitForTimeout(2200);
        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({
                path: path.join(OUT_DIR, 'hero-motion-final-1440.png'),
            });
            console.log('  Saved hero-motion-final-1440.png');
        }
        await ctx.close();
    }

    // 2. Final composition across full viewport matrix (1920, 1440, 1024, 768, 390, 320)
    console.log('\nCapturing settled compositions across viewports...');
    const viewports = [
        { name: '1920', width: 1920, height: 1080 },
        { name: '1024', width: 1024, height: 768 },
        { name: '768', width: 768, height: 1024 },
        { name: '390', width: 390, height: 844 },
        { name: '320', width: 320, height: 568 },
    ];

    for (const vp of viewports) {
        const ctx = await createContext(vp.width, vp.height);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });
        // Settle immediately via class or wait
        await page.evaluate(() => {
            const card = document.getElementById('annivHeroCard');
            if (card) {
                card.classList.remove('is-animating');
                card.classList.add('is-settled');
            }
        });
        await page.waitForTimeout(400);

        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({
                path: path.join(OUT_DIR, `hero-motion-final-${vp.name}.png`),
            });
            console.log(`  Saved hero-motion-final-${vp.name}.png`);
        }
        await ctx.close();
    }

    // 3. News page captures at 1440 and 390
    console.log('\nCapturing News editorial announcement...');
    for (const width of [1440, 390]) {
        const ctx = await createContext(width, width === 1440 ? 900 : 844);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

        await page.goto(BASE + '/news', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivNewsCard', { timeout: 8000 });
        await page.waitForTimeout(300);

        const cardEl = await page.$('#annivNewsCard');
        if (cardEl) {
            await cardEl.screenshot({
                path: path.join(OUT_DIR, `news-motion-${width === 1440 ? 'desktop-1440' : 'mobile-390'}.png`),
            });
            console.log(`  Saved news-motion-${width === 1440 ? 'desktop-1440' : 'mobile-390'}.png`);
        }
        await ctx.close();
    }

    // 4. About page captures at 1440 and 390
    console.log('\nCapturing About page milestone feature...');
    for (const width of [1440, 390]) {
        const ctx = await createContext(width, width === 1440 ? 900 : 844);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

        await page.goto(BASE + '/about', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivAboutCard', { timeout: 8000 });
        await page.waitForTimeout(300);

        const cardEl = await page.$('#annivAboutCard');
        if (cardEl) {
            await cardEl.screenshot({
                path: path.join(OUT_DIR, `about-motion-${width === 1440 ? 'desktop-1440' : 'mobile-390'}.png`),
            });
            console.log(`  Saved about-motion-${width === 1440 ? 'desktop-1440' : 'mobile-390'}.png`);
        }
        await ctx.close();
    }

    // 5. Reduced Motion check
    console.log('\nCapturing reduced motion state...');
    {
        const ctx = await createContext(1440, 900, { reducedMotion: 'reduce' });
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });
        await page.waitForTimeout(400);

        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({
                path: path.join(OUT_DIR, 'hero-motion-reduced-motion-1440.png'),
            });
            console.log('  Saved hero-motion-reduced-motion-1440.png');
        }
        await ctx.close();
    }

    // 6. Zoom overflow checks
    console.log('\nRunning zoom checks at 125% and 150%...');
    for (const zoom of [1.25, 1.5]) {
        const ctx = await createContext(1440, 900, { dsf: zoom });
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
        const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
        console.log(`  Zoom ${(zoom * 100)}%: clientWidth=${clientWidth}, scrollWidth=${scrollWidth}`);
        if (scrollWidth > clientWidth) {
            console.warn(`  WARNING: Horizontal overflow detected at ${zoom * 100}% zoom!`);
        } else {
            console.log(`  Pass: Zero horizontal overflow at ${zoom * 100}% zoom.`);
        }
        await ctx.close();
    }

    await browser.close();
    console.log('\nAll cinematic motion captures completed successfully!');
}

run().catch(err => {
    console.error('Capture error:', err);
    process.exit(1);
});
