// LOCAL USE ONLY. Homepage Video admin page, end to end, in a real browser on the STAGING site (bedru).
//   node tools/local/staging-hero-video-check.mjs <folder with test-desktop.mp4 test-mobile.mp4 test-poster.jpg evil.mp4>
// Login comes from deploy/STAGING-ACCESS.txt (never printed). It uploads small test files, checks the homepage,
// then resets to the built-in video so the site is left as it was. Screenshots -> storage/app/staging-checks/hero-video/.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const dir = path.resolve(process.argv[2] || '.');
const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const shots = path.resolve('storage/app/staging-checks/hero-video');
fs.mkdirSync(shots, { recursive: true });
const results = [];
const rec = (name, pass, note = '') => { results.push({ name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', name, note ? '- ' + note : ''); };

const browser = await chromium.launch();
const ctxOpts = (extra = {}) => ({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined, viewport: { width: 1440, height: 900 }, ...extra });
const admin = await (await browser.newContext(ctxOpts())).newPage();
admin.setDefaultTimeout(120000);
const go = async (p, u) => { await p.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 120000 }); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {}); };
const shot = (p, name) => p.screenshot({ path: path.join(shots, name + '.png') }).catch(() => {});

async function heroState(extra = {}) {
    const ctx = await browser.newContext(ctxOpts(extra));
    const p = await ctx.newPage(); p.setDefaultTimeout(120000);
    await go(p, '/'); await p.waitForTimeout(2500);
    const s = await p.evaluate(() => {
        const v = document.querySelector('video.hero-media__video');
        return v ? { mp4: v.dataset.desktopMp4 || '', webm: v.dataset.desktopWebm || '', mobile: v.dataset.mobileMp4 || '', poster: v.getAttribute('poster') || '', src: v.currentSrc || '' } : null;
    });
    const w = extra.viewport?.width || 1440;
    await shot(p, `home-${w}${extra.reducedMotion ? '-reduced' : ''}`);
    await ctx.close();
    return s;
}

// login
await go(admin, '/login');
await admin.fill('input[name=email]', 'admin@gmail.com');
await admin.fill('input[name=password]', adminPw);
await Promise.all([admin.waitForNavigation({ timeout: 120000 }), admin.click('button[type=submit]')]);
rec('admin login', /\/admin\//.test(admin.url()));

// guests cannot reach it
const guest = await (await browser.newContext(ctxOpts())).newPage();
await guest.goto(base + '/admin/hero-video', { waitUntil: 'domcontentloaded' });
rec('guest blocked from /admin/hero-video', /\/login/.test(guest.url()), guest.url().replace(base, ''));

// page
await go(admin, '/admin/hero-video');
const text = (await admin.textContent('body')).replace(/\s+/g, ' ');
rec('page opens, shows built-in video + server limit', /built-in video/i.test(text) && /accepts files up to \d+(\.\d+)? MB/.test(text), (text.match(/up to [\d.]+ MB/) || [''])[0]);
rec('no-ffmpeg guidance shown', /cannot shrink videos/.test(text) || /shrink the video/.test(text), /cannot shrink/.test(text) ? 'server has no ffmpeg: poster required' : 'ffmpeg available');
await shot(admin, 'admin-page-default');
const before = await heroState();
rec('homepage default uses built-in files', !!before && /assets\/videos\/hero\//.test(before.mp4 + before.webm), before?.mp4);

async function upload(files) {
    await go(admin, '/admin/hero-video');
    for (const [field, file] of Object.entries(files)) await admin.setInputFiles('#f-' + field, path.join(dir, file));
    await admin.click('#hero-submit');
    await Promise.race([admin.waitForNavigation({ timeout: 120000 }).catch(() => {}), admin.waitForSelector('#hero-error:not(.d-none)', { timeout: 120000 }).catch(() => {})]);
    await admin.waitForLoadState('load').catch(() => {});
}

// invalid: PHP renamed to .mp4
await upload({ desktop_mp4: 'evil.mp4', poster: 'test-poster.jpg' });
const errText = (await admin.textContent('#hero-error').catch(() => '')) + ' ' + (await admin.textContent('#e-desktop_mp4').catch(() => ''));
rec('PHP renamed to .mp4 is rejected', /not match|not allowed|Wrong file type/i.test(errText), errText.trim().slice(0, 120));
await shot(admin, 'admin-invalid-rejected');
rec('rejected upload changed nothing', JSON.stringify((await heroState())?.mp4) === JSON.stringify(before.mp4));

// invalid: no desktop video first
await upload({ poster: 'test-poster.jpg' });
rec('poster alone is refused on first upload', /Start with the desktop MP4/.test(await admin.textContent('body')));

// valid desktop + poster
await upload({ desktop_mp4: 'test-desktop.mp4', poster: 'test-poster.jpg' });
rec('valid upload accepted', /uploaded video/i.test(await admin.textContent('body')));
await shot(admin, 'admin-after-upload');
const afterDesktop = await heroState();
rec('homepage plays uploaded desktop video + poster', !!afterDesktop && /\/storage\/hero-video\/[A-Za-z0-9]{40}\.mp4/.test(afterDesktop.mp4) && /\/storage\/hero-video\//.test(afterDesktop.poster), afterDesktop?.mp4);
const vUrl = afterDesktop?.mp4;
if (vUrl) {
    const r = await admin.request.get(vUrl);
    rec('uploaded video is served as video/mp4', r.status() === 200 && /video\/mp4/.test(r.headers()['content-type'] || ''), `${r.status()} ${r.headers()['content-type']}`);
    const h = await admin.request.get(base + '/storage/hero-video/.htaccess');
    rec('.htaccess in upload folder is not downloadable', h.status() >= 400, String(h.status()));
}

// mobile cut
await upload({ mobile_mp4: 'test-mobile.mp4' });
const afterMobile = await heroState({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
rec('phone width gets the mobile video', !!afterMobile && /\/storage\/hero-video\//.test(afterMobile.mobile) && (afterMobile.src === '' || afterMobile.src.includes(afterMobile.mobile.split('?')[0])), afterMobile?.mobile);
const reduced = await heroState({ reducedMotion: 'reduce' });
rec('reduced motion: no video source, poster only', !!reduced && reduced.src === '', 'currentSrc=' + JSON.stringify(reduced?.src));

// reset (in-page confirmation, no alert)
let sawNativeDialog = false; admin.on('dialog', (d) => { sawNativeDialog = true; d.dismiss(); });
await go(admin, '/admin/hero-video');
await admin.click('#btn-reset');
await admin.waitForSelector('.sky-modal [data-sky-ok]', { state: 'visible' });
await shot(admin, 'admin-reset-confirm');
await Promise.all([admin.waitForNavigation(), admin.click('.sky-modal [data-sky-ok]')]);
rec('reset uses the in-page dialog (no alert)', !sawNativeDialog);
const afterReset = await heroState();
rec('reset: homepage back to built-in video', /assets\/videos\/hero\//.test(afterReset?.mp4 || ''), afterReset?.mp4);

// restore previous
await go(admin, '/admin/hero-video');
await admin.click('#btn-restore');
await admin.waitForSelector('.sky-modal [data-sky-ok]', { state: 'visible' });
await Promise.all([admin.waitForNavigation(), admin.click('.sky-modal [data-sky-ok]')]);
const afterRestore = await heroState();
rec('restore previous: uploaded video is back', /\/storage\/hero-video\//.test(afterRestore?.mp4 || ''), afterRestore?.mp4);

// leave the site as found: reset to built-in
await go(admin, '/admin/hero-video');
await admin.click('#btn-reset');
await admin.waitForSelector('.sky-modal [data-sky-ok]', { state: 'visible' });
await Promise.all([admin.waitForNavigation(), admin.click('.sky-modal [data-sky-ok]')]);
const final = await heroState();
rec('left on the built-in video', /assets\/videos\/hero\//.test(final?.mp4 || ''));

await browser.close();
fs.writeFileSync(path.join(shots, 'report.json'), JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
