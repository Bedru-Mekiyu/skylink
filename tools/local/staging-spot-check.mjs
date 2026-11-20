// LOCAL USE ONLY. LIGHT spot check of bedru (about 12 page loads, paused): the host firewall blocks heavy automated testing.
//   node tools/local/staging-spot-check.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const out = path.resolve('storage/app/staging-checks/about-gallery');
fs.mkdirSync(out, { recursive: true });
const results = []; const rec = (n, p, note = '') => { results.push({ n, p, note }); console.log(p ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const nap = (ms = 3000) => new Promise((r) => setTimeout(r, ms));
let loads = 0;

const noAuth = await fetch(base + '/robots.txt'); loads++;
rec('Basic Auth is ON (no password gives 401)', noAuth.status === 401, String(noAuth.status));
await nap();
const auth = 'Basic ' + Buffer.from('staging:' + basicPw).toString('base64');
const hdr = await fetch(base + '/', { headers: { Authorization: auth } }); loads++;
const html = await hdr.text();
rec('X-Robots-Tag noindex, nofollow header', /noindex, nofollow/i.test(hdr.headers.get('x-robots-tag') || ''), hdr.headers.get('x-robots-tag'));
rec('robots meta tag in the page HTML (home)', html.includes('<meta name="robots" content="noindex, nofollow">'));
await nap();
const rob = await fetch(base + '/robots.txt', { headers: { Authorization: auth } }); loads++;
rec('robots.txt disallows everything', /Disallow: \//.test(await rob.text()));

const browser = await chromium.launch();
const ctx = await browser.newContext({ httpCredentials: { username: 'staging', password: basicPw }, viewport: { width: 1440, height: 900 }, reducedMotion: 'no-preference' });
const p = await ctx.newPage(); p.setDefaultTimeout(90000);
const go = async (u) => { await nap(); loads++; await p.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 90000 }); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); };

await go('/about'); await p.waitForTimeout(2500);
const a = await p.evaluate(() => {
    const g = document.querySelector('#gallery'); const hero = document.querySelector('.about-hero').getBoundingClientRect();
    const cards = [...document.querySelectorAll('.team-card__photo')].map((e) => { const r = e.getBoundingClientRect(); return (r.width / r.height).toFixed(2) + '/' + getComputedStyle(e).borderRadius; });
    return { gal: !!g, cin: !!g && g.classList.contains('is-cinematic'), slides: g ? g.querySelectorAll('.sk-gallery__slide').length : 0, hero: Math.round(hero.height), cards, names: [...document.querySelectorAll('.team-card h3')].map((e) => e.textContent.trim()), meta: !!document.querySelector('meta[name=robots][content="noindex, nofollow"]'), ovf: document.documentElement.scrollWidth - innerWidth, body: getComputedStyle(document.body).fontSize };
});
rec('About: gallery shown (3 test photos, cinematic mode)', a.gal && a.cin && a.slides === 3, JSON.stringify({ slides: a.slides }));
rec('About: banner 450px (50%), 4:5 rounded team cards, Hanan present, noindex meta, no sideways scroll', a.hero === 450 && a.cards.length === 4 && a.cards.every((c) => c === '0.80/12px') && a.names.includes('Hanan Nesru') && a.meta && a.ovf <= 0, JSON.stringify({ hero: a.hero, card: a.cards[0], ovf: a.ovf, body: a.body }));
await p.locator('#gallery').scrollIntoViewIfNeeded(); await p.waitForTimeout(2000);
await p.locator('#gallery').screenshot({ path: path.join(out, 'bedru-gallery-spot-check.png') });
const opener = p.locator('#gallery .sk-gallery__open[tabindex="0"]').first(); await p.waitForTimeout(1200); await opener.click({ force: true });
await p.waitForSelector('.sk-lb:not([hidden])', { timeout: 20000 }).catch(() => {});
const lb = await p.evaluate(() => { const d = document.querySelector('.sk-lb'); return d && !d.hidden ? { img: d.querySelector('img').src.split('/').pop(), cap: d.querySelector('.sk-lb__cap').innerText.replace(/\s+/g, ' ') } : null; });
rec('Lightbox opens with the large image and caption', !!lb && /-display\.webp$/.test(lb.img), JSON.stringify(lb));
await p.keyboard.press('Escape');

await go('/'); await p.locator('.partners-section').scrollIntoViewIfNeeded(); await p.waitForTimeout(2000);
const h = await p.evaluate(() => { const imgs = [...document.querySelectorAll('.partners-track .partner-logo')]; return { n: imgs.length, webp: !!document.querySelector('.partners-track picture source[type="image/webp"]'), h: [...new Set(imgs.map((i) => Math.round(i.getBoundingClientRect().height)))], sized: imgs.every((i) => i.getAttribute('width') && i.getAttribute('height')) }; });
rec('Home: 54 marquee images (27 + loop copy), WebP copies, 90px, width/height set', h.n === 54 && h.webp && h.h.length === 1 && h.h[0] === 90 && h.sized, JSON.stringify(h));

await go('/login'); await p.waitForSelector('input[name=email]'); await p.fill('input[name=email]', 'admin@gmail.com'); await p.fill('input[name=password]', adminPw);
await Promise.all([p.waitForNavigation({ timeout: 90000 }), p.click('button[type=submit]')]); loads++;
rec('admin login with the new strong password', /\/admin\//.test(p.url()));
await go('/admin/gallery');
const g = await p.evaluate(() => ({ rows: document.querySelectorAll('#gallery-table tbody tr[data-id]').length, priv: document.body.innerText.includes('Only upload photos you are allowed to show publicly'), side: [...document.querySelectorAll('.sidebar-heading')].map((e) => getComputedStyle(e).fontSize) }));
rec('Admin > Gallery: 3 rows, privacy line, 13px sidebar headings', g.rows === 3 && g.priv && g.side.every((s) => parseFloat(s) >= 13), JSON.stringify(g));
await p.screenshot({ path: path.join(out, 'bedru-admin-gallery-spot-check.png') });
await go('/admin/partners');
const pr = await p.evaluate(() => ({ rows: document.querySelectorAll('#partner-table tbody tr[data-id]').length, pag: document.querySelectorAll('.pagination').length > 0, img: [...document.querySelectorAll('#partner-table img')].slice(0, 3).map((i) => i.src.includes('/storage/partners/')) }));
rec('Admin > Partners: 20 rows, paging, thumbnails are the small web copies', pr.rows === 20 && pr.pag && pr.img.every(Boolean), JSON.stringify(pr));
await go('/admin/inbox');
rec('Inbox is empty (customer data removed)', (await p.locator('#bulk-delete-form tbody tr[data-id], .inbox-row').count()) === 0 && !/Local Check|SK-\d{6}/.test(await p.textContent('body')));
await browser.close();
console.log(`\npage loads used: ${loads} (limit 20)`);
fs.writeFileSync(path.join(out, 'bedru-spot-check-report.json'), JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.p); console.log(`${results.length - failed.length}/${results.length} passed`); process.exit(failed.length ? 1 : 0);
