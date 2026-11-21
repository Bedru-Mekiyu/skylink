// LOCAL USE ONLY. Gallery at the bottom of the About page: order, seams, empty state, lazy images, start/stop by visibility, anchors.
//   EXPECT=photos|none node tools/local/local-verify-gallery-position.mjs   (site on http://127.0.0.1:8000)
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const expect = process.env.EXPECT || 'photos';
const out = path.resolve(process.env.OUT || 'footage/_review/gallery-bottom');
fs.mkdirSync(out, { recursive: true });
const results = [];
const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const introDone = (p) => p.waitForFunction(() => { const i = document.querySelector('.anniv5-intro'); return !i || getComputedStyle(i).display === 'none' || getComputedStyle(i).visibility === 'hidden' || i.hidden || parseFloat(getComputedStyle(i).opacity) === 0; }, null, { timeout: 40000 }).catch(() => {});
const browser = await chromium.launch();
const sizes = [[320, 640, 1], [360, 740, 1], [390, 844, 1], [768, 1024, 1], [1024, 768, 1], [1440, 900, 1], [1920, 1080, 1], [1536, 864, 1.25], [1280, 720, 1.5]];
for (const [w, h] of sizes) {
    const label = `${w}x${h}`;
    const ctx = await browser.newContext({ viewport: { width: w, height: h } });
    const p = await ctx.newPage();
    const errs = [];
    p.on('console', (m) => { if (m.type() === 'error' && !/tawk|ERR_FAILED|favicon/i.test(m.text())) errs.push(m.text().slice(0, 120)); });
    await p.goto(base + '/about', { waitUntil: 'load' }); await introDone(p);
    const m = await p.evaluate(async () => {
        const top = (e) => e.getBoundingClientRect().top + scrollY, bottom = (e) => e.getBoundingClientRect().bottom + scrollY;
        const awards = document.querySelector('.awards-section'), gal = document.getElementById('gallery'), footer = document.querySelector('footer.footer');
        const intro = document.getElementById('about-intro'), team = document.querySelector('.team-section');
        const lazy = gal ? [...gal.querySelectorAll('img')].every((i) => i.loading === 'lazy') : true;
        const loadedAtTop = gal ? [...gal.querySelectorAll('img')].filter((i) => i.complete && i.naturalWidth > 0).length : 0;
        const oldSpot = [...document.querySelectorAll('section')].map((s) => s.id || '').includes('gallery') && (document.getElementById('gallery').previousElementSibling === null);
        return {
            ovf: document.documentElement.scrollWidth - innerWidth,
            hasGallery: !!gal, lazy, loadedAtTop,
            orderOk: gal ? top(team) < top(awards) && bottom(awards) <= top(gal) + 1 && bottom(gal) <= top(footer) + 1 : bottom(awards) <= top(footer) + 40,
            seamAwardsGallery: gal ? Math.round(top(gal) - bottom(awards)) : null,
            seamGalleryFooter: gal ? Math.round(top(footer) - bottom(gal)) : Math.round(top(footer) - bottom(awards)),
            galBg: gal ? getComputedStyle(gal).backgroundColor : null, awardsBg: getComputedStyle(awards).backgroundColor, footerBg: getComputedStyle(footer).backgroundColor,
            introOk: !!intro && !!document.querySelector('.team-card--lead') && !!document.querySelector('.about-hero'),
            gapMilestonesGallery: gal ? Math.round(gal.querySelector('h2').getBoundingClientRect().top + scrollY - Math.max(...[...document.querySelectorAll('.awards-section .award-item')].map((e) => e.getBoundingClientRect().bottom + scrollY))) : null,
            dupIds: document.querySelectorAll('#gallery').length, oldBetween: (() => { const t = document.querySelector('.team-section'); const prev = t.previousElementSibling; return prev ? prev.id : ''; })(),
        };
    });
    rec(`@${label}: no sideways scroll, intro/team/CEO/banner present, no console errors`, m.ovf <= 0 && m.introOk && errs.length === 0, JSON.stringify({ ovf: m.ovf, errs }));
    if (expect === 'photos') {
        rec(`@${label}: order Milestones -> Gallery -> footer, no gap or overlap (gaps ${m.seamAwardsGallery}/${m.seamGalleryFooter})`, m.hasGallery && m.orderOk && Math.abs(m.seamAwardsGallery) <= 1 && Math.abs(m.seamGalleryFooter) <= 1);
        rec(`@${label}: gap from the last Milestones item to the Gallery title is modest (${m.gapMilestonesGallery}px)`, m.gapMilestonesGallery >= 36 && m.gapMilestonesGallery <= (w >= 768 ? 66 : 56));
        rec(`@${label}: plain white gallery between light Milestones and light footer (${m.awardsBg} | ${m.galBg} | ${m.footerBg})`, m.galBg === 'rgba(0, 0, 0, 0)' || m.galBg === 'rgb(255, 255, 255)');
        rec(`@${label}: images lazy, none decoded before the section is near (${m.loadedAtTop} loaded at top)`, m.lazy && m.loadedAtTop < 6);
        rec(`@${label}: old spot is empty (nothing with id gallery before the team section: "${m.oldBetween}")`, m.dupIds === 1 && m.oldBetween !== 'gallery');
        if (w === 390 || w === 1440 || w === 1920) {
            await p.locator('#gallery').scrollIntoViewIfNeeded(); await p.waitForTimeout(1800);
            await p.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight)); await p.waitForTimeout(700);
            await p.screenshot({ path: path.join(out, `bottom-${expect}-${label}.png`) });
        }
    } else {
        rec(`@${label}: no gallery, Milestones directly above the footer (gap ${m.seamGalleryFooter})`, !m.hasGallery && Math.abs(m.seamGalleryFooter) <= 40);
        if (w === 390 || w === 1440) { await p.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight)); await p.waitForTimeout(600); await p.screenshot({ path: path.join(out, `bottom-${expect}-${label}.png`) }); }
    }
    await ctx.close();
}
if (expect === 'photos') {
    // motion starts only when the section is in view, and stops when it leaves
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const p = await ctx.newPage(); await p.goto(base + '/about', { waitUntil: 'load' }); await introDone(p); await p.waitForTimeout(6500);
    const pos = () => p.evaluate(() => { const b = document.querySelector('#gallery .sk-gallery__open[tabindex="0"]'); return b ? b.parentNode.dataset.i : '?'; });
    const idle0 = await pos(); await p.waitForTimeout(6000); const idle1 = await pos();
    rec('far above the gallery it does not turn (off-screen)', idle0 === idle1, `${idle0} -> ${idle1}`);
    await p.locator('#gallery').scrollIntoViewIfNeeded(); await p.waitForTimeout(500);
    const a = await pos(); await p.waitForTimeout(6500); const b = await pos();
    rec('it starts turning once the section scrolls into view', a !== b, `${a} -> ${b}`);
    await p.evaluate(() => window.scrollTo(0, 0)); await p.waitForTimeout(800);
    const c = await pos(); await p.waitForTimeout(6000); const d = await pos();
    rec('it pauses again when scrolled away', c === d, `${c} -> ${d}`);
    // anchors still work from the top of the page
    await p.click('[data-ai-more]'); await p.waitForTimeout(1500);
    rec('MORE INFORMATION still scrolls to the Journey section', await p.evaluate(() => Math.abs(document.getElementById('journey').getBoundingClientRect().top) < 100));
    await ctx.close();
}
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
