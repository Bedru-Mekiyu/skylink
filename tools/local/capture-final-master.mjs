import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const OUT_DIR = path.resolve('footage/_review/anniversary');

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}

async function run() {
    console.log('Launching browser for Final Master Anniversary Experience captures...');
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

    // 1. Kinetic phase captures at 1440px
    console.log('\n1. Capturing 7-Phase motion sequence on Homepage (1440px)...');
    {
        const ctx = await createContext(1440, 900);
        const page = await setupPage(ctx);

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });

        // Phase 03: Number sequence (around ~2.6s)
        await page.waitForTimeout(2600);
        const stageEl = await page.$('.anniv-circ-stage');
        if (stageEl) {
            await stageEl.screenshot({
                path: path.join(OUT_DIR, 'hero-phase03-seq-1440.png'),
            });
            console.log('  Saved hero-phase03-seq-1440.png (Phase 03 sequence 1..4)');
        }

        // Phase 06: Multi-color celebration explosion (around ~5.5s)
        await page.waitForTimeout(2900);
        if (stageEl) {
            await stageEl.screenshot({
                path: path.join(OUT_DIR, 'hero-phase06-explosion-1440.png'),
            });
            console.log('  Saved hero-phase06-explosion-1440.png (Phase 06 Multi-color Explosion)');
        }

        // Phase 07: Settle into resting state (>= 6.4s)
        await page.waitForTimeout(1500);
        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({
                path: path.join(OUT_DIR, 'hero-master-1440.png'),
            });
            console.log('  Saved hero-master-1440.png (Phase 07 Settled Hero)');
        }
        await ctx.close();
    }

    // 2. Homepage across viewports (1024, 768, 390, 320)
    console.log('\n2. Capturing Homepage settled centerpiece across viewports...');
    for (const vp of [
        { name: '1024', width: 1024, height: 768 },
        { name: '768', width: 768, height: 1024 },
        { name: '390', width: 390, height: 844 },
        { name: '320', width: 320, height: 568 },
    ]) {
        const ctx = await createContext(vp.width, vp.height);
        const page = await setupPage(ctx);

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });
        await page.evaluate(() => {
            const card = document.getElementById('annivHeroCenterpiece');
            if (card) {
                card.classList.remove('is-animating');
                card.classList.add('is-settled');
            }
        });
        await page.waitForTimeout(400);

        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({
                path: path.join(OUT_DIR, `hero-master-${vp.name}.png`),
            });
            console.log(`  Saved hero-master-${vp.name}.png`);
        }
        await ctx.close();
    }

    // 3. News page captures (1440 & 390)
    console.log('\n3. Capturing News page integration (1440 & 390)...');
    for (const width of [1440, 390]) {
        const ctx = await createContext(width, width === 1440 ? 900 : 844);
        const page = await setupPage(ctx);

        await page.goto(BASE + '/news', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivNewsCard', { timeout: 8000 });
        await page.evaluate(() => {
            const card = document.getElementById('annivNewsCenterpiece');
            if (card) {
                card.classList.remove('is-animating');
                card.classList.add('is-settled');
            }
        });
        await page.waitForTimeout(400);

        const newsCard = await page.$('#annivNewsCard');
        if (newsCard) {
            await newsCard.screenshot({
                path: path.join(OUT_DIR, `news-master-${width === 1440 ? '1440' : '390'}.png`),
            });
            console.log(`  Saved news-master-${width === 1440 ? '1440' : '390'}.png`);
        }
        await ctx.close();
    }

    // 4. About page captures (1440 & 390)
    console.log('\n4. Capturing About page integration (1440 & 390)...');
    for (const width of [1440, 390]) {
        const ctx = await createContext(width, width === 1440 ? 900 : 844);
        const page = await setupPage(ctx);

        await page.goto(BASE + '/about', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivAboutCard', { timeout: 8000 });
        await page.evaluate(() => {
            const card = document.getElementById('annivAboutCenterpiece');
            if (card) {
                card.classList.remove('is-animating');
                card.classList.add('is-settled');
            }
        });
        await page.waitForTimeout(400);

        const aboutCard = await page.$('#annivAboutCard');
        if (aboutCard) {
            await aboutCard.screenshot({
                path: path.join(OUT_DIR, `about-master-${width === 1440 ? '1440' : '390'}.png`),
            });
            console.log(`  Saved about-master-${width === 1440 ? '1440' : '390'}.png`);
        }
        await ctx.close();
    }

    // 5. Products page captures (1440 & 390)
    console.log('\n5. Capturing Products page integration (1440 & 390)...');
    for (const width of [1440, 390]) {
        const ctx = await createContext(width, width === 1440 ? 900 : 844);
        const page = await setupPage(ctx);

        await page.goto(BASE + '/products', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivProductCard', { timeout: 8000 });
        await page.evaluate(() => {
            const card = document.getElementById('annivProductCenterpiece');
            if (card) {
                card.classList.remove('is-animating');
                card.classList.add('is-settled');
            }
        });
        await page.waitForTimeout(400);

        const productCard = await page.$('#annivProductCard');
        if (productCard) {
            await productCard.screenshot({
                path: path.join(OUT_DIR, `products-master-${width === 1440 ? '1440' : '390'}.png`),
            });
            console.log(`  Saved products-master-${width === 1440 ? '1440' : '390'}.png`);
        }
        await ctx.close();
    }

    // 6. Reduced Motion Capture
    console.log('\n6. Capturing reduced motion state (1440px)...');
    {
        const ctx = await createContext(1440, 900, { reducedMotion: 'reduce' });
        const page = await setupPage(ctx);

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });
        await page.waitForTimeout(500);

        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({
                path: path.join(OUT_DIR, 'hero-reduced-motion-1440.png'),
            });
            console.log('  Saved hero-reduced-motion-1440.png');
        }
        await ctx.close();
    }

    // 7. Zoom overflow checks at 125% and 150%
    console.log('\n7. Verifying zero horizontal scroll at 125% and 150% zoom...');
    for (const zoom of [1.25, 1.5]) {
        const ctx = await createContext(1440, 900, { dsf: zoom });
        const page = await setupPage(ctx);

        await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
        const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
        const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
        console.log(`  Zoom ${(zoom * 100)}%: clientWidth=${clientWidth}, scrollWidth=${scrollWidth}`);
        if (scrollWidth > clientWidth) {
            console.error(`  FAIL: Horizontal overflow detected at ${zoom * 100}% zoom!`);
            process.exit(1);
        } else {
            console.log(`  Pass: Zero horizontal overflow at ${zoom * 100}% zoom.`);
        }
        await ctx.close();
    }

    await browser.close();
    console.log('\nAll active captures and zoom checks completed successfully!');
}

run().catch(err => {
    console.error('Capture error:', err);
    process.exit(1);
});
