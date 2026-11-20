// LOCAL USE ONLY. Responsive audit of the password-protected bedru test site (login from deploy/STAGING-ACCESS.txt, never printed).
//   node tools/local/responsive-audit.mjs [public|admin|both] [--browser chromium|chrome|msedge|firefox|webkit] [--widths all|key] [--shots <dir>] [--only <substring>]
// Per page and viewport it measures: sideways scroll, elements sticking out of the screen, text under 16px on phones,
// tap targets under 44px on touch widths, stretched/upscaled images. It also takes a screenshot per page x width.
// Output: storage/app/staging-checks/<shots dir>/<page>/<width>.png + report-responsive-<browser>.json
import { chromium, firefox, webkit } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const args = process.argv.slice(2);
const opt = (n, d) => { const i = args.indexOf('--' + n); return i >= 0 ? args[i + 1] : d; };
const scope = args.find((a) => ['public', 'admin', 'both'].includes(a)) || 'both';
const browserName = opt('browser', 'chromium');
const widthSet = opt('widths', 'all');
const shotsDir = opt('shots', 'final');
const only = opt('only', '');
const base = opt('base', 'https://bedru.skylinkict.com').replace(/\/$/, '');
const remote = /bedru\.skylinkict\.com/.test(base);
const access = remote ? fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8') : '';
const basicPw = remote ? access.match(/user\s+staging\s+password\s+(\S+)/)[1] : '';
const adminEmail = remote ? 'staging-admin@skylinkict.test' : process.env.ADMIN_EMAIL;
const adminPw = remote ? access.match(/email\s+staging-admin@skylinkict\.test\s+password\s+(\S+)/)[1] : process.env.ADMIN_PASSWORD;
const out = path.resolve('storage/app/staging-checks', shotsDir);
fs.mkdirSync(out, { recursive: true });

const ALL = [
    [320, 568, 'phone'], [360, 740, 'phone'], [375, 667, 'phone'], [390, 844, 'phone'], [414, 896, 'phone'], [430, 932, 'phone'],
    [768, 1024, 'tablet'], [820, 1180, 'tablet'], [834, 1194, 'tablet'],
    [1024, 768, 'laptop'], [1280, 720, 'laptop'], [1366, 768, 'laptop'],
    [1440, 900, 'desktop'], [1920, 1080, 'desktop'],
    [667, 375, 'phone-landscape'], [932, 430, 'phone-landscape'],
];
const KEY = [[390, 844, 'phone'], [768, 1024, 'tablet'], [1440, 900, 'desktop']];
const viewports = (widthSet === 'key' ? KEY : ALL).map(([w, h, kind]) => ({ w, h, kind, name: kind === 'phone-landscape' ? `${w}x${h}` : String(w) }));

const launcher = { chromium, chrome: chromium, msedge: chromium, firefox, webkit }[browserName];
const browser = await launcher.launch(['chrome', 'msedge'].includes(browserName) ? { channel: browserName } : {});
const ctx = await browser.newContext({ httpCredentials: remote ? { username: 'staging', password: basicPw } : undefined, viewport: { width: 1440, height: 900 } });
await ctx.route(/tawk\.to/, (r) => r.abort());
const page = await ctx.newPage(); page.setDefaultTimeout(120000);

const nav = async (u) => { for (let a = 0; a < 4; a++) { try { const r = await page.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 120000 }); await page.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); return r; } catch (e) { if (a === 3) throw e; await page.waitForTimeout(15000 * (a + 1)); } } };
const measure = (vw, touch) => page.evaluate(({ vw, touch }) => {
    const one = (el) => { let s = el.tagName.toLowerCase(); if (el.id) s += '#' + el.id; else if (el.className && typeof el.className === 'string' && el.className.trim()) s += '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.'); return s; };
    const sel = (el) => { let s = one(el); let p = el.parentElement, d = 0; while (p && d < 2 && p !== document.body && (!p.className || typeof p.className !== 'string' || !p.className.trim()) && !p.id) { p = p.parentElement; d++; } if (p && p !== document.body && p !== el.parentElement) s = one(p) + ' > ... > ' + s; else if (el.parentElement && el.parentElement !== document.body) s = one(el.parentElement) + ' > ' + s; return s; };
    const visible = (el) => { const cs = getComputedStyle(el); if (cs.display === 'none' || cs.visibility === 'hidden' || +cs.opacity === 0) return null; const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 ? r : null; };
    const inScroller = (el) => { let p = el.parentElement; while (p && p !== document.body) { const s = getComputedStyle(p); if (/(auto|scroll|hidden|clip)/.test(s.overflowX) || s.position === 'fixed') return true; p = p.parentElement; } return false; };
    const doc = document.documentElement;
    const overflow = Math.max(doc.scrollWidth, document.body.scrollWidth) - window.innerWidth;
    const sticking = [];
    for (const el of document.querySelectorAll('body *')) { const r = visible(el); if (!r) continue; if (r.right < -5000 || r.left < -5000) continue; /* the invisible spam-trap field is parked at -10000px on purpose */ if ((r.right > window.innerWidth + 1 || r.left < -1) && !inScroller(el)) sticking.push(`${sel(el)} (${Math.round(r.left)}..${Math.round(r.right)})`); }
    const small = [];
    if (vw <= 430) for (const el of document.querySelectorAll('p, li, td, dd, label, blockquote')) { const r = visible(el); if (!r) continue; const own = [...el.childNodes].filter((n) => n.nodeType === 3).map((n) => n.textContent).join('').trim(); if (own.length < 40) continue; const fs = parseFloat(getComputedStyle(el).fontSize); if (fs < 15.5) small.push(`${sel(el)} ${fs}px`); }
    const taps = [];
    if (touch) for (const el of document.querySelectorAll('a[href], button, input:not([type=hidden]), select, textarea, [role=button]')) { let r = visible(el); if (!r) continue; if (['checkbox', 'radio'].includes(el.type)) { const lab = el.closest('label, .form-check, td.sky-tap, th.sky-tap'); const lr = lab && visible(lab); if (lr && lr.height >= 44) continue; } if (el.closest('.visually-hidden, .skip-link, [aria-hidden="true"]') || el.classList.contains('visually-hidden') || el.tabIndex < 0) continue; if (el.tagName === 'A' && getComputedStyle(el).display === 'inline' && el.closest('p, li, td, dd, blockquote, span, small, .small, .breadcrumb, footer')) continue; if (r.width < 44 || r.height < 44) taps.push(`${sel(el)} ${Math.round(r.width)}x${Math.round(r.height)}`); }
    const imgs = [];
    for (const im of document.querySelectorAll('img')) { const r = visible(im); if (!r || !im.naturalWidth || r.width < 40) continue; const fit = getComputedStyle(im).objectFit; const ratio = (r.width / r.height) / (im.naturalWidth / im.naturalHeight); if (fit === 'fill' && Math.abs(ratio - 1) > 0.08) imgs.push(`stretched ${sel(im)} ${(ratio).toFixed(2)}`); else if (r.width > im.naturalWidth * 1.25 + 4 && im.naturalWidth > 60) imgs.push(`upscaled ${sel(im)} ${Math.round(r.width)}px from ${im.naturalWidth}px`); }
    return { overflow, sticking: [...new Set(sticking)], small: [...new Set(small)], taps: [...new Set(taps)], imgs: [...new Set(imgs)] };
}, { vw, touch });

const report = [];
async function audit(name, url) {
    if (only && !name.includes(only)) return;
    const r = await nav(url);
    if (!r || r.status() >= 500) { report.push({ page: name, url, error: 'status ' + r?.status() }); console.log('FAIL', name, 'status', r?.status()); return; }
    await page.waitForTimeout(1500);
    const dir = path.join(out, name); fs.mkdirSync(dir, { recursive: true });
    for (const v of viewports) {
        await page.setViewportSize({ width: v.w, height: v.h });
        await page.waitForTimeout(600);
        const m = await measure(v.w, v.w <= 1024 || v.kind === 'phone-landscape');
        await page.screenshot({ path: path.join(dir, v.name + '.png') }).catch(() => {});
        const issues = [];
        if (m.overflow > 1) issues.push(`sideways scroll +${m.overflow}px`);
        if (m.sticking.length) issues.push(`sticks out: ${m.sticking.slice(0, 3).join('; ')}${m.sticking.length > 3 ? ` (+${m.sticking.length - 3})` : ''}`);
        if (m.small.length) issues.push(`text <16px: ${m.small.slice(0, 2).join('; ')}${m.small.length > 2 ? ` (+${m.small.length - 2})` : ''}`);
        if (m.taps.length) issues.push(`small tap targets (${m.taps.length}): ${m.taps.slice(0, 3).join('; ')}`);
        if (m.imgs.length) issues.push(`images: ${m.imgs.slice(0, 2).join('; ')}`);
        report.push({ page: name, url, width: v.name, ...m, issues });
        console.log(issues.length ? 'ISSUE' : 'ok   ', name.padEnd(22), v.name.padStart(8), issues.join(' | ').slice(0, 260));
    }
    await page.setViewportSize({ width: 1440, height: 900 });
}

if (scope === 'public' || scope === 'both') {
    await nav('/');
    const link = (re) => page.evaluate((src) => { const r = new RegExp(src); return [...document.querySelectorAll('a[href]')].map((a) => a.getAttribute('href')).find((h) => r.test(h)) || null; }, re.source);
    const rel = (h) => (h ? h.replace(base, '') : null);
    await nav('/services'); const svcCat = rel(await link(/\/services\/category\//)); const svcDet = rel(await link(/\/services\/detail\//));
    await nav('/products'); const prodDet = rel(await link(/\/products\/[^/]+\/[^/]+/));
    await nav('/news'); const art = rel(await link(/\/news\/[^/?#]+$/)); const vid = rel(await link(/\/videos\/[^/?#]+$/));
    const pages = [['home', '/'], ['about', '/about'], ['services', '/services'], ['service-category', svcCat], ['service-detail', svcDet], ['products', '/products'], ['product-detail', prodDet], ['portfolio', '/portfolio'], ['news', '/news'], ['news-article', art], ['video-detail', vid], ['contact', '/contact'], ['login', '/login'], ['not-found-404', '/no-such-page-xyz']].filter(([, u]) => u);
    for (const [n, u] of pages) await audit('public-' + n, u);
}
if (scope === 'admin' || scope === 'both') {
    await nav('/login');
    await page.fill('input[name=email]', adminEmail); await page.fill('input[name=password]', adminPw);
    await Promise.all([page.waitForNavigation({ timeout: 120000 }).catch(() => {}), page.click('button[type=submit]')]);
    await nav('/admin/inbox');
    const first = await page.evaluate(() => document.querySelector('tbody tr a[href*="/admin/inbox/"]')?.getAttribute('href'));
    const adminPages = [['dashboard', '/admin/dashboard'], ['inbox', '/admin/inbox'], ['inbox-message', first ? first.replace(base, '') : null], ['news-list', '/admin/news'], ['news-create', '/admin/news/create'], ['services', '/admin/services'], ['service-categories', '/admin/service-categories'], ['portfolio', '/admin/portfolio'], ['videos', '/admin/videos'], ['video-create', '/admin/videos/create'], ['products', '/admin/products'], ['anniversary', '/admin/anniversary'], ['profile', '/admin/profile'], ['subscribers', '/admin/subscribers'], ['contact-messages', '/admin/contact-messages']].filter(([, u]) => u);
    for (const [n, u] of adminPages) await audit('admin-' + n, u);
}

fs.writeFileSync(path.join(out, `report-responsive-${browserName}.json`), JSON.stringify(report, null, 2));
await browser.close();
const bad = report.filter((r) => r.error || (r.issues && r.issues.length));
const hard = report.filter((r) => r.error || (r.issues || []).some((i) => /sideways|sticks out/.test(i)));
console.log(`\n${report.length} checks, ${bad.length} with findings, ${hard.length} with sideways scroll / overflow`);
