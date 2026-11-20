// LOCAL USE ONLY (never against bedru). Gallery behaviour in a real browser on the local site (http://127.0.0.1:8000).
//   node tools/local/local-verify-gallery.mjs            (needs >= 3 visible gallery photos; seed with .seedgal.php)
// Checks: autoplay, hover/focus/pause, buttons, swipe, keyboard, lightbox (dialog, focus trap, Esc, outside click, focus return),
// reduced motion + Save-Data = static grid, 4x CPU-throttled frame rate, no sideways scroll, no overlap with fixed widgets.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const out = path.resolve('storage/app/staging-checks/about-gallery');
fs.mkdirSync(out, { recursive: true });
const results = [];
const rec = (name, pass, note = '') => { results.push({ name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', name, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const newPage = async (opts = {}) => { const c = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'no-preference', ...opts }); const p = await c.newPage(); return [c, p]; };
const caption = (p) => p.locator('#gallery [data-caption]').first().innerText();
const toGallery = async (p) => { await p.goto(base + '/about', { waitUntil: 'load' }); await p.locator('#gallery').scrollIntoViewIfNeeded(); await p.waitForTimeout(1500); };

// ---- cinematic, autoplay, pause rules ----
let [c, p] = await newPage(); await toGallery(p);
rec('cinematic mode on (3+ photos, motion allowed)', await p.evaluate(() => document.querySelector('#gallery').classList.contains('is-cinematic')));
const c0 = await caption(p); await p.waitForTimeout(6500); const c1 = await caption(p);
rec('auto-advances by itself', c0 !== c1, `${c0.slice(0, 28)} -> ${c1.slice(0, 28)}`);
await p.hover('#gallery .sk-gallery__stage'); const h0 = await caption(p); await p.waitForTimeout(6500);
rec('pauses on hover', h0 === await caption(p));
await p.mouse.move(5, 5); await p.waitForTimeout(300);
await p.click('#gallery [data-act="pause"]'); await p.mouse.move(5, 5); const u0 = await caption(p); await p.waitForTimeout(6500);
rec('pause button stops it (aria-pressed true)', u0 === await caption(p) && (await p.getAttribute('#gallery [data-act="pause"]', 'aria-pressed')) === 'true');
await p.click('#gallery [data-act="next"]'); await p.waitForTimeout(400);
rec('next button moves', (await caption(p)) !== u0);
await p.click('#gallery [data-act="prev"]'); await p.waitForTimeout(400);
rec('prev button moves back', (await caption(p)) === u0);
// swipe with the mouse
const box = await p.locator('#gallery .sk-gallery__stage').boundingBox();
await p.mouse.move(box.x + box.width * 0.7, box.y + box.height / 2); await p.mouse.down(); await p.mouse.move(box.x + box.width * 0.3, box.y + box.height / 2, { steps: 8 }); await p.mouse.up(); await p.waitForTimeout(500);
rec('swipe left goes to the next photo', (await caption(p)) !== u0 && !(await p.locator('.sk-lb:not([hidden])').count()), 'and a swipe does not open the lightbox');
// keyboard
await p.focus('#gallery .sk-gallery__slide:not(.is-far) .sk-gallery__open[tabindex="0"]'); const k0 = await caption(p); await p.keyboard.press('ArrowRight'); await p.waitForTimeout(400);
rec('ArrowRight on the focused gallery moves on', (await caption(p)) !== k0);

// ---- lightbox ----
await p.waitForTimeout(1300); // let the slide settle before clicking it
const opener = p.locator('#gallery .sk-gallery__open[tabindex="0"]').first();
await opener.focus(); const reopen = async () => { await p.waitForTimeout(1300); await opener.click({ force: true }); await p.waitForSelector('.sk-lb:not([hidden])'); };
const capBefore = await caption(p); await opener.click({ force: true }); await p.waitForSelector('.sk-lb:not([hidden])');
const dlg = await p.evaluate(() => { const d = document.querySelector('.sk-lb'); return { role: d.getAttribute('role'), modal: d.getAttribute('aria-modal'), focus: document.activeElement.className, img: d.querySelector('img').src, cap: d.querySelector('.sk-lb__cap').innerText.replace(/\s+/g, ' '), body: getComputedStyle(document.body).overflow }; });
rec('lightbox is a modal dialog, focus on Close, page scroll locked', dlg.role === 'dialog' && dlg.modal === 'true' && /sk-lb__close/.test(dlg.focus) && dlg.body === 'hidden', JSON.stringify({ focus: dlg.focus }));
rec('lightbox shows the large image (display size), caption and counter', /-display\.webp/.test(dlg.img) && /\d+ \/ \d+/.test(dlg.cap), dlg.cap.slice(0, 70));
await p.screenshot({ path: path.join(out, 'lightbox-local.png') });
const f = [];
for (let i = 0; i < 4; i++) { await p.keyboard.press('Tab'); f.push(await p.evaluate(() => document.activeElement.className.split(' ').pop())); }
rec('Tab stays inside the lightbox (focus trap)', f.every((x) => /sk-lb__(close|prev|next)/.test(x)), f.join(','));
const n0 = await p.locator('.sk-lb__cap').innerText(); await p.keyboard.press('ArrowRight'); await p.waitForTimeout(200);
rec('lightbox arrow key shows the next photo', (await p.locator('.sk-lb__cap').innerText()) !== n0);
await p.keyboard.press('Escape'); await p.waitForTimeout(200);
rec('Esc closes and focus returns to the gallery', (await p.locator('.sk-lb').getAttribute('hidden')) !== null && await p.evaluate(() => !!document.activeElement.closest('#gallery')), await p.evaluate(() => document.activeElement.className));
await reopen(); await p.mouse.click(30, 150); await p.waitForTimeout(200);
rec('click outside the photo closes it', (await p.locator('.sk-lb').getAttribute('hidden')) !== null);
await reopen(); await p.click('.sk-lb__close'); await p.waitForTimeout(200);
rec('X closes it', (await p.locator('.sk-lb').getAttribute('hidden')) !== null);
// swipe inside lightbox
await reopen(); const l0 = await p.locator('.sk-lb__cap').innerText();
await p.mouse.move(900, 450); await p.mouse.down(); await p.mouse.move(300, 450, { steps: 6 }); await p.mouse.up(); await p.waitForTimeout(200);
rec('swipe inside the lightbox changes the photo', (await p.locator('.sk-lb__cap').innerText()) !== l0);
await p.keyboard.press('Escape');
await c.close();

// ---- reduced motion and Save-Data: static grid, no rotation ----
[c, p] = await newPage({ reducedMotion: 'reduce' }); await toGallery(p);
const rm = await p.evaluate(() => ({ cin: document.querySelector('#gallery').classList.contains('is-cinematic'), cols: getComputedStyle(document.querySelector('.sk-gallery__track')).display, bar: getComputedStyle(document.querySelector('.sk-gallery__bar')).display }));
const r0 = await p.evaluate(() => document.querySelector('.sk-gallery__track').getBoundingClientRect().height); await p.waitForTimeout(6000);
rec('reduced motion: static grid, no controls, no rotation', !rm.cin && rm.cols === 'grid' && rm.bar === 'none' && r0 === await p.evaluate(() => document.querySelector('.sk-gallery__track').getBoundingClientRect().height));
await p.screenshot({ path: path.join(out, 'gallery-local-reduced-motion.png') });
await p.locator('#gallery .sk-gallery__open').first().click(); await p.waitForSelector('.sk-lb:not([hidden])');
rec('reduced-motion grid still opens the lightbox', true); await c.close();
[c, p] = await newPage();
await p.addInitScript(() => { Object.defineProperty(navigator, 'connection', { value: { saveData: true, effectiveType: '4g' }, configurable: true }); });
await toGallery(p);
rec('Save-Data: static grid, no auto-rotation', !(await p.evaluate(() => document.querySelector('#gallery').classList.contains('is-cinematic')))); await c.close();

// ---- frame rate with 4x CPU slowdown, layout stability ----
[c, p] = await newPage(); const cdp = await c.newCDPSession(p);
await p.goto(base + '/about', { waitUntil: 'load' }); await p.locator('#gallery').scrollIntoViewIfNeeded(); await p.waitForTimeout(1200);
await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
const fps = await p.evaluate(() => new Promise((res) => { const t = []; let last = performance.now(); const end = last + 7000; const tick = (now) => { t.push(now - last); last = now; now < end ? requestAnimationFrame(tick) : res(t); }; requestAnimationFrame(tick); }));
const avg = fps.reduce((a, b) => a + b, 0) / fps.length, sorted = [...fps].sort((a, b) => a - b), p95 = sorted[Math.floor(sorted.length * 0.95)];
rec('4x CPU slowdown: frame rate stays smooth during rotation', 1000 / avg >= 30 && p95 < 80, `avg ${(1000 / avg).toFixed(0)} fps, 95th percentile frame ${p95.toFixed(0)} ms, ${fps.length} frames`);
await cdp.send('Emulation.setCPUThrottlingRate', { rate: 1 });
const cls = await p.evaluate(() => new Promise((res) => { let s = 0; new PerformanceObserver((l) => l.getEntries().forEach((e) => { if (!e.hadRecentInput) s += e.value; })).observe({ type: 'layout-shift', buffered: true }); setTimeout(() => res(s), 500); }));
rec('no layout shift from the gallery', cls < 0.05, 'CLS ' + cls.toFixed(3));
await c.close();

// ---- widths, scaling, overlap with fixed widgets ----
[c, p] = await newPage();
for (const [w, h, dsf, label] of [[320, 640, 1, '320'], [360, 740, 1, '360'], [390, 844, 1, '390'], [768, 1024, 1, '768'], [1024, 768, 1, '1024'], [1440, 900, 1, '1440'], [1920, 1080, 1, '1920'], [1536, 864, 1.25, '125% scaling (1920 screen)'], [1280, 720, 1.5, '150% scaling (1920 screen)']]) {
    await p.setViewportSize({ width: w, height: h });
    const ctx2 = p.context(); await toGallery(p); await p.setViewportSize({ width: w, height: h });
    await p.waitForTimeout(600);
    const m = await p.evaluate(() => {
        const g = document.querySelector('#gallery'), r = g.getBoundingClientRect();
        const rect = (e) => { const b = e.getBoundingClientRect(); return { l: b.left, t: b.top, r: b.right, b: b.bottom }; };
        const hit = (a, b) => a.l < b.r && a.r > b.l && a.t < b.b && a.b > b.t;
        const fixed = [...document.querySelectorAll('body *')].filter((e) => { const cs = getComputedStyle(e); return cs.position === 'fixed' && cs.display !== 'none' && cs.visibility !== 'hidden' && parseFloat(cs.opacity) > 0 && e.getBoundingClientRect().width > 0 && !e.closest('.sk-lb'); });
        const btns = [...g.querySelectorAll('.sk-gallery__btn')].map(rect);
        const bad = []; fixed.forEach((f) => { const fr = rect(f); if (btns.some((b) => hit(b, fr))) bad.push(f.className || f.tagName); });
        return { ovf: document.documentElement.scrollWidth - innerWidth, cin: g.classList.contains('is-cinematic'), stage: Math.round(g.querySelector('.sk-gallery__stage').getBoundingClientRect().height), overlap: bad };
    });
    rec(`gallery @${label} (${w}x${h}): no sideways scroll, no overlap with navbar/launcher/chat/badge`, m.ovf <= 0 && m.overlap.length === 0, JSON.stringify({ ovf: m.ovf, stage: m.stage, overlap: m.overlap }));
    await p.locator('#gallery').screenshot({ path: path.join(out, `gallery-local-${w}x${h}${dsf === 1 ? '' : '-x' + dsf}.png`) });
}
await c.close(); await browser.close();
fs.writeFileSync(path.join(out, 'gallery-local-report.json'), JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
