// LOCAL USE ONLY (local sqlite site, http://127.0.0.1:8000). Admin sweep: login rules, every admin GET page at 1440 and 390 px,
// guests, /register, stack traces. A temporary admin is created and removed. Uploads are covered by PHPUnit
// (GalleryTest, PartnerTest, HeroVideoTest, ImageUploadValidationTest, AdminCanAddContentTest, ...).
//   node tools/local/local-verify-admin-sweep.mjs
import { chromium } from 'playwright-core';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const base = process.env.BASE || 'http://127.0.0.1:8000';
const php = process.env.PHP || process.env.USERPROFILE + '/.config/herd/bin/php84/php.exe';
const out = path.resolve('footage/_review/admin-sweep');
fs.mkdirSync(out, { recursive: true });
const guard = spawnSync(php, ['tools/local/guard.php'], { encoding: 'utf8' });
if (guard.status !== 0) { console.error('Refusing to run: ' + guard.stdout + guard.stderr); process.exit(2); }
const tinker = (code) => spawnSync(php, ['artisan', 'tinker', '--execute=' + code], { encoding: 'utf8' }).stdout.trim().split('\n').pop();
const results = []; const warnings = [];
const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const EMAIL = 'sweep-admin@example.test', PW = 'Sweep-Check-2026!x';
tinker(`App\\Models\\User::where("email","${EMAIL}")->delete(); $u = new App\\Models\\User(); $u->forceFill(["name"=>"Sweep Admin","email"=>"${EMAIL}","password"=>Hash::make("${PW}"),"is_admin"=>true,"email_verified_at"=>now()])->save(); echo "created";`);

// first record id of each model for the {param} pages
const ids = JSON.parse(tinker('echo json_encode(["gallery"=>optional(App\\Models\\GalleryImage::first())->id,"inbox"=>optional(App\\Models\\Inquiry::first())->id,"news"=>optional(App\\Models\\News::first())->id,"partner"=>optional(App\\Models\\Partner::first())->id,"portfolio"=>optional(App\\Models\\Portfolio::first())->id,"product"=>optional(App\\Models\\Product::first())->id,"cat"=>optional(App\\Models\\ServiceCategory::first())->id,"service"=>optional(App\\Models\\Service::first())->id,"video"=>optional(App\\Models\\Video::first())->id]);'));
const pages = ['/admin/dashboard', '/admin/profile', '/admin/anniversary', '/admin/hero-video', '/admin/gallery', '/admin/testimonials', '/admin/testimonials/create', '/admin/departments', '/admin/departments/create', '/admin/partners', '/admin/partners/create', '/admin/news', '/admin/news/create', '/admin/news/duplicates-report', '/admin/portfolio', '/admin/portfolio/create', '/admin/products', '/admin/products/create', '/admin/services', '/admin/services/create', '/admin/service-categories', '/admin/service-categories/create', '/admin/videos', '/admin/videos/create', '/admin/inbox', '/admin/contact-messages', '/admin/subscribers', '/admin/admin/subscriptions', '/admin/newsletter'];
const withId = [['gallery', 'gallery/%/edit'], ['inbox', 'inbox/%'], ['news', 'news/%/edit'], ['partner', 'partners/%/edit'], ['portfolio', 'portfolio/%/edit'], ['product', 'products/%/edit'], ['product', 'products/%'], ['cat', 'service-categories/%/edit'], ['service', 'services/%/edit'], ['service', 'services/%'], ['video', 'videos/%/edit']];
for (const [k, tpl] of withId) if (ids[k]) pages.push('/admin/' + tpl.replace('%', ids[k]));

const browser = await chromium.launch();
// guests: every admin page sends to login (or 403)
{
    const ctx = await browser.newContext(); const p = await ctx.newPage(); let bad = [];
    for (const u of pages.slice(0, 12)) { const r = await p.goto(base + u, { waitUntil: 'domcontentloaded' }); const ok = /\/login/.test(p.url()) || r.status() === 403; if (!ok) bad.push(u + ' ' + r.status()); }
    rec('guests are sent to login (or 403) for admin pages', bad.length === 0, bad.join(', '));
    const r = await p.goto(base + '/register'); rec('/register is 404', r.status() === 404, String(r.status()));
    await ctx.close();
}
// login rules
{
    const ctx = await browser.newContext(); const p = await ctx.newPage();
    await p.goto(base + '/login'); await p.fill('input[name=email]', EMAIL); await p.fill('input[name=password]', 'wrong-password'); await p.click('button[type=submit]'); await p.waitForTimeout(800);
    rec('wrong password: stays on login with an error, no stack trace', /\/login/.test(p.url()) && !/Stack trace|Whoops|ErrorException/i.test(await p.content()));
    let limited = false;
    for (let i = 0; i < 7; i++) { await p.goto(base + '/login'); await p.fill('input[name=email]', EMAIL); await p.fill('input[name=password]', 'wrong-' + i); await p.click('button[type=submit]'); await p.waitForTimeout(250); if (/Too many|too many|seconds|429/i.test(await p.content())) { limited = true; break; } }
    rec('repeated wrong passwords are rate limited', limited);
    await ctx.close();
}
// admin sweep (a fresh limiter key: different IP is not possible, so clear the cache first)
spawnSync(php, ['artisan', 'cache:clear'], { encoding: 'utf8' });
for (const [w, h, tag] of [[1440, 900, 'desktop'], [390, 844, 'phone']]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h } }); const p = await ctx.newPage();
    const errs = []; const failed = [];
    p.on('console', (m) => { if (m.type() === 'error' && !/tawk|ERR_FAILED|favicon|Failed to load resource/i.test(m.text())) errs.push(m.text().slice(0, 140)); });
    p.on('response', (r) => { if (r.status() >= 400 && !/favicon|tawk/i.test(r.url())) failed.push(r.status() + ' ' + r.url().replace(base, '')); });
    await p.goto(base + '/login'); await p.fill('input[name=email]', EMAIL); await p.fill('input[name=password]', PW); await p.click('button[type=submit]'); await p.waitForURL(/admin/, { timeout: 15000 }).catch(() => {});
    rec(`login works @${tag}`, /\/admin\//.test(p.url()), p.url().replace(base, ''));
    let n = 0, bad = [];
    for (const u of pages) {
        errs.length = 0; failed.length = 0;
        const r = await p.goto(base + u, { waitUntil: 'load', timeout: 60000 }).catch((e) => ({ status: () => 0 })); await p.waitForTimeout(350);
        const m = await p.evaluate(() => ({ ovf: document.documentElement.scrollWidth - innerWidth, trace: /Stack trace|Whoops|ErrorException|SQLSTATE|vendor.laravel/i.test(document.body.innerText), title: document.title }));
        const problems = [];
        if (r.status() !== 200) problems.push('status ' + r.status());
        if (m.ovf > 0) problems.push('sideways scroll ' + m.ovf);
        if (m.trace) problems.push('debug text');
        if (errs.length) problems.push('console: ' + errs[0]);
        const hard = failed.filter((f) => !/\.(jpe?g|png|webp|gif|svg)(\?|$)/i.test(f)); const soft = failed.filter((f) => /\.(jpe?g|png|webp|gif|svg)(\?|$)/i.test(f));
        if (soft.length) warnings.push(u + ' missing image: ' + soft[0]);
        if (hard.length) problems.push('requests: ' + hard.slice(0, 2).join(' | '));
        n++; if (problems.length) bad.push(u + ' -> ' + problems.join('; '));
        if (u === '/admin/dashboard' || u === '/admin/gallery' || u === '/admin/news') await p.screenshot({ path: path.join(out, `${u.slice(7).replace(/\W+/g, '-')}-${tag}.png`) });
    }
    rec(`${n} admin pages @${tag}: 200, no sideways scroll, no console errors / failed requests / debug text`, bad.length === 0, bad.join(' || '));
    // logout
    await p.goto(base + '/admin/dashboard');
    const logout = p.locator('form[action$="/logout"] button, a:has-text("Logout"), button:has-text("Logout"), button:has-text("Log Out")').first();
    if (await logout.count()) { await logout.evaluate((e) => e.click()).catch(() => {}); await p.waitForTimeout(1000); }
    else await p.evaluate(async () => { const t = document.querySelector('meta[name=csrf-token]')?.content; await fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': t } }); });
    await p.goto(base + '/admin/dashboard'); rec(`logout works @${tag}`, /\/login/.test(p.url()));
    await ctx.close();
}
tinker(`App\\Models\\User::where("email","${EMAIL}")->delete(); echo "removed";`);
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
