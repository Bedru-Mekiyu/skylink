// LOCAL USE ONLY. Video checks on the password-protected bedru test site (login from deploy/STAGING-ACCESS.txt, never printed).
// Videos are NOT blocked. Per video detail page: the player frame loads and shows the video (not an error); the news pop-up
// player; the guard's fallback ("Watch on TikTok") when TikTok is made unreachable; the homepage hero video plays and loops.
// Screenshots -> storage/app/staging-checks/videos/ ; a short recording of a video playing -> storage/app/staging-checks/videos/recording/
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = access.match(/user\s+staging\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const out = path.resolve('storage/app/staging-checks/videos'); fs.mkdirSync(out, { recursive: true });
const rows = JSON.parse(fs.readFileSync(path.join(path.resolve('storage/app/staging-checks'), 'videos.json'), 'utf8').replace(/^﻿/, ''));
const results = [];
const rec = (area, name, pass, note = '') => { results.push({ area, name, pass, note }); console.log(pass ? 'ok  ' : 'FAIL', area.padEnd(10), name, note ? '— ' + note : ''); };

const browser = await chromium.launch({ args: ['--autoplay-policy=no-user-gesture-required'] });
const newCtx = (extra = {}) => browser.newContext({ httpCredentials: { username: 'staging', password: basicPw }, viewport: { width: 1440, height: 900 }, ...extra });
const nav = async (p, u) => { for (let a = 0; a < 5; a++) { try { return await p.goto(base + u, { waitUntil: 'domcontentloaded', timeout: 120000 }); } catch (e) { if (a === 4) throw e; await new Promise((r) => setTimeout(r, 20000 * (a + 1))); } } }; // the shared host is heavily loaded: back off instead of hammering
const frameInfo = async (p) => { const o = []; for (const f of p.frames()) { if (f === p.mainFrame()) continue; const t = await f.locator('body').innerText({ timeout: 5000 }).catch(() => ''); o.push({ url: f.url(), text: t.replace(/\s+/g, ' ').slice(0, 120) }); } return o; };

const ctx = await newCtx(); await ctx.route(/tawk\.to/, (r) => r.abort());
// the detail pages are checked for their PLAYER only: skip our own images to keep the load on the shared server small
await ctx.route((u) => u.hostname === 'bedru.skylinkict.com', (route) => (['image', 'font'].includes(route.request().resourceType()) ? route.abort() : route.continue()));
const page = await ctx.newPage(); page.setDefaultTimeout(90000);
const dialogs = []; page.on('dialog', async (d) => { dialogs.push(d.message()); await d.dismiss(); });

// 1. every video detail page
for (const v of rows) {
    const url = '/videos/' + v.slug;
    try {
        await nav(page, url);
        const tiktok = /tiktok\.com/.test(v.url);
        await page.waitForTimeout(tiktok ? 8000 : 5000);
        const info = await frameInfo(page);
        const fb = await page.$('.sky-video-fallback');
        const playerFrame = info.find((f) => (tiktok ? /tiktok\.com\/player/ : /youtube(-nocookie)?\.com\/embed/).test(f.url));
        const err = info.some((f) => /Unable to load player|Video unavailable|This video is unavailable/i.test(f.text));
        const watchLink = await page.$('a:has-text("Watch on ' + (tiktok ? 'TikTok' : 'YouTube') + '")');
        const good = !!playerFrame && !err && !fb && !!watchLink;
        rec('video', `#${v.id} ${tiktok ? 'TikTok ' : 'YouTube'} plays on its page`, good, good ? (playerFrame.text || playerFrame.url).slice(0, 60) : `frame=${!!playerFrame} err=${err} fallback=${!!fb} watchLink=${!!watchLink}`);
        if ([1, 8, 23].includes(Number(v.id))) await page.screenshot({ path: path.join(out, `detail-${v.id}-1440.png`) });
        await page.waitForTimeout(2500);
    } catch (e) { rec('video', `#${v.id} page`, false, String(e.message).slice(0, 120)); }
}
rec('video', 'no browser dialog appeared on any video page', dialogs.length === 0, dialogs.join(' | '));

// 2. news pop-up (banner) works at 1440 and 390
for (const w of [1440, 390]) {
    const c = await newCtx({ viewport: { width: w, height: w === 390 ? 844 : 900 } }); await c.route(/tawk\.to/, (r) => r.abort());
    const p = await c.newPage(); p.setDefaultTimeout(90000);
    await nav(p, '/news'); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {});
    const opener = await p.$('[data-news-player-open]');
    if (!opener) { rec('popup', `${w}px banner has a Watch video button`, false); await c.close(); continue; }
    await opener.click(); await p.waitForTimeout(8000);
    const info = await frameInfo(p);
    const ok = info.some((f) => /tiktok\.com\/player|youtube-nocookie/.test(f.url) && !/Unable to load player/i.test(f.text));
    rec('popup', `${w}px news pop-up plays the video`, ok, info[0]?.text.slice(0, 50) ?? 'no frame');
    await p.screenshot({ path: path.join(out, `popup-${w}.png`) });
    await p.keyboard.press('Escape'); await p.waitForTimeout(500);
    rec('popup', `${w}px Esc closes the pop-up and removes the player`, (await p.$('#newsPlayer[open]')) === null && (await frameInfo(p)).length === 0);
    await c.close();
}

// 3. the fallback: make TikTok unreachable for a visitor (blocked network) and check what the page shows
{
    const c = await newCtx(); await c.route(/tawk\.to/, (r) => r.abort());
    await c.route(/(^|\.)tiktok(v)?\.com|tiktokcdn/i, (r) => r.abort());
    const p = await c.newPage(); p.setDefaultTimeout(90000);
    const tk = rows.find((r) => /tiktok\.com/.test(r.url));
    await nav(p, '/videos/' + tk.slug); await p.waitForTimeout(29000);
    const fb = await p.$('.sky-video-fallback a.sky-video-fallback__btn');
    const href = fb ? await fb.getAttribute('href') : '';
    const target = fb ? await fb.getAttribute('target') : '';
    rec('fallback', 'TikTok unreachable: the detail page shows "Watch on TikTok" (new tab) instead of an error', !!fb && /tiktok\.com/.test(href) && target === '_blank' && /Unable to load/.test(await p.content()) === false, `${(await fb?.innerText())?.trim()} -> ${href.slice(0, 60)}`);
    await p.screenshot({ path: path.join(out, 'fallback-detail-1440.png') });
    await nav(p, '/news'); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {});
    const op = await p.$('[data-news-player-open]');
    if (op && (await op.getAttribute('data-platform')) === 'tiktok') {
        await op.click(); await p.waitForTimeout(29000);
        const fb2 = await p.$('#newsPlayer .sky-video-fallback a.sky-video-fallback__btn');
        rec('fallback', 'TikTok unreachable: the news pop-up shows "Watch on TikTok"', !!fb2, (await fb2?.innerText())?.trim() ?? 'none');
        await p.screenshot({ path: path.join(out, 'fallback-popup-1440.png') });
    } else rec('fallback', 'banner is a TikTok video (to test the pop-up fallback)', false, 'banner is not TikTok');
    await c.close();
}

// 4. homepage hero video
{
    const c = await newCtx(); await c.route(/tawk\.to/, (r) => r.abort());
    const p = await c.newPage(); p.setDefaultTimeout(90000);
    await nav(p, '/'); await p.waitForLoadState('load', { timeout: 90000 }).catch(() => {});
    await p.waitForTimeout(6000);
    const v1 = await p.evaluate(() => { const v = document.querySelector('video'); return v ? { src: v.currentSrc.split('/').pop().split('?')[0], t: v.currentTime, paused: v.paused, loop: v.loop, muted: v.muted, ready: v.readyState, dur: v.duration } : null; });
    await p.waitForTimeout(3000);
    const v2 = await p.evaluate(() => { const v = document.querySelector('video'); return v ? { t: v.currentTime, paused: v.paused } : null; });
    rec('hero', 'homepage hero video is present, loops, and its time advances (playing)', !!v1 && v1.loop && !v1.paused && v2.t > v1.t, JSON.stringify({ ...v1, t2: v2?.t }));
    await p.screenshot({ path: path.join(out, 'home-hero-1440.png') });
    await c.close();
}

// 5. a short recording of a video playing (pop-up) -> recording/
{
    const recDir = path.join(out, 'recording'); fs.mkdirSync(recDir, { recursive: true });
    const c = await newCtx({ recordVideo: { dir: recDir, size: { width: 960, height: 600 } }, viewport: { width: 960, height: 600 } }); await c.route(/tawk\.to/, (r) => r.abort());
    const p = await c.newPage(); p.setDefaultTimeout(90000);
    await nav(p, '/news'); await p.waitForLoadState('load', { timeout: 60000 }).catch(() => {});
    const op = await p.$('[data-news-player-open]'); if (op) { await op.click(); await p.waitForTimeout(9000); await p.keyboard.press('Escape'); await p.waitForTimeout(1000); }
    await c.close();
    rec('recording', 'recording of the video pop-up saved', fs.readdirSync(recDir).some((f) => f.endsWith('.webm')), recDir);
}

fs.writeFileSync(path.join(out, 'report-videos.json'), JSON.stringify(results, null, 2));
await browser.close();
const bad = results.filter((r) => !r.pass);
console.log(bad.length ? `\n${bad.length} of ${results.length} FAILED` : `\nALL ${results.length} VIDEO CHECKS PASSED`);
