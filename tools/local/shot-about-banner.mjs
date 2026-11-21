// LOCAL USE ONLY: About banner screenshots at 360/768/1440/1920 (viewport top, no ceremony intro).  node tools/local/shot-about-banner.mjs <label>
import { chromium } from 'playwright-core';
import fs from 'node:fs';
const label = process.argv[2] || 'after';
fs.mkdirSync('footage/_review/about-banner', { recursive: true });
const b = await chromium.launch();
for (const [w, h] of [[360, 740], [768, 1024], [1440, 900], [1920, 1080]]) {
    const p = await (await b.newContext({ viewport: { width: w, height: h }, reducedMotion: 'reduce' })).newPage();
    await p.goto('http://127.0.0.1:8000/about', { waitUntil: 'load' });
    await p.addStyleTag({ content: '.anniv5-intro{display:none!important}' });
    await p.waitForTimeout(1500);
    await p.screenshot({ path: `footage/_review/about-banner/${label}-${w}.png`, clip: { x: 0, y: 0, width: w, height: Math.min(h, w < 600 ? 460 : 760) } });
    await p.close();
}
await b.close();
