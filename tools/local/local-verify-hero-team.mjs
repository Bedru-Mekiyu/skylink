// LOCAL USE ONLY. Homepage hero still (CEO) + About team layout at 320..1920 px and 125% / 150% scaling.
//   node tools/local/local-verify-hero-team.mjs   (site on http://127.0.0.1:8000)
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const out = path.resolve('footage/_review/hero-ceo');
fs.mkdirSync(out, { recursive: true });
const results = [];
const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const sizes = [[320, 640, 1], [360, 740, 1], [390, 844, 1], [768, 1024, 1], [1024, 768, 1], [1440, 900, 1], [1920, 1080, 1], [1536, 864, 1.25], [1280, 720, 1.5]];
for (const [w, h, dsf] of sizes) {
    const label = `${w}x${h}${dsf === 1 ? '' : '@x' + dsf}`;
    // reduced motion keeps the still (poster) on screen, so the CEO picture is what we capture
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, reducedMotion: 'reduce' });
    const p = await ctx.newPage();
    await p.goto(base + '/', { waitUntil: 'load' });
    await p.waitForTimeout(1500);
    const m = await p.evaluate(() => {
        const img = document.querySelector('.hero-media__poster');
        const h1 = document.querySelector('.hero h1'); const r = img.getBoundingClientRect(); const t = h1 && h1.getBoundingClientRect();
        return { src: img.currentSrc.split('/').pop().split('?')[0], alt: img.alt, ovf: document.documentElement.scrollWidth - innerWidth, loaded: img.complete && img.naturalWidth > 0, nat: img.naturalWidth + 'x' + img.naturalHeight, h1: t ? [Math.round(t.left), Math.round(t.right)] : null };
    });
    rec(`hero still @${label}: CEO file, alt text, no sideways scroll`, /^poster(-mobile)?\.webp$/.test(m.src) && /Muhammad Karim/.test(m.alt) && m.ovf <= 0 && m.loaded, JSON.stringify(m));
    await p.screenshot({ path: path.join(out, `home-${label}.png`) });
    await p.goto(base + '/about', { waitUntil: 'load' });
    const team = p.locator('text=Our Management Team');
    await team.scrollIntoViewIfNeeded(); await p.waitForTimeout(800);
    const t = await p.evaluate(() => {
        const cards = [...document.querySelectorAll('.team-section .mb-5 .row')].map((r) => [...r.children].map((c) => Math.round(c.getBoundingClientRect().top)));
        return { rows: cards.map((c) => c.length), ovf: document.documentElement.scrollWidth - innerWidth };
    });
    rec(`team @${label}: first row 1 card (CEO), second row 3, no sideways scroll`, t.rows[0] === 1 && t.rows[1] === 3 && t.ovf <= 0, JSON.stringify(t));
    await p.locator('.team-section .mb-5').first().screenshot({ path: path.join(out, `team-${label}.png`) });
    await ctx.close();
}
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
