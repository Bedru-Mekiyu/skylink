import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const OUT_DIR = path.resolve('footage/_review/anniversary');

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}

async function run() {
    console.log('Launching browser for Visual Enhancements capture...');
    const browser = await chromium.launch({ headless: true });

    // Setup context with intro marked as seen
    async function createContext(width, height, dsf = 1) {
        const ctx = await browser.newContext({
            viewport: { width, height },
            deviceScaleFactor: dsf,
        });
        await ctx.addInitScript(() => {
            window.localStorage.setItem('skylink_anniv_5y_intro', '1');
            document.cookie = 'skylink_anniv_5y_intro=1; path=/';
        });
        return ctx;
    }

    // 1. Homepage Hero captures at 1440, 1024, 768, 390
    const heroViewports = [
        { name: '1440', width: 1440, height: 900 },
        { name: '1024', width: 1024, height: 768 },
        { name: '768', width: 768, height: 1024 },
        { name: '390', width: 390, height: 844 },
    ];

    for (const vp of heroViewports) {
        const ctx = await createContext(vp.width, vp.height);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());

        await page.goto(BASE + '/', { waitUntil: 'networkidle' });
        await page.waitForSelector('#annivHeroCard', { timeout: 8000 });
        await page.waitForTimeout(400);

        // Capture hero section view
        const heroEl = await page.$('.hero');
        if (heroEl) {
            await heroEl.screenshot({
                path: path.join(OUT_DIR, `hero-feature-${vp.name}.png`),
            });
            console.log(`Saved hero-feature-${vp.name}.png`);
        }

        // Also capture the isolated card itself
        const cardEl = await page.$('#annivHeroCard');
        if (cardEl && vp.name === '1440') {
            await cardEl.screenshot({
                path: path.join(OUT_DIR, `hero-card-closeup-1440.png`),
            });
            console.log(`Saved hero-card-closeup-1440.png`);
        }
        await ctx.close();
    }

    // 2. News Page captures at 1440 and 390
    const newsViewports = [
        { name: '1440', width: 1440, height: 900 },
        { name: '390', width: 390, height: 844 },
    ];

    for (const vp of newsViewports) {
        const ctx = await createContext(vp.width, vp.height);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());

        await page.goto(BASE + '/news', { waitUntil: 'networkidle' });
        await page.waitForSelector('#annivNewsCard', { timeout: 8000 });
        await page.waitForTimeout(400);

        const cardEl = await page.$('#annivNewsCard');
        if (cardEl) {
            await cardEl.screenshot({
                path: path.join(OUT_DIR, `news-card-${vp.name}.png`),
            });
            console.log(`Saved news-card-${vp.name}.png`);
        }
        await ctx.close();
    }

    // 3. About Page captures at 1440 and 390
    const aboutViewports = [
        { name: '1440', width: 1440, height: 900 },
        { name: '390', width: 390, height: 844 },
    ];

    for (const vp of aboutViewports) {
        const ctx = await createContext(vp.width, vp.height);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());

        await page.goto(BASE + '/about', { waitUntil: 'networkidle' });
        await page.waitForSelector('#annivAboutCard', { timeout: 8000 });
        await page.waitForTimeout(400);

        const cardEl = await page.$('#annivAboutCard');
        if (cardEl) {
            await cardEl.screenshot({
                path: path.join(OUT_DIR, `about-card-${vp.name}.png`),
            });
            console.log(`Saved about-card-${vp.name}.png`);
        }
        await ctx.close();
    }

    // 4. Zoom overflow check at 125% and 150%
    console.log('\nRunning zoom checks at 125% and 150%...');
    for (const zoom of [1.25, 1.5]) {
        const ctx = await createContext(1440, 900, zoom);
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());

        await page.goto(BASE + '/', { waitUntil: 'networkidle' });
        const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
        const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
        console.log(`Zoom ${(zoom * 100)}%: clientWidth=${clientWidth}, scrollWidth=${scrollWidth}`);
        if (scrollWidth > clientWidth) {
            console.warn(`WARNING: Horizontal overflow detected at ${zoom * 100}% zoom!`);
        } else {
            console.log(`Pass: Zero horizontal overflow at ${zoom * 100}% zoom.`);
        }
        await ctx.close();
    }

    await browser.close();
    console.log('\nAll visual enhancement captures completed successfully!');
}

run().catch(err => {
    console.error('Capture error:', err);
    process.exit(1);
});
