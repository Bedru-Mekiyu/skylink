// LOCAL USE ONLY. Screenshots the "Software Team" card (/about) and the support team card (/contact).
//   node tools/local/capture-team-swap.mjs http://127.0.0.1:8000 before|after
// Writes screenshots and cards.json to storage/app/media-update/team-swap/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const label = process.argv[3] || 'after';
const out = path.resolve('storage/app/media-update/team-swap');
fs.mkdirSync(out, { recursive: true });

const cards = [
    { name: 'software-about', url: '/about', card: '.team-showcase-card', img: 'img' },
    { name: 'support-contact', url: '/contact', card: '.operators-container', img: 'img.operators-image' },
];
const report = {};
const browser = await chromium.launch();
for (const width of [1440, 390]) {
    const ctx = await browser.newContext({ viewport: { width, height: 900 } });
    await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
    const page = await ctx.newPage();
    for (const c of cards) {
        await page.goto(base + c.url, { waitUntil: 'domcontentloaded' });
        await page.keyboard.press('Escape'); // skip the first-visit intro if it shows
        await page.waitForTimeout(500);
        const card = page.locator(c.card).first();
        if (!await card.isVisible()) { report[`${c.name}-${width}`] = 'hidden at this width (the site hides it below 992px)'; continue; }
        await card.scrollIntoViewIfNeeded();
        // Images are lazy and the page uses AOS: wait for the picture itself.
        await page.waitForFunction((s) => { const i = document.querySelector(s); return i && i.complete && i.naturalWidth > 0; }, `${c.card} ${c.img}`, { timeout: 15000 }).catch(() => {});
        await page.waitForTimeout(1200);
        await card.screenshot({ path: path.join(out, `${label}-${c.name}-${width}.png`) });
        report[`${c.name}-${width}`] = await card.locator(c.img).first().evaluate((i) => ({
            title: (i.closest('.team-showcase-card')?.querySelector('h3') || i.closest('.operators-container')?.querySelector('h4'))?.textContent.trim(),
            src: i.getAttribute('src'), srcset: (i.getAttribute('srcset') || '').split(',')[0].trim(), currentSrc: i.currentSrc, alt: i.alt,
            natural: [i.naturalWidth, i.naturalHeight], shown: [Math.round(i.getBoundingClientRect().width), Math.round(i.getBoundingClientRect().height)],
        }));
    }
    await ctx.close();
}
fs.writeFileSync(path.join(out, `${label}-cards.json`), JSON.stringify(report, null, 2));
await browser.close();
console.log(JSON.stringify(report, null, 1));
