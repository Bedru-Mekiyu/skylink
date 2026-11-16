// Layout check: visits every page at every size and zoom and reports
// sideways scroll, small text on phones, small tap targets, small form text
// (iOS zoom), stretched images and headings pushed below the first screen on
// short landscape phones. Takes full-page screenshots at 360/768/1024/1440.
// LOCAL USE ONLY. Needs the site running (start-site.bat).
//
//   node tools/local/check-layout.mjs [--label=before] [--quick] [--pages=home,about]
//
// --quick: 100% zoom only and no landscape. Results:
//   footage/_review/layout/<label>/summary.md, report.json, shots/*.png
// Exit code 1 if any page scrolls sideways.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
import { BASE, PAGES, PORTRAIT, LANDSCAPE_PHONES, ZOOMS, SHOT_WIDTHS, SCROLLBAR_CSS, args, siteIsUp, stamp } from './pages.mjs';

const opt = args();
const label = opt.label || 'run-' + stamp();
const outDir = path.resolve('footage/_review/layout', label);
fs.mkdirSync(path.join(outDir, 'shots'), { recursive: true });
const pages = opt.pages ? PAGES.filter(([n]) => opt.pages.split(',').includes(n)) : PAGES;

if (!(await siteIsUp())) {
    console.error(`The site is not running at ${BASE}. Start it first with start-site.bat.`);
    process.exit(2);
}

// Runs inside the page. Returns the measurements for one view.
function measure({ touch }) {
    const de = document.documentElement, cw = de.clientWidth, vh = innerHeight;
    const sel = el => {
        let s = el.tagName.toLowerCase();
        if (el.id) return s + '#' + el.id;
        if (typeof el.className === 'string' && el.className.trim()) s += '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.');
        return s;
    };
    const visible = el => {
        const r = el.getBoundingClientRect();
        if (r.width < 1 || r.height < 1) return false;
        const s = getComputedStyle(el);
        return s.visibility !== 'hidden' && s.display !== 'none' && +s.opacity !== 0;
    };
    // Elements sticking out past the right/left edge and not clipped by a parent.
    const over = [];
    if (de.scrollWidth > cw) {
        for (const el of document.body.querySelectorAll('*')) {
            const r = el.getBoundingClientRect();
            if (r.width < 1 || r.height < 1 || (r.right <= cw + 1 && r.left >= -1)) continue;
            if (getComputedStyle(el).position === 'fixed') continue;
            let p = el.parentElement, clipped = false;
            while (p && p !== document.body) {
                if (/(hidden|clip|auto|scroll)/.test(getComputedStyle(p).overflowX)) {
                    const pr = p.getBoundingClientRect();
                    if (pr.right <= cw + 1 && pr.left >= -1) { clipped = true; break; }
                }
                p = p.parentElement;
            }
            if (!clipped) over.push(`${sel(el)} (${Math.round(r.left)}..${Math.round(r.right)})`);
            if (over.length >= 8) break;
        }
    }
    const phone = cw <= 430;
    // Body text under 16px on phones (paragraph-like elements with their own text).
    const smallText = [];
    if (phone) {
        for (const el of document.querySelectorAll('p, li, td, dd, blockquote, label, .card-text')) {
            if (!visible(el) || !el.textContent.trim()) continue;
            const fs = parseFloat(getComputedStyle(el).fontSize);
            if (fs < 16) smallText.push(`${sel(el)} ${fs}px`);
        }
    }
    // Tap targets under 44x44 on touch devices (inline links in text are exempt).
    const smallTaps = [];
    if (touch) {
        for (const el of document.querySelectorAll('a[href], button, input:not([type=hidden]), select, textarea, [role=button], summary')) {
            if (!visible(el)) continue;
            const s = getComputedStyle(el);
            if (el.tagName === 'A' && s.display === 'inline' && el.closest('p, li')?.textContent.trim().length > el.textContent.trim().length + 5) continue;
            const r = el.getBoundingClientRect();
            if (r.width < 44 || r.height < 44) smallTaps.push(`${sel(el)} ${Math.round(r.width)}x${Math.round(r.height)}`);
        }
    }
    // Form fields under 16px make iOS zoom in on focus.
    const smallInputs = [];
    if (touch) {
        for (const el of document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]), select, textarea')) {
            if (!visible(el)) continue;
            const fs = parseFloat(getComputedStyle(el).fontSize);
            if (fs < 16) smallInputs.push(`${sel(el)} ${fs}px`);
        }
    }
    // Images drawn with a different shape than the file (stretched/squashed).
    const stretched = [];
    for (const img of document.images) {
        if (!visible(img) || !img.naturalWidth) continue;
        const s = getComputedStyle(img);
        if (s.objectFit !== 'fill') continue;
        const r = img.getBoundingClientRect();
        const a = img.naturalWidth / img.naturalHeight, b = r.width / r.height;
        if (Math.abs(a - b) / a > 0.04) stretched.push(`${(img.getAttribute('src') || '').split('/').pop().slice(0, 50)} ${Math.round(r.width)}x${Math.round(r.height)} (file ${img.naturalWidth}x${img.naturalHeight})`);
    }
    const h1 = document.querySelector('h1');
    const h1Bottom = h1 && visible(h1) ? Math.round(h1.getBoundingClientRect().bottom + scrollY) : null;
    return {
        cw, vh, scrollWidth: de.scrollWidth, overflowX: de.scrollWidth > cw, over,
        smallText: smallText.length, smallTextSamples: [...new Set(smallText)].slice(0, 5),
        smallTaps: smallTaps.length, smallTapSamples: [...new Set(smallTaps)].slice(0, 6),
        smallInputs: smallInputs.length, smallInputSamples: [...new Set(smallInputs)].slice(0, 4),
        stretched, h1BelowFold: h1Bottom !== null && h1Bottom > vh, height: de.scrollHeight,
    };
}

const views = [];
for (const z of (opt.quick ? [1] : ZOOMS)) for (const [w, h] of PORTRAIT) views.push({ w, h, z, orient: w > h && w <= 932 ? 'landscape' : 'portrait' });
if (!opt.quick) for (const [w, h] of LANDSCAPE_PHONES) views.push({ w, h, z: 1, orient: 'landscape' });

const browser = await chromium.launch();
const report = [];
const started = Date.now();
// One browser context per zoom level and device type, reused across sizes so
// files come from the cache. The Tawk chat is blocked here (it is a fixed
// bubble and cannot cause sideways scroll); the state shots below include it.
const contexts = new Map();
async function contextFor(z, touch) {
    const key = z + (touch ? 't' : 'd');
    if (!contexts.has(key)) {
        const ctx = await browser.newContext({ viewport: { width: 800, height: 600 }, deviceScaleFactor: z, isMobile: touch, hasTouch: touch });
        await ctx.route(/tawk\.to/, r => r.abort());
        if (!touch) await ctx.addInitScript(css => { document.addEventListener('DOMContentLoaded', () => { const s = document.createElement('style'); s.textContent = css; document.head.appendChild(s); }); }, SCROLLBAR_CSS);
        contexts.set(key, { ctx, page: await ctx.newPage() });
    }
    return contexts.get(key).page;
}
for (const v of views) {
    const touch = v.orient === 'landscape' ? true : v.w <= 820;
    const page = await contextFor(v.z, touch);
    await page.setViewportSize({ width: Math.round(v.w / v.z), height: Math.round(v.h / v.z) });
    for (const [name, url] of pages) {
        let status = 0;
        try {
            const res = await page.goto(BASE + url, { waitUntil: 'load', timeout: 60000 });
            status = res.status();
        } catch (e) { status = 'timeout'; }
        await page.waitForTimeout(250);
        const m = await page.evaluate(measure, { touch }).catch(e => ({ error: e.message }));
        const view = `${v.w}x${v.h}@${Math.round(v.z * 100)}%${v.orient === 'landscape' ? ' landscape' : ''}`;
        report.push({ page: name, url, view, status, ...m });
        if (v.z === 1 && v.orient === 'portrait' && SHOT_WIDTHS.includes(v.w)) {
            await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += innerHeight) { scrollTo(0, y); await new Promise(r => setTimeout(r, 40)); } scrollTo(0, 0); });
            await page.waitForTimeout(300);
            await page.screenshot({ path: path.join(outDir, 'shots', `${name}-${v.w}.png`), fullPage: true, animations: 'disabled' });
        }
    }
    process.stdout.write('.');
}
for (const { ctx } of contexts.values()) await ctx.close();

// Interactive states: phone menu, contact launcher, chat bubble position.
for (const [w, h, touch] of [[360, 740, true], [1440, 900, false]]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, isMobile: touch, hasTouch: touch });
    const page = await ctx.newPage();
    await page.goto(BASE + '/', { waitUntil: 'load', timeout: 60000 });
    await page.waitForTimeout(6000); // let the chat bubble appear
    await page.screenshot({ path: path.join(outDir, 'shots', `state-bubbles-${w}.png`) });
    const launcher = page.locator('.sk-contact__launcher');
    if (await launcher.count()) {
        await launcher.first().click();
        await page.waitForTimeout(500);
        await page.screenshot({ path: path.join(outDir, 'shots', `state-launcher-open-${w}.png`) });
        await page.keyboard.press('Escape');
    }
    if (touch) {
        await page.locator('.navbar-toggler').first().tap().catch(() => {});
        await page.waitForTimeout(600);
        await page.screenshot({ path: path.join(outDir, 'shots', `state-phone-menu-${w}.png`) });
    }
    await ctx.close();
}
await browser.close();

// Summary per page.
const lines = [`# Layout check: ${label}`, '', `${report.length} views, ${pages.length} pages, ${Math.round((Date.now() - started) / 1000)} s. Site: ${BASE}`, '',
    '| Page | Sideways scroll (views) | Phone body text < 16px (max) | Tap targets < 44px (max) | Form text < 16px (max) | Stretched images | h1 below first screen (landscape) |',
    '|---|---|---|---|---|---|---|'];
let anyOverflow = false;
for (const [name] of pages) {
    const r = report.filter(x => x.page === name);
    const of = r.filter(x => x.overflowX);
    if (of.length) anyOverflow = true;
    const max = k => Math.max(0, ...r.map(x => x[k] || 0));
    const st = new Set(r.flatMap(x => x.stretched || []));
    const fold = r.filter(x => x.h1BelowFold && x.view.includes('landscape'));
    const errs = r.filter(x => x.status !== 200);
    lines.push(`| ${name}${errs.length ? ' ⚠ ' + errs.length + ' load errors' : ''} | ${of.length ? of.length + ': ' + of.slice(0, 3).map(x => x.view).join(', ') + (of.length > 3 ? '…' : '') : 'none'} | ${max('smallText')} | ${max('smallTaps')} | ${max('smallInputs')} | ${st.size} | ${fold.length ? fold.map(x => x.view).join(', ') : 'none'} |`);
}
lines.push('', 'Details (offending elements, samples): report.json. Screenshots: shots/.');
fs.writeFileSync(path.join(outDir, 'report.json'), JSON.stringify(report, null, 1));
fs.writeFileSync(path.join(outDir, 'summary.md'), lines.join('\n') + '\n');
console.log('\n' + lines.join('\n'));
console.log(`\nSaved in ${outDir}`);
process.exit(anyOverflow ? 1 : 0);
