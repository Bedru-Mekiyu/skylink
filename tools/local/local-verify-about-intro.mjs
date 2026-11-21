// LOCAL USE ONLY. About intro block (floating card) + larger CEO card, full width matrix.
//   node tools/local/local-verify-about-intro.mjs   (site on http://127.0.0.1:8000)
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const out = path.resolve(process.env.OUT || 'footage/_review/about-intro');
fs.mkdirSync(out, { recursive: true });
// With the anniversary ceremony forced on, a full-screen intro plays once per visit: wait for it before using the mouse.
const introDone = (p) => p.waitForFunction(() => { const i = document.querySelector('.anniv5-intro'); return !i || getComputedStyle(i).display === 'none' || getComputedStyle(i).visibility === 'hidden' || i.hidden || parseFloat(getComputedStyle(i).opacity) === 0; }, null, { timeout: 40000 }).catch(() => {});
const results = [];
const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const sizes = [[320, 640, 1], [360, 740, 1], [390, 844, 1], [430, 932, 1], [768, 1024, 1], [1024, 768, 1], [1366, 768, 1], [1440, 900, 1], [1920, 1080, 1], [1536, 864, 1.25], [1280, 720, 1.5]];
for (const [w, h, dsf] of sizes) {
    const label = `${w}x${h}${dsf === 1 ? '' : '@x' + dsf}`;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, reducedMotion: 'no-preference' });
    const p = await ctx.newPage();
    const errs = [];
    p.on('console', (m) => { if (m.type() === 'error' && !/tawk|ERR_FAILED|favicon/i.test(m.text())) errs.push(m.text().slice(0, 120)); });
    await p.goto(base + '/about', { waitUntil: 'load' }); await introDone(p);
    await p.locator('#about-intro').scrollIntoViewIfNeeded();
    await p.waitForTimeout(2600); // reveal + count-up finish
    const m = await p.evaluate(() => {
        const q = (s) => document.querySelector(s); const r = (e) => e.getBoundingClientRect();
        const card = q('.about-intro__card'), lines = [...document.querySelectorAll('.ai-line')];
        const more = q('[data-ai-more]'), body = q('.about-intro__body');
        const lead = q('.team-card--lead .team-card__photo'), others = [...document.querySelectorAll('.team-card:not(.team-card--lead) .team-card__photo')];
        const cr = r(card), br = r(body);
        return {
            ovf: document.documentElement.scrollWidth - innerWidth,
            threeLines: lines.length === 3 && lines.every((l) => r(l).height < parseFloat(getComputedStyle(l).fontSize) * 1.6),
            fits: lines.every((l) => l.scrollWidth <= card.clientWidth - 2),
            count: q('[data-ai-count]').textContent,
            visible: parseFloat(getComputedStyle(card).opacity) === 1,
            tap: Math.round(r(more).height),
            stacked: br.top >= cr.bottom - 1,
            noPhone: !/Call to ask|\+251|fa-phone/.test(q('#about-intro').innerText + q('#about-intro').innerHTML.replace(/<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>/g, '')),
            card: Math.round(cr.width), bodyBg: getComputedStyle(q('#about-intro')).backgroundColor,
            leadW: Math.round(r(lead).width), otherW: Math.round(Math.max(...others.map((o) => r(o).width))), vw: innerWidth,
        };
    });
    rec(`intro @${label}: no overflow, headline 3 lines inside the card, count 5, visible, tap >= 44, no phone`, m.ovf <= 0 && m.threeLines && m.fits && m.count === '5' && m.visible && m.tap >= 44 && m.noPhone && m.bodyBg === 'rgb(255, 255, 255)', JSON.stringify(m));
    rec(`layout @${label}: ${w >= 992 ? 'two columns' : 'card on top, text below'}`, w >= 992 ? !m.stacked : m.stacked);
    rec(`CEO card @${label}: larger than the others (${m.leadW} vs ${m.otherW}, ratio ${(m.leadW / m.otherW).toFixed(2)})`, m.leadW > m.otherW * 1.5 && m.leadW <= m.vw);
    await p.locator('#about-intro').screenshot({ path: path.join(out, `intro-${label}.png`) });
    await p.locator('text=Our Management Team').locator('xpath=..').screenshot({ path: path.join(out, `team-${label}.png`) });
    rec(`no console errors @${label}`, errs.length === 0, errs.join(' | '));
    await ctx.close();
}
// reduced motion + Save-Data: static (no hidden state), count already 5, link jumps to #journey
for (const mode of ['reduce', 'savedata']) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: mode === 'reduce' ? 'reduce' : 'no-preference' });
    const p = await ctx.newPage();
    if (mode === 'savedata') await p.addInitScript(() => { Object.defineProperty(navigator, 'connection', { value: { saveData: true, effectiveType: '4g' }, configurable: true }); });
    await p.goto(base + '/about', { waitUntil: 'load' });
    const s = await p.evaluate(() => ({ anim: document.documentElement.classList.contains('ai-anim'), op: getComputedStyle(document.querySelector('.about-intro__card')).opacity, word: getComputedStyle(document.querySelector('.ai-w')).opacity, count: document.querySelector('[data-ai-count]').textContent }));
    rec(`${mode}: shown static immediately (no animation class, opacity 1, 5+)`, !s.anim && s.op === '1' && s.word === '1' && s.count === '5', JSON.stringify(s));
    await p.click('[data-ai-more]'); await p.waitForTimeout(600);
    rec(`${mode}: MORE INFORMATION goes to the Journey section`, await p.evaluate(() => Math.abs(document.getElementById('journey').getBoundingClientRect().top) < 80));
    await ctx.close();
}
// hover: arrow nudges right, card lifts
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const p = await ctx.newPage(); await p.goto(base + '/about', { waitUntil: 'load' }); await introDone(p);
    await p.locator('#about-intro').scrollIntoViewIfNeeded(); await p.evaluate(() => document.querySelector('.about-intro__card').scrollIntoView({ block: 'center' })); await p.waitForTimeout(2600);
    const x0 = await p.evaluate(() => document.querySelector('.about-intro__arrow').getBoundingClientRect().left);
    await p.hover('.about-intro__more'); await p.waitForTimeout(500);
    const x1 = await p.evaluate(() => document.querySelector('.about-intro__arrow').getBoundingClientRect().left);
    rec('hover: the arrow nudges right', x1 - x0 >= 4, `${x0} -> ${x1}`);
    const hb = await p.locator('.about-intro__headline').boundingBox(); await p.mouse.move(hb.x + 40, hb.y + hb.height / 2, { steps: 6 }); await p.waitForTimeout(900);
    const ty = await p.evaluate(() => { const m = getComputedStyle(document.querySelector('.about-intro__card')).transform; return m === 'none' ? 0 : parseFloat(m.replace(/^matrix\(|\)$/g, '').split(',')[5]); });
    rec('hover: the card lifts a little (translateY -2..-8 px)', ty <= -2 && ty >= -8, 'translateY ' + ty + ' ' + await p.evaluate(() => getComputedStyle(document.querySelector('.about-intro__card')).transform + ' hov=' + document.querySelector('.about-intro__card').matches(':hover')));
    await ctx.close();
}
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
