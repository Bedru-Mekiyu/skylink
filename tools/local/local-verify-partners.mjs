// LOCAL USE ONLY. Homepage "Trusted Partners" with 1, 3, 12, 27 (as imported) and 40 logos on the local site.
//   node tools/local/local-verify-partners.mjs
import { chromium } from 'playwright-core';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const php = process.env.PHP || 'C:/Users/AFRO-EAGLE/tools/php85/php.exe';
const out = path.resolve('storage/app/staging-checks/partners');
fs.mkdirSync(out, { recursive: true });
const tinker = (n) => spawnSync(php, ['artisan', 'tinker', "--execute=require 'tools/local/partners-set-n.php';"], { encoding: 'utf8', env: { ...process.env, PARTNERS_N: String(n) } });
const results = []; const rec = (n, p, note = '') => { results.push({ n, p, note }); console.log(p ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };

// make exactly N visible partners (extra rows are tagged "ZZ extra" and removed at the end)
const setN = (N) => tinker(N);

const browser = await chromium.launch();
for (const N of [1, 3, 12, 27, 40]) {
    setN(N);
    for (const [w, h] of [[390, 844], [1440, 900]]) {
        const c = await browser.newContext({ viewport: { width: w, height: h }, reducedMotion: 'no-preference' }); const p = await c.newPage();
        await p.goto(base + '/', { waitUntil: 'load' }); await p.locator('.partners-section').scrollIntoViewIfNeeded(); await p.waitForTimeout(1500);
        const m = await p.evaluate(() => {
            const imgs = [...document.querySelectorAll('.partners-track .partner-logo')];
            const tr = document.querySelector('.partners-track'), sl = document.querySelector('.partners-slider');
            return { n: imgs.length, loop: tr.classList.contains('is-loop'), anim: getComputedStyle(tr).animationName, dur: getComputedStyle(tr).animationDuration, h: [...new Set(imgs.map((i) => Math.round(i.getBoundingClientRect().height)))], maxW: Math.round(Math.max(...imgs.map((i) => i.getBoundingClientRect().width))), sized: imgs.every((i) => i.getAttribute('width') && i.getAttribute('height')) || imgs.every((i) => !i.getAttribute('width')), ovf: document.documentElement.scrollWidth - innerWidth, still: sl.classList.contains('partners-slider--still'), sectionH: Math.round(document.querySelector('.partners-section').getBoundingClientRect().height) };
        });
        const expectLoop = N > 6;
        rec(`${N} logos @${w}: ${expectLoop ? 'seamless loop (set repeated once)' : 'still centred row'}, 90px boxes, no sideways scroll`, m.n === (expectLoop ? N * 2 : N) && m.loop === expectLoop && m.still === !expectLoop && m.h.length === 1 && m.h[0] === 90 && m.ovf <= 0 && (!expectLoop || m.anim === 'partnersLoop'), JSON.stringify({ imgs: m.n, dur: m.dur, maxLogoW: m.maxW, sectionH: m.sectionH }));
        if (w === 1440) await p.locator('.partners-section').screenshot({ path: path.join(out, `home-marquee-${N}-logos.png`) });
        await c.close();
    }
}
// reduced motion with 27
setN(27);
{ const c = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' }); const p = await c.newPage(); await p.goto(base + '/', { waitUntil: 'load' }); await p.locator('.partners-section').scrollIntoViewIfNeeded();
  const m = await p.evaluate(() => ({ anim: getComputedStyle(document.querySelector('.partners-track')).animationName, wrap: getComputedStyle(document.querySelector('.partners-track')).flexWrap, copies: [...document.querySelectorAll('[data-partners-copy]')].every((e) => getComputedStyle(e).display === 'none') }));
  rec('27 logos, reduced motion: static wrapping grid, repeated copy hidden', m.anim === 'none' && m.wrap === 'wrap' && m.copies, JSON.stringify(m)); await p.locator('.partners-section').screenshot({ path: path.join(out, 'home-marquee-27-reduced-motion.png') }); await c.close(); }
// restore: the 27 imported logos active, extras removed
tinker('restore');
await browser.close();
fs.writeFileSync(path.join(out, 'local-report.json'), JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.p); console.log(`\n${results.length - failed.length}/${results.length} passed`); process.exit(failed.length ? 1 : 0);
