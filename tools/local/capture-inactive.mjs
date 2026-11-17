import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const OUT_DIR = path.resolve('footage/_review/anniversary');

async function run() {
    console.log('Capturing campaign INACTIVE state...');
    const browser = await chromium.launch({ headless: true });

    async function capture(url, width, height, filename) {
        const ctx = await browser.newContext({
            viewport: { width, height },
            deviceScaleFactor: 1,
        });
        const page = await ctx.newPage();
        await page.route(/tawk\.to/, r => r.abort());
        await page.route(/\.(mp4|webm)(\?|$)/i, r => r.abort());
        await page.route(/fonts\.(googleapis|gstatic)\.com/, r => r.abort());

        await page.goto(BASE + url, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(500);

        // Verify anniversary elements do not exist
        const hasBadge = await page.$('#annivBadgeBtn');
        const hasHero = await page.$('#annivHeroCard');
        const hasProduct = await page.$('#annivProductCard');
        if (hasBadge || hasHero || hasProduct) {
            console.error(`WARNING: Anniversary elements found on ${url} while inactive!`);
        } else {
            console.log(`Verified clean: zero anniversary elements on ${url} (inactive).`);
        }

        await page.screenshot({ path: path.join(OUT_DIR, filename), fullPage: false });
        console.log(`  Saved ${filename}`);
        await ctx.close();
    }

    await capture('/', 1440, 900, 'campaign-inactive-home-1440.png');
    await capture('/', 390, 844, 'campaign-inactive-home-390.png');
    await capture('/products', 1440, 900, 'campaign-inactive-products-1440.png');
    await capture('/products', 390, 844, 'campaign-inactive-products-390.png');

    await browser.close();
}

run().catch(err => {
    console.error('Inactive capture error:', err);
    process.exit(1);
});
