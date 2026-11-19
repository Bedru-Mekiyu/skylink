// LOCAL TEST ONLY. Checks the stage-2 layout served by test/router.php (fake public_html, Apache rules emulated).
//   ADMIN_EMAIL=... ADMIN_PASSWORD=... node deploy/domain-switch/test/verify-stage2.mjs 8202
import { chromium } from 'playwright-core';

const port = process.argv[2] || '8202';
const fails = [];
const ok = (c, m) => { console.log(c ? 'ok  ' : 'FAIL', m); if (!c) fails.push(m); };
const get = async (host, path, init = {}) => {
    // Node's fetch cannot set Host, so talk to 127.0.0.1 with a Host header through http.request.
    const http = await import('node:http');
    return new Promise((res, rej) => {
        const r = http.request({ host: '127.0.0.1', port, path, method: init.method || 'GET', headers: { Host: `${host}:${port}`, ...(init.headers || {}) } }, (resp) => {
            let b = ''; resp.on('data', (d) => (b += d)); resp.on('end', () => res({ status: resp.statusCode, headers: resp.headers, body: b }));
        });
        r.on('error', rej); r.end();
    });
};
const S = 'skylinkict.com';

// 1. other sites keep working, clashes do not leak
let r = await get(S, '/erp/');            ok(r.status === 200 && r.body === 'ERP-OK', 'erp/ still served (' + r.status + ')');
r = await get(S, '/erp');                 ok([200, 301].includes(r.status), '/erp (no slash) -> ' + r.status);
r = await get(S, '/webpro/');             ok(r.body === 'WEBPRO-OK', 'webpro/ still served');
r = await get(S, '/newsite/');            ok(r.body === 'NEWSITE-OK', 'newsite/index.html still served');
r = await get(S, '/privacy-policy/');     ok(r.body === 'PRIVACY-OK', 'privacy-policy/ still served');
r = await get(S, '/readymeal/sub/page.html'); ok(r.body === 'READYMEAL-OK', 'file in another site still served');
r = await get(S, '/cwpord_test.php');     ok(r.body === 'CWPORD-OK', 'loose root .php file still served');
r = await get(S, '/assets/index-DI8Yc8se.js'); ok(r.body === 'BASIRAH-ASSET', 'Basirah /assets/index-*.js untouched');
r = await get(S, '/assets/images/logo.png');   ok(r.body === 'BASIRAH-IMAGE', 'Basirah /assets/images untouched');
r = await get(S, '/robots.txt');          ok(/Disallow/.test(r.body), 'shared robots.txt untouched');
r = await get(S, '/empty-folder/');       ok(r.status === 403, 'folder without index -> 403, no listing, no Laravel (' + r.status + ')');
// 2. home page is Sky Link, not the old index.html
r = await get(S, '/');                    ok(r.status === 200 && /Sky ?Link/i.test(r.body) && !/BASIRAH-INDEX/.test(r.body), 'home page is Sky Link, not index.html (' + r.status + ')');
ok(/http:\/\/skylinkict\.com:\d+\/sl\/assets\/(images|videos)\//.test(r.body), 'home page loads media from /sl/assets');
ok(/\/sl\/assets\/images\//.test(r.body), 'home page loads images from /sl/assets/images');
ok(!/(href|src)="[^"]*skylinkict\.com:\d+\/(assets|build|storage)\//.test(r.body), 'no un-prefixed /assets|/build|/storage URLs');
// 3. Sky Link routes
for (const p of ['/about', '/services', '/news', '/portfolio', '/contact', '/login', '/products']) {
    r = await get(S, p); ok(r.status === 200, `Sky Link ${p} -> ${r.status}`);
}
r = await get(S, '/admin/anniversary'); ok(r.status === 302, 'admin area redirects a guest -> ' + r.status);
r = await get(S, '/no-such-page-xyz');  ok(r.status === 404 && /404|Not Found/i.test(r.body), 'unknown URL -> Laravel 404');
// 4. assets through /sl/ symlinks
r = await get(S, '/sl/build/manifest.json'); ok(r.status === 200 && /app-/.test(r.body), 'Vite manifest via /sl/build -> ' + r.status);
r = await get(S, '/sl/assets/images/skylink-logo.svg'); ok(r.status === 200, '/sl/assets/images logo -> ' + r.status);
r = await get(S, '/sl/storage/');         ok(r.status !== 200 || r.body === '', '/sl/storage listing not exposed (' + r.status + ')');
r = await get(S, '/sl/index.php');        ok(r.status === 404, '/sl/index.php does not exist (only 3 symlinks inside /sl) -> ' + r.status);
r = await get(S, '/sl/.env');             ok(r.status === 404, '/sl/.env not reachable -> ' + r.status);
r = await get(S, '/favicon.ico');         ok(r.status === 200, '/favicon.ico -> ' + r.status);
// 5. redirects
r = await get('www.' + S, '/about?x=1');  ok(r.status === 301 && /^https:\/\/skylinkict\.com(:\d+)?\/about\?x=1$/.test(r.headers.location), 'www -> non-www https, path+query kept');
// 6. other hosts are never sent to Laravel
for (const h of ['chapatest.skylinkict.com', 'tms.skylinkict.com']) {
    r = await get(h, '/does-not-exist'); ok(r.status === 404 && !/Laravel|Sky ?Link/i.test(r.body), `${h}/does-not-exist -> plain ${r.status}`);
    r = await get(h, '/skylink-front.php'); ok(r.status === 404, `${h} cannot reach the front controller directly -> ${r.status}`);
}
// 7. browser: styles, images, admin login, Anniversary page, form posts (CSRF + session over the new host)
const browser = await chromium.launch({ args: [`--host-resolver-rules=MAP ${S} 127.0.0.1,MAP www.${S} 127.0.0.1`] });
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (x) => x.abort());
const page = await ctx.newPage();
page.setDefaultTimeout(90000); // slow CDN/font requests on a busy PC made a 30 s default flaky
const bad = [];
page.on('response', (x) => { if (x.status() >= 400 && !/tawk|favicon/.test(x.url())) bad.push(x.status() + ' ' + x.url()); });
await page.goto(`http://${S}:${port}/`, { waitUntil: 'networkidle' });
ok((await page.evaluate(() => [...document.images].filter((i) => i.complete && i.naturalWidth > 0).length)) > 3, 'browser: images decoded through /sl/assets');
ok((await page.evaluate(() => getComputedStyle(document.body).fontFamily)).length > 0 && (await page.evaluate(() => document.styleSheets.length)) > 0, 'browser: stylesheets applied');
await page.goto(`http://${S}:${port}/login`);
await page.fill('input[name=email]', process.env.ADMIN_EMAIL);
await page.fill('input[name=password]', process.env.ADMIN_PASSWORD);
await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
ok(/\/admin\/dashboard/.test(page.url()), 'browser: admin login works on the new host -> ' + new URL(page.url()).pathname);
await page.goto(`http://${S}:${port}/admin/anniversary`);
const st = (await page.textContent('#anniversary-status')).trim().replace(/\s+/g, ' ');
ok(/HIDDEN/.test(st), 'browser: Anniversary page -> ' + st);
ok(bad.length === 0, 'browser: no 4xx/5xx' + (bad.length ? ': ' + bad.slice(0, 4).join(' | ') : ''));
await browser.close();
console.log(fails.length ? `\n${fails.length} FAILED` : '\nALL STAGE-2 CHECKS PASSED');
process.exit(fails.length ? 1 : 0);
