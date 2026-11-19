// LOCAL USE ONLY. Checks the public pages of the password-protected staging site in a real browser at 1440, 768 and 390 px.
//   node tools/local/staging-public-check.mjs        (reads the staging login from deploy/STAGING-ACCESS.txt, never prints it)
// Per page and width: HTTP status, console errors, failed requests, horizontal scroll, fonts, screenshot
// -> storage/app/staging-checks/<page>/<width>.png and storage/app/staging-checks/report-public.json
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const pw = access.match(/user\s+staging\s+password\s+(\S+)/)[1];
const base = 'https://staging.skylinkict.com';
const out = path.resolve('storage/app/staging-checks');
const widths = [1440, 768, 390];
const report = [];

const browser = await chromium.launch();
const ctx = await browser.newContext({ httpCredentials: { username: 'staging', password: pw }, viewport: { width: 1440, height: 900 } });
await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort()); // chat widget + the big hero videos are not what is being tested
const page = await ctx.newPage();
page.setDefaultTimeout(150000);
let problems = [];
page.on('console', (m) => { if (m.type() === 'error' && !/tawk|ERR_BLOCKED|net::ERR_FAILED/.test(m.text())) problems.push('console: ' + m.text().slice(0, 160)); });
page.on('pageerror', (e) => problems.push('js: ' + e.message.slice(0, 160)));
page.on('response', (r) => { if (r.status() >= 400 && !/tawk|favicon/.test(r.url())) problems.push(`http ${r.status()}: ${r.url().replace(base, '')}`); });

const firstLink = async (from, re) => {
    await page.goto(base + from, { waitUntil: 'domcontentloaded' });
    return page.evaluate((src) => { const r = new RegExp(src); return [...document.querySelectorAll('a[href]')].map((a) => a.getAttribute('href')).find((h) => r.test(h)) || null; }, re.source);
};
const norm = (h) => (h ? h.replace(base, '') : null);

const targets = [['home', '/'], ['about', '/about'], ['services', '/services'], ['products', '/products'], ['portfolio', '/portfolio'], ['news', '/news'], ['contact', '/contact'], ['login', '/login']];
const svc = norm(await firstLink('/services', /\/services\/detail\//));
const cat = norm(await firstLink('/services', /\/services\/category\//));
const prod = norm(await firstLink('/products', /\/products\/[^/]+\/[^/]+/));
const art = norm(await firstLink('/news', /\/news\/[^/?#]+$/));
const vid = norm(await firstLink('/news', /\/videos\/[^/?#]+$/));
for (const [n, u] of [['service-detail', svc], ['service-category', cat], ['product-detail', prod], ['news-article', art], ['video-detail', vid]]) if (u) targets.push([n, u]);
// an Amharic article, if the news list has one (Ethiopic title)
const amLink = await page.evaluate(() => { const a = [...document.querySelectorAll('a[href*="/news/"]')].find((x) => /[ሀ-፿]/.test(x.textContent)); return a ? a.getAttribute('href') : null; });
await page.goto(base + '/news', { waitUntil: 'domcontentloaded' });
const am = await page.evaluate(() => { const a = [...document.querySelectorAll('a[href*="/news/"]')].find((x) => /[ሀ-፿]/.test(x.textContent)); return a ? a.getAttribute('href') : null; });
if (am) targets.push(['news-article-amharic', norm(am)]);
targets.push(['not-found-404', '/this-page-does-not-exist-xyz']);

for (const [name, url] of targets) {
    let retries = 0;
    for (let i = 0; i < widths.length; i++) {
        const w = widths[i];
        try {
        await page.setViewportSize({ width: w, height: 900 });
        problems = [];
        let r; for (let a = 0; a < 4 && !r; a++) { r = await page.goto(base + url, { waitUntil: 'domcontentloaded' }).catch(() => null); if (!r) await page.waitForTimeout(3000); } // local network blips (ERR_NETWORK_CHANGED)
        await page.evaluate(() => { try { sessionStorage.clear(); } catch (e) {} });
        await page.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); // slow shared host: best effort
        await page.waitForTimeout(2500);
        const m = await page.evaluate(async () => ({
            overflow: document.documentElement.scrollWidth - window.innerWidth,
            fonts: ['Bodoni Moda', 'Playfair Display', 'Belleza'].map((f) => [f, document.fonts.check(`16px "${f}"`)]),
            h1: document.querySelectorAll('h1').length,
            ceremony: document.documentElement.outerHTML.includes('anniv5'),
        }));
        const dir = path.join(out, name); fs.mkdirSync(dir, { recursive: true });
        await page.screenshot({ path: path.join(dir, `${w}.png`), fullPage: false });
        const row = { page: name, url, width: w, status: r.status(), overflowPx: m.overflow, ceremony: m.ceremony, fontsOk: Object.fromEntries(m.fonts), problems: [...new Set(problems)] };
        const expected = name === 'not-found-404' ? 404 : 200;
        const isExternal404 = (p) => /^http 404: https?:\/\//.test(p) && !p.includes('staging.skylinkict.com');
        row.warnings = row.problems.filter(isExternal404); // dead third-party images (the page swaps in the placeholder)
        const relevant = row.problems.filter((p) => !(name === 'not-found-404' && /404/.test(p)) && !isExternal404(p) && !(row.warnings.length && /Failed to load resource: the server responded with a status of 404/.test(p)));
        row.fontsLoaded = Object.values(row.fontsOk).every(Boolean);
        row.pass = row.status === expected && row.overflowPx <= 1 && !row.ceremony && relevant.length === 0;
        // The shared host sometimes drops a connection while many images load: one retry, flagged in the report.
        if (!row.pass && retries < 2 && relevant.some((p) => /ERR_(CONNECTION|SOCKET|HTTP2|NETWORK|INTERNET|TIMED)/.test(p))) { retries++; i--; continue; }
        row.retries = retries; retries = 0;
        report.push(row);
        console.log(row.pass ? 'ok  ' : 'FAIL', name.padEnd(22), String(w).padStart(4), 'status', row.status, 'overflow', row.overflowPx, row.retries ? '(retried ' + row.retries + 'x)' : '', row.problems.length ? row.problems.slice(0, 2).join(' | ') : '');
        } catch (e) { if (retries < 3) { retries++; i--; continue; } report.push({ page: name, url, width: w, pass: false, problems: [String(e.message).slice(0, 160)] }); console.log('FAIL', name.padEnd(22), w, String(e.message).slice(0, 120)); retries = 0; }
    }
}
fs.writeFileSync(path.join(out, 'report-public.json'), JSON.stringify(report, null, 2));
await browser.close();
const bad = report.filter((r) => !r.pass);
console.log(bad.length ? `\n${bad.length} of ${report.length} checks FAILED` : `\nALL ${report.length} PUBLIC CHECKS PASSED`);
