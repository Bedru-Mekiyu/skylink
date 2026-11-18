// LOCAL USE ONLY. Typography QA for the public site.
//   node tools/local/capture-typography.mjs http://127.0.0.1:8080 before|after
// Full-page screenshots of every public page at 5 widths, the open phone menu, and report.json with
// the rendered font of each text role, overflow/clipping, font requests and layout shift.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const tag = process.argv[3] || 'after';
const out = path.resolve('storage/app/typography-captures', tag);
fs.mkdirSync(out, { recursive: true });

const widths = [1440, 1024, 768, 390, 320];
const browser = await chromium.launch();
const ctx = await browser.newContext();
await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
await ctx.addInitScript(() => {
    try { sessionStorage.setItem('skylink.anniv5.introSeen', '1'); } catch (e) {}
    window.__cls = 0;
    new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; })
        .observe({ type: 'layout-shift', buffered: true });
});
const page = await ctx.newPage();
const open = async (uri) => {
    const res = await page.goto(base + uri, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load', { timeout: 15000 }).catch(() => {});
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(400);
    return res;
};

// Public pages, including one real detail page of each dynamic kind.
const pages = ['/', '/about', '/services', '/services/category/software-service', '/products', '/portfolio', '/news', '/contact', '/subscribe/basic'];
for (const [list, pattern] of [['/products', '/products/'], ['/news', '/news/'], ['/services/category/software-service', '/services/detail/']]) {
    await open(list);
    const href = await page.evaluate((p) => [...document.querySelectorAll('a[href]')].map((a) => new URL(a.href).pathname)
        .find((h) => h.startsWith(p) && h.length > p.length), pattern);
    if (href) pages.push(href);
}

const roleProbe = () => {
    const first = (sel) => [...document.querySelectorAll(sel)].find((e) => e.offsetParent && e.textContent.trim());
    const font = (sel) => { const e = first(sel); if (!e) return null; const cs = getComputedStyle(e);
        return `${cs.fontFamily.split(',')[0].replace(/"/g, '')} ${cs.fontWeight} ${cs.fontSize}`; };
    return {
        h1: font('main h1, h1'), h2: font('main h2'), h3: font('main h3'), cardTitle: font('.card-title, .news-card__title, .project-card__title, .service-content h3, .service-content h4'),
        cardText: font('.card .card-text, .card-description, .service-content p, .news-card__excerpt, .project-card__text, .product-item p'),
        body: font('main p'), nav: font('.navbar .nav-link'), button: font('.btn, button'), footer: font('footer p, footer a'),
        label: font('label, .form-label'), articleBody: font('.article__body p'),
        overflowX: document.documentElement.scrollWidth - window.innerWidth,
        clipped: [...document.querySelectorAll('h1,h2,h3,h4,h5,h6,p,a,button,.btn,label')]
            .filter((e) => e.offsetParent && !e.matches('.visually-hidden, .sr-only') && getComputedStyle(e).overflow !== 'visible' && e.scrollWidth > e.clientWidth + 2 && !e.closest('[class*="track"],[class*="carousel"],[class*="marquee"]'))
            .map((e) => e.tagName + '.' + String(e.className).split(' ')[0]).slice(0, 5),
        fontCss: performance.getEntriesByType('resource').filter((r) => /fonts\.googleapis\.com\/css/.test(r.name)).length,
        fontFamiliesLoaded: [...new Set([...document.fonts].filter((f) => f.status === 'loaded').map((f) => f.family.replace(/"/g, '')))],
        cls: Math.round(window.__cls * 1000) / 1000,
    };
};

const report = { base, tag, pages: [] };
for (const w of widths) {
    await page.setViewportSize({ width: w, height: w > 800 ? 900 : 800 });
    for (const uri of pages) {
        const res = await open(uri);
        const row = { uri, width: w, status: res.status(), ...(await page.evaluate(roleProbe)) };
        report.pages.push(row);
        const name = (uri === '/' ? 'home' : uri.slice(1).replace(/\//g, '-')) + `-${w}.png`;
        await page.screenshot({ path: path.join(out, name), fullPage: true });
    }
}

// Phone menu open.
await page.setViewportSize({ width: 390, height: 800 });
await open('/');
await page.locator('.navbar-toggler').click();
await page.waitForTimeout(500);
await page.screenshot({ path: path.join(out, 'menu-open-390.png') });
report.menu = await page.evaluate(() => { const a = document.querySelector('.navbar-collapse .nav-link'); return a && getComputedStyle(a).fontFamily.split(',')[0]; });

await browser.close();
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
const bad = report.pages.filter((p) => p.status !== 200 || p.overflowX > 0 || p.clipped.length);
console.log(`${report.pages.length} page shots (${pages.length} pages x ${widths.length} widths); ${bad.length} with problems. ${out}`);
for (const p of bad) console.log('  ', p.width, p.uri, 'status', p.status, 'overflowX', p.overflowX, 'clipped', p.clipped.join(' '));
