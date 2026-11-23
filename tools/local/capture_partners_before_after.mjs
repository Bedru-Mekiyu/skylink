import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const base = 'https://bedru.skylinkict.com';
const outDir = path.resolve('footage/_review/partner-shapes');
fs.mkdirSync(outDir, { recursive: true });

const nap = (ms = 2000) => new Promise((r) => setTimeout(r, ms));
const mode = process.argv[2] || 'before'; // 'before' or 'after'

const browser = await chromium.launch();

const viewports = [
    { name: '1440', width: 1440, height: 900 },
    { name: '768', width: 768, height: 1024 },
    { name: '360', width: 360, height: 780 }
];

for (const vp of viewports) {
    const ctx = await browser.newContext({
        httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined,
        viewport: { width: vp.width, height: vp.height },
        reducedMotion: 'reduce' // stops the animation so screenshot captures fixed positions cleanly
    });
    const page = await ctx.newPage();
    page.setDefaultTimeout(60000);

    // English
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await nap(1500);
    const sectionEn = page.locator('.partners-section').first();
    if (await sectionEn.count()) {
        await sectionEn.scrollIntoViewIfNeeded();
        await nap(1000);
        const shotPathEn = path.join(outDir, `${mode}-${vp.name}-en.png`);
        await sectionEn.screenshot({ path: shotPathEn });
        console.log(`Saved ${shotPathEn}`);
    }

    // Amharic (check if ?lang=am or cookie or locale toggle)
    // Try setting cookie or url if supported, or visit
    await page.goto(base + '/?lang=am', { waitUntil: 'domcontentloaded' });
    await nap(1500);
    const sectionAm = page.locator('.partners-section').first();
    if (await sectionAm.count()) {
        await sectionAm.scrollIntoViewIfNeeded();
        await nap(1000);
        const shotPathAm = path.join(outDir, `${mode}-${vp.name}-am.png`);
        await sectionAm.screenshot({ path: shotPathAm });
        console.log(`Saved ${shotPathAm}`);
    }

    await ctx.close();
}

await browser.close();
console.log(`All ${mode.toUpperCase()} screenshots captured successfully!`);
