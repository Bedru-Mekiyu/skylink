// LIGHT spot check on the test site only (bedru): About intro text, team section, Milestones-to-Gallery gap. 3 page loads.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = 'https://bedru.skylinkict.com';
const out = path.resolve('footage/_review/spot-bedru-about-text');
fs.mkdirSync(out, { recursive: true });
const results = []; const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
for (const [w, h, tag] of [[1440, 900, 'desktop'], [390, 844, 'phone']]) {
    const p = await (await browser.newContext({ viewport: { width: w, height: h }, reducedMotion: 'reduce' })).newPage();
    const errs = []; p.on('console', (m) => { if (m.type() === 'error' && !/tawk|ERR_FAILED|favicon|Failed to load resource/i.test(m.text())) errs.push(m.text().slice(0, 100)); });
    await p.goto(base + '/about', { waitUntil: 'load' }); await p.waitForTimeout(2500);
    const m = await p.evaluate(() => {
        const t = (s) => document.querySelector(s); const r = (e) => e.getBoundingClientRect();
        const lead = t('.team-card--lead .team-card__photo'), gal = document.getElementById('gallery');
        const lastItem = [...document.querySelectorAll('.awards-section .award-item')].pop();
        return {
            headline: t('.about-intro__headline').innerText.replace(/\s+/g, ' ').trim(), link: t('[data-ai-more]').innerText.replace(/\s+/g, ' ').trim(), href: t('[data-ai-more]').getAttribute('href'),
            oldText: /Best IT Solution With Many Years|MORE INFORMATION/.test(document.body.innerText), label: !!t('.about-intro .section-tag'),
            paragraph: /turn visionary concepts/.test(t('.about-intro__text').innerText), points: [...document.querySelectorAll('.about-intro__points li')].length,
            ceo: Math.round(r(lead).width) + 'x' + Math.round(r(lead).height), landscape: r(lead).width > r(lead).height,
            gap: gal ? Math.round(r(gal.querySelector('h2')).top - r(lastItem).bottom) : null, slides: gal ? gal.querySelectorAll('.sk-gallery__slide').length : 0, ovf: document.documentElement.scrollWidth - innerWidth,
            countText: t('[data-ai-count]').textContent,
        };
    });
    rec(`${tag}: headline text exact, link MORE ABOUT US -> #journey, label and lead-in gone, paragraph + 4 items kept, count 5`, m.headline === '5+ YEARS OF DRIVING DIGITAL INNOVATION AND TECHNOLOGICAL EXCELLENCE.' && m.link.startsWith('MORE ABOUT US') && m.href === '#journey' && !m.oldText && !m.label && m.paragraph && m.points === 4 && m.countText === '5', JSON.stringify({ headline: m.headline, link: m.link }));
    rec(`${tag}: CEO frame is landscape (${m.ceo}), Milestones-to-Gallery gap ${m.gap}px, 15 slides, no sideways scroll, no console errors`, m.landscape && m.gap >= 36 && m.gap <= (w >= 768 ? 66 : 56) && m.slides === 15 && m.ovf <= 0 && errs.length === 0, JSON.stringify({ errs }));
    if (tag === 'desktop') { await p.locator('.about-intro').scrollIntoViewIfNeeded(); await p.waitForTimeout(800); await p.screenshot({ path: path.join(out, 'intro.png') }); await p.locator('.team-card--lead').scrollIntoViewIfNeeded(); await p.waitForTimeout(1500); await p.screenshot({ path: path.join(out, 'team.png') }); await p.locator('#gallery').scrollIntoViewIfNeeded(); await p.waitForTimeout(1500); await p.screenshot({ path: path.join(out, 'milestones-gallery.png') }); }
    await p.close();
    await new Promise((r) => setTimeout(r, 3000));
}
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
