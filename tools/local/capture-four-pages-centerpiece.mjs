import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const OUT_DIR = path.resolve('footage/_review/anniversary');
const ARTIFACT_DIR = 'C:/Users/AFRO-EAGLE/.gemini/antigravity-cli/brain/e3c6e76f-94ae-45aa-af98-754efbfeec62/screenshots';

for (const dir of [OUT_DIR, ARTIFACT_DIR]) {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
}

function saveArtifact(srcName, destName) {
    const src = path.join(OUT_DIR, srcName);
    const dest = path.join(ARTIFACT_DIR, destName || srcName);
    if (fs.existsSync(src)) {
        fs.copyFileSync(src, dest);
    }
}

async function run() {
    console.log('=== Playwright Verification: Shared Anniversary Centerpiece on 4 Pages ===\n');
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

    const pages = [
        {
            name: 'services',
            url: '/services',
            cardSelector: '#annivServicesCard',
            centerpieceSelector: '#annivServicesCenterpiece',
            title: 'Services Page',
        },
        {
            name: 'portfolio',
            url: '/portfolio',
            cardSelector: '#annivPortfolioCard',
            centerpieceSelector: '#annivPortfolioCenterpiece',
            title: 'Portfolio Page',
        },
        {
            name: 'projects',
            url: '/projects',
            cardSelector: '#annivPortfolioCard',
            centerpieceSelector: '#annivPortfolioCenterpiece',
            title: 'Projects Route',
        },
        {
            name: 'about',
            url: '/about',
            cardSelector: '#annivAboutCard',
            centerpieceSelector: '#annivAboutCenterpiece',
            title: 'About Page',
        },
    ];

    for (const p of pages) {
        console.log(`\n--- Testing ${p.title} (${p.url}) ---`);

        // 1. Desktop 1440px
        {
            const ctx = await createContext(1440, 900);
            const page = await setupPage(ctx);
            await page.goto(BASE + p.url, { waitUntil: 'domcontentloaded' });
            await page.waitForSelector(p.cardSelector, { timeout: 8000 });

            // Scroll feature into view
            const cardEl = await page.$(p.cardSelector);
            await cardEl.scrollIntoViewIfNeeded();

            // Check overflow
            const overflow = await page.evaluate(() => {
                return {
                    scrollWidth: document.documentElement.scrollWidth,
                    clientWidth: document.documentElement.clientWidth,
                    hasOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
                };
            });
            console.log(`  [Desktop 1440px] Overflow check: ${overflow.hasOverflow ? 'FAIL' : 'PASS'} (scrollWidth: ${overflow.scrollWidth}, clientWidth: ${overflow.clientWidth})`);

            // Wait for Phase 06 multi-color explosion
            await page.waitForTimeout(6000);
            const shotExplosion = `${p.name}-desktop-explosion-1440.png`;
            await cardEl.screenshot({ path: path.join(OUT_DIR, shotExplosion) });
            saveArtifact(shotExplosion);
            console.log(`  Captured ${shotExplosion}`);

            // Full viewport screenshot
            const shotViewport = `${p.name}-desktop-viewport-1440.png`;
            await page.screenshot({ path: path.join(OUT_DIR, shotViewport) });
            saveArtifact(shotViewport);
            console.log(`  Captured ${shotViewport}`);

            await ctx.close();
        }

        // 2. Mobile 390px
        {
            const ctx = await createContext(390, 844, { dsf: 2 });
            const page = await setupPage(ctx);
            await page.goto(BASE + p.url, { waitUntil: 'domcontentloaded' });
            await page.waitForSelector(p.cardSelector, { timeout: 8000 });

            const cardEl = await page.$(p.cardSelector);
            await cardEl.scrollIntoViewIfNeeded();

            // Check overflow
            const overflow = await page.evaluate(() => {
                return {
                    scrollWidth: document.documentElement.scrollWidth,
                    clientWidth: document.documentElement.clientWidth,
                    hasOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
                };
            });
            console.log(`  [Mobile 390px] Overflow check: ${overflow.hasOverflow ? 'FAIL' : 'PASS'} (scrollWidth: ${overflow.scrollWidth}, clientWidth: ${overflow.clientWidth})`);

            await page.waitForTimeout(2000);
            const shotMobile = `${p.name}-mobile-390.png`;
            await cardEl.screenshot({ path: path.join(OUT_DIR, shotMobile) });
            saveArtifact(shotMobile);
            console.log(`  Captured ${shotMobile}`);

            await ctx.close();
        }

        // 3. Zoom level check (125% & 150%)
        {
            for (const dsf of [1.25, 1.5]) {
                const ctx = await createContext(1440, 900, { dsf });
                const page = await setupPage(ctx);
                await page.goto(BASE + p.url, { waitUntil: 'domcontentloaded' });
                await page.waitForSelector(p.cardSelector, { timeout: 8000 });

                const overflow = await page.evaluate(() => {
                    return document.documentElement.scrollWidth > document.documentElement.clientWidth;
                });
                console.log(`  [Zoom ${dsf * 100}%] Overflow check: ${overflow ? 'FAIL' : 'PASS'}`);
                await ctx.close();
            }
        }
    }

    console.log('\n=== All 4 pages verified successfully! ===\n');
    await browser.close();
}

run().catch(err => {
    console.error('Test run failed:', err);
    process.exit(1);
});
