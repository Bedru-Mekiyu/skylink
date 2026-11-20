// LOCAL USE ONLY (never against bedru). Admin pages + regression checks on the local site, mail caught by Mailpit.
//   node tools/local/local-verify-admin.mjs <folder with test-logo.png, not-an-image.png>
// Local admin: local-check@example.test (created for this check, removed afterwards by the hand-over notes).
import { chromium } from 'playwright-core';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const dir = path.resolve(process.argv[2] || '.');
const base = process.env.BASE || 'http://127.0.0.1:8000';
const php = process.env.PHP || 'C:/Users/AFRO-EAGLE/tools/php85/php.exe';
const out = path.resolve('storage/app/staging-checks/about-gallery');
const outP = path.resolve('storage/app/staging-checks/partners');
const photos = ['achieve1.jpg', 'achieve2.jpg', 'marketing.jpg'].map((f) => path.resolve('public/assets/images/about', f));
fs.mkdirSync(out, { recursive: true });
const results = []; const rec = (n, p, note = '') => { results.push({ n, p, note }); console.log(p ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const tinker = (code) => spawnSync(php, ['artisan', 'tinker', '--execute=' + code], { encoding: 'utf8' }).stdout;

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'no-preference' });
const p = await ctx.newPage(); p.setDefaultTimeout(60000);
const errors = []; p.on('pageerror', (e) => errors.push(e.message));
const go = async (u) => { const r = await p.goto(base + u, { waitUntil: 'load' }); return r; };

await go('/login'); await p.fill('input[name=email]', 'local-check@example.test'); await p.fill('input[name=password]', 'LocalCheck-2026!');
await Promise.all([p.waitForNavigation(), p.click('button[type=submit]')]);
rec('local admin login', /\/admin\//.test(p.url()));

// ---------------- Partners admin ----------------
await go('/admin/partners'); await p.waitForTimeout(1500);
rec('partners list: 20 per page and Bootstrap pagination', (await p.locator('#partner-table tbody tr[data-id]').count()) === 20 && (await p.locator('.pagination').count()) > 0);
await go('/admin/partners/create'); await p.fill('#name', 'ZZ Check Partner'); await p.setInputFiles('#logo', path.join(dir, 'not-an-image.png'));
await Promise.all([p.waitForNavigation().catch(() => {}), p.click('#partner-submit')]);
const t1 = (await p.textContent('body')).replace(/\s+/g, ' ');
rec('partners: a text file renamed .png is rejected by the app', /Only JPG, PNG or WebP/.test(t1) && !(await p.evaluate(() => document.body.innerHTML.includes('was added'))), (t1.match(/Only JPG[^.]*\./) || [''])[0]);
await p.screenshot({ path: path.join(outP, 'local-invalid-rejected.png') });
await go('/admin/partners/create'); await p.fill('#name', 'ZZ Check Partner'); await p.setInputFiles('#logo', path.join(dir, 'test-logo.png')); await p.waitForSelector('#pv-logo:not([hidden])');
await Promise.all([p.waitForNavigation(), p.click('#partner-submit')]);
rec('partners: valid logo added', /ZZ Check Partner/.test(await p.textContent('body')));
await go('/admin/partners?q=ZZ+Check'); const row = await p.locator('tr:has-text("ZZ Check Partner")').first(); const rid = await row.getAttribute('data-id');
let nativeDialog = false; p.on('dialog', (d) => { nativeDialog = true; d.dismiss(); });
await p.click(`tr[data-id="${rid}"] form[action$="/toggle"] button`); await p.waitForSelector('.sky-modal [data-sky-ok]', { state: 'visible' });
await Promise.all([p.waitForNavigation(), p.click('.sky-modal [data-sky-ok]')]);
rec('partners: hide uses the in-page dialog, partner hidden', !nativeDialog && /hidden from the homepage/.test(await p.textContent('body')));
// reorder with the arrow button, persisted
await go('/admin/partners'); const first = await p.locator('#partner-table tbody tr[data-id]').first().getAttribute('data-id');
await p.click('#partner-table tbody tr[data-id]:nth-child(1) [data-move="1"]'); await p.waitForTimeout(800); await go('/admin/partners');
rec('partners: arrow reorder is saved', (await p.locator('#partner-table tbody tr[data-id]').nth(1).getAttribute('data-id')) === first);
tinker("App\\Models\\Partner::where('name','ZZ Check Partner')->get()->each(function($x){ foreach([$x->logo_path,$x->logo_fallback] as $f){ if($f) Storage::disk('public')->delete($f);} if($x->original_path) Storage::disk('local')->delete($x->original_path); $x->delete(); }); $p=App\\Models\\Partner::orderBy('sort_order')->get(); $i=0; foreach($p as $x){ $x->update(['sort_order'=>++$i]); } App\\Support\\PublicCache::bust();");

// ---------------- Gallery admin ----------------
await go('/admin/gallery'); await p.waitForTimeout(1000);
const priv = (await p.textContent('body')).includes("Only upload photos you are allowed to show publicly (for example, with the partners' or clients' permission).");
rec('gallery admin: privacy line shown', priv);
tinker("App\\Models\\GalleryImage::query()->delete();");
await go('/admin/gallery');
await p.setInputFiles('#photos', [...photos, path.join(dir, 'not-an-image.png')]); await p.fill('#cap-en', 'Bulk upload test'); await p.fill('#cap-am', 'የሙከራ መግለጫ');
await p.click('#upload-btn'); await p.waitForSelector('#upload-progress:not(.d-none)');
await p.screenshot({ path: path.join(out, 'admin-gallery-uploading.png') });
await p.waitForFunction(() => /photo\(s\) added/.test(document.querySelector('#upload-log')?.textContent || ''), null, { timeout: 90000 });
const lg = await p.locator('#upload-log').innerText();
rec('gallery admin: bulk upload with progress bar, 3 added and the text file rejected', /3 photo\(s\) added, 1 failed/.test(lg) && /not-an-image\.png: Only JPG, PNG or WebP/.test(lg), lg.replace(/\s+/g, ' ').slice(0, 160));
await p.waitForNavigation({ timeout: 10000 }).catch(() => {}); await p.waitForLoadState('load');
rec('gallery admin: list shows 3 thumbnails with captions', (await p.locator('#gallery-table tbody tr[data-id]').count()) === 3 && /Bulk upload test/.test(await p.textContent('body')));
await p.screenshot({ path: path.join(out, 'admin-gallery-list.png') });
const gid = await p.locator('#gallery-table tbody tr[data-id]').first().getAttribute('data-id');
await go(`/admin/gallery/${gid}/edit`); await p.fill('#caption_en', 'Edited caption'); await p.fill('#caption_am', 'የተስተካከለ'); await p.fill('#taken_on', '2026-03-15');
await Promise.all([p.waitForNavigation(), p.click('#gallery-save')]);
rec('gallery admin: edit captions (English + Amharic) and date saved', /Edited caption/.test(await p.textContent('body')) && /የተስተካከለ/.test(await p.textContent('body')));
await p.click(`tr[data-id="${gid}"] form[action$="/toggle"] button`); await p.waitForSelector('.sky-modal [data-sky-ok]', { state: 'visible' });
await Promise.all([p.waitForNavigation(), p.click('.sky-modal [data-sky-ok]')]);
rec('gallery admin: hide uses the in-page dialog', !nativeDialog && /hidden from the gallery/.test(await p.textContent('body')));
const shown = tinker('echo App\\Models\\GalleryImage::where("visible",1)->count();').trim();
rec('hidden photo no longer public (2 visible of 3)', shown.endsWith('2'), shown);
await p.click('#gallery-table tbody tr[data-id]:nth-child(1) [data-move="1"]'); await p.waitForTimeout(800);
rec('gallery admin: arrow reorder saves', (await p.locator('#gallery-live').innerText()).includes('Order saved'));
await go('/about'); rec('About page shows the 2 visible photos as a still grid (fewer than 3)', (await p.locator('#gallery .sk-gallery__slide').count()) === 2 && !(await p.evaluate(() => document.querySelector('#gallery').classList.contains('is-cinematic'))));
tinker("App\\Models\\GalleryImage::query()->update(['visible'=>false]); App\\Support\\PublicCache::bust();");
await go('/about'); rec('no visible photos: no gallery section and no gap', (await p.locator('#gallery').count()) === 0);
await p.screenshot({ path: path.join(out, 'about-no-gallery.png'), fullPage: false });

// ---------------- Regression: contact forms + email (Mailpit) ----------------
await fetch('http://127.0.0.1:8025/api/v1/messages', { method: 'DELETE' }).catch(() => {});
const ctx2 = await browser.newContext({ viewport: { width: 390, height: 844 } }); const q = await ctx2.newPage(); q.setDefaultTimeout(60000);
for (const type of ['contact', 'quote']) {
    await q.goto(base + '/contact', { waitUntil: 'load' }); await q.waitForTimeout(4800);
    await q.fill('#cf-name', 'Local Check ' + type); await q.selectOption('#cf-type', type); await q.fill('#cf-email', 'visitor@example.test'); await q.fill('#cf-phone', '+251911000000');
    if (await q.$('#cf-service')) await q.fill('#cf-service', 'Web Development').catch(() => {});
    await q.fill('#cf-message', 'Local regression test ' + type);
    await Promise.all([q.waitForNavigation(), q.click('#contactForm button[type=submit]')]);
    const t = (await q.textContent('body')).replace(/\s+/g, ' ');
    rec(`form "${type}": saved and confirmation page shown`, /SK-\d{6}-[A-Z0-9]{5}/.test(t));
}
await q.waitForTimeout(2500);
const mp = await (await fetch('http://127.0.0.1:8025/api/v1/messages')).json().catch(() => ({ messages: [] }));
const subjects = (mp.messages || []).map((m) => m.Subject + ' -> ' + (m.To || []).map((x) => x.Address).join(','));
rec('Mailpit caught the team and visitor emails (2 forms = 4 mails)', (mp.messages || []).length >= 4 && subjects.some((s) => /visitor@example\.test/.test(s)), subjects.slice(0, 4).join(' | ').slice(0, 220));
await ctx2.close();

// ---------------- Regression: inbox ----------------
await go('/admin/inbox'); await p.waitForTimeout(800);
const inboxRows = await p.locator('table tbody tr, .inbox-row, [data-inquiry]').count();
rec('inbox lists the new messages', /Local Check/.test(await p.textContent('body')), inboxRows + ' rows');
const link = p.locator('a:has-text("Local Check")').first(); await link.click(); await p.waitForLoadState('load');
rec('inbox message opens', /Local regression test/.test(await p.textContent('body')));
await go('/admin/inbox');
const del = p.locator('button.inbox-del').first();
if (await del.count()) { await del.click(); await p.waitForSelector('.sky-modal [data-sky-ok]', { state: 'visible' }); await Promise.all([p.waitForNavigation(), p.click('.sky-modal [data-sky-ok]')]); rec('inbox delete uses the in-page dialog', !nativeDialog); } else rec('inbox delete button found', false);

// ---------------- Regression: News filter, marquees, content management ----------------
await go('/news'); const chips = p.locator('a.news-chip'); const nChips = await chips.count();
const before = await p.locator('.news-card').count();
await go('/news?category=events'); const after = await p.locator('.news-card').count(); const cats = await p.evaluate(() => [...document.querySelectorAll('.news-card .news-tag, .news-card [class*=tag]')].map((e) => e.textContent.trim().toLowerCase()));
rec('News filter: category chips change the list', nChips >= 3 && after > 0 && after <= before && cats.every((c) => /event/.test(c)), `${nChips} chips, ${before} -> ${after} cards, tags ${[...new Set(cats)].join(',')}`);
await go('/'); await p.waitForTimeout(1200);
const marq = await p.evaluate(() => ['.services-track', '.products-track', '.partners-track'].map((s) => { const e = document.querySelector(s); return e ? s + ':' + getComputedStyle(e).animationName : s + ':missing'; }));
rec('homepage marquees run', marq.filter((x) => /:none|missing/.test(x)).length === 0, marq.join(' '));
for (const u of ['/admin/dashboard', '/admin/news', '/admin/portfolio', '/admin/videos', '/admin/products', '/admin/services', '/admin/service-categories', '/admin/subscribers', '/admin/admin/subscriptions', '/admin/profile', '/admin/anniversary', '/admin/hero-video', '/admin/partners', '/admin/gallery']) {
    const r = await go(u); if (r.status() >= 400) rec(`admin page ${u}`, false, String(r.status()));
}
rec('all admin pages load (no 4xx/5xx)', !results.some((r) => /admin page/.test(r.n)));
rec('no JavaScript errors on the checked pages', errors.length === 0, errors.slice(0, 2).join(' | '));
await go('/admin/anniversary'); rec('anniversary admin page: ceremony Off by default', /Currently HIDDEN|HIDDEN/i.test(await p.textContent('body')));

await browser.close();
fs.writeFileSync(path.join(outP, 'local-admin-report.json'), JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.p); console.log(`\n${results.length - failed.length}/${results.length} passed`); process.exit(failed.length ? 1 : 0);
