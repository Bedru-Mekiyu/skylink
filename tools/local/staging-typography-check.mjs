// LOCAL USE ONLY. Typography check on the STAGING site (bedru): every public page + main admin pages, 9 widths.
//   node tools/local/staging-typography-check.mjs [webkit]
// Per page and width: horizontal overflow, fonts used, text under 13px, layout shift (CLS), old fonts loaded.
// Screenshots at 390 / 768 / 1440 -> storage/app/staging-checks/typography/<page>/<width>.png. Login from deploy/STAGING-ACCESS.txt.
import { chromium, webkit } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const useWebkit = process.argv[2] === 'webkit';
const out = path.resolve('storage/app/staging-checks/typography' + (useWebkit ? '-webkit' : ''));
fs.mkdirSync(out, { recursive: true });
const WIDTHS = useWebkit ? [390] : [320, 375, 390, 430, 768, 1024, 1366, 1440, 1920];
const SHOT = new Set([390, 768, 1440]);

const browser = await (useWebkit ? webkit : chromium).launch();
const ctx = await browser.newContext({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined, viewport: { width: 1440, height: 900 } });
await ctx.route(/tawk\.to/i, (r) => r.abort());
const page = await ctx.newPage(); page.setDefaultTimeout(120000);
const go = async (u) => { const r = await page.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 150000 }); await page.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); return r; };

// admin login
await go('/login');
await page.fill('input[name=email]', 'admin@gmail.com'); await page.fill('input[name=password]', adminPw);
await Promise.all([page.waitForNavigation({ timeout: 120000 }), page.click('button[type=submit]')]);

// discover detail pages from the list pages
const first = async (list, re) => { await go(list); return page.evaluate((src) => { const r = new RegExp(src); const a = [...document.querySelectorAll('a[href]')].map((x) => x.getAttribute('href')).find((h) => r.test(h)); return a ? new URL(a, location.href).pathname : null; }, re.source); };
const detail = [
    ['news-detail', await first('/news', /\/news\/[a-z0-9-]+$/i)],
    ['product-detail', await first('/products', /\/products\/[a-z0-9-]+\/[a-z0-9-]+$/i)],
    ['service-detail', await first('/services', /\/services\/(detail|category)\/[a-z0-9-]+/i)],
    ['video-detail', await first('/news', /\/videos\/[a-z0-9-]+$/i)],
].filter(([, u]) => u);
const pages = [['home', '/'], ['about', '/about'], ['contact', '/contact'], ['products', '/products'], ['portfolio', '/portfolio'], ['news', '/news'], ['services', '/services'], ...detail,
    ['admin-dashboard', '/admin/dashboard'], ['admin-inbox', '/admin/inbox'], ['admin-news', '/admin/news'], ['admin-portfolio', '/admin/portfolio'], ['admin-videos', '/admin/videos'],
    ['admin-products', '/admin/products'], ['admin-services', '/admin/services'], ['admin-anniversary', '/admin/anniversary'], ['admin-hero-video', '/admin/hero-video'], ['admin-profile', '/admin/profile']];

const rows = [];
let bad = 0;
for (const [name, url] of pages) {
    for (const w of WIDTHS) {
        await page.setViewportSize({ width: w, height: 900 });
        const resp = await go(url).catch(() => null);
        await page.waitForTimeout(1800);
        const m = await page.evaluate(async () => {
            const cls = await new Promise((res) => { let s = 0; try { new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) s += e.value; }).observe({ type: 'layout-shift', buffered: true }); } catch {} setTimeout(() => res(s), 700); });
            const small = new Map(); const fam = {};
            const tw = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT); let n;
            while ((n = tw.nextNode())) {
                if (!n.textContent.trim()) continue; const el = n.parentElement;
                if (!el || /^(SCRIPT|STYLE|NOSCRIPT)$/.test(el.tagName)) continue;
                const cs = getComputedStyle(el); if (cs.display === 'none' || cs.visibility === 'hidden') continue;
                const r = el.getBoundingClientRect(); if (!r.width || !r.height) continue;
                const f = cs.fontFamily.split(',')[0].replace(/["']/g, ''); fam[f] = (fam[f] || 0) + 1;
                const fs = parseFloat(cs.fontSize);
                if (fs < 13) { const k = el.tagName + '.' + String(el.className).slice(0, 30) + '@' + fs; small.set(k, (small.get(k) || 0) + 1); }
            }
            const hasGeez = /[ሀ-፿]/.test(document.body.innerText);
            return {
                overflow: document.documentElement.scrollWidth - innerWidth, cls: Math.round(cls * 1000) / 1000, fam, small: [...small.entries()].slice(0, 6), hasGeez,
                geezFont: hasGeez ? document.fonts.check('16px "Noto Sans Ethiopic"', 'ሀ') : null,
                oldFonts: ['Bodoni Moda', 'Playfair Display', 'Belleza', 'EB Garamond', 'Manrope'].filter((f) => [...document.fonts].some((x) => x.family.replace(/["']/g, '') === f && x.status === 'loaded')),
                bodyPx: parseFloat(getComputedStyle(document.body).fontSize),
            };
        });
        if (SHOT.has(w)) { const d = path.join(out, name); fs.mkdirSync(d, { recursive: true }); await page.screenshot({ path: path.join(d, w + '.png') }).catch(() => {}); }
        const problem = m.overflow > 1 || m.small.length || m.oldFonts.length || m.cls > 0.1 || (resp && resp.status() >= 400);
        if (problem) bad++;
        rows.push({ page: name, w, status: resp?.status(), ...m });
        console.log(problem ? 'CHECK' : 'ok   ', name.padEnd(18), String(w).padStart(4), `ovf=${m.overflow} cls=${m.cls} body=${m.bodyPx}px`, m.small.length ? 'small=' + JSON.stringify(m.small) : '', m.oldFonts.length ? 'OLD=' + m.oldFonts : '', m.hasGeez ? `geez=${m.geezFont}` : '');
    }
}
await browser.close();
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(rows, null, 1));
console.log(`\n${rows.length} page/width combinations, ${bad} to review. Report: ${path.join(out, 'report.json')}`);
