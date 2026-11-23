import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const outDir = path.resolve('footage/_review/about-team');
fs.mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch();
const viewports = [
    { name: '1440', width: 1440, height: 900 },
    { name: '1024', width: 1024, height: 768 },
    { name: '768', width: 768, height: 1024 },
    { name: '360', width: 360, height: 780, isMobile: true }
];

// Serve from local test
// We can use a request to the local laravel app or bedru
// Let's test on local artisan serve if running, or verify image aspect ratio and layout via DOM
for (const vp of viewports) {
    const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, isMobile: !!vp.isMobile });
    const page = await ctx.newPage();
    // We will run this against bedru once deployed or local
    await ctx.close();
}
await browser.close();
console.log('Script ready');
