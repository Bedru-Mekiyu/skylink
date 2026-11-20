// LOCAL USE ONLY. For every TikTok video of the test site: load TikTok's own player (bare page, same URL the site uses) in REAL Chrome
// (with the H.264 codecs the bundled test browser lacks) and record what the player tells its host page:
// onPlayerReady / onStateChange / onPlayerError(code). Also asks TikTok's oEmbed whether the video is public and embeddable.
//   node tools/local/video-embed-probe.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const rows = JSON.parse(fs.readFileSync('storage/app/staging-checks/videos.json', 'utf8').replace(/^﻿/, '')).filter((r) => /tiktok\.com/.test(r.url));
const out = [];
let browser;
try { browser = await chromium.launch({ channel: 'chrome', args: ['--autoplay-policy=no-user-gesture-required'] }); console.log('browser: installed Google Chrome'); }
catch (e) { browser = await chromium.launch({ args: ['--autoplay-policy=no-user-gesture-required'] }); console.log('browser: bundled Chromium (no H.264)'); }
const ctx = await browser.newContext({ viewport: { width: 500, height: 800 } });

for (const r of rows) {
    const id = (r.url.match(/\/video\/(\d+)/) || [])[1];
    const oe = await fetch('https://www.tiktok.com/oembed?url=' + encodeURIComponent(r.url.replace(/\?.*$/, ''))).then(async (x) => ({ status: x.status, body: x.status === 200 ? await x.json() : null })).catch(() => ({ status: 0 }));
    const p = await ctx.newPage();
    const msgs = [];
    await p.exposeFunction('seen', (m) => msgs.push(m));
    await p.setContent(`<html><body style="margin:0"><iframe width=400 height=700 src="https://www.tiktok.com/player/v1/${id}?autoplay=0" allow="autoplay; fullscreen"></iframe><script>window.addEventListener('message',function(e){ if(/tiktok\\.com$/.test(new URL(e.origin).hostname) && e.data && e.data['x-tiktok-player']) window.seen({type:e.data.type,value:e.data.value}); });</script></body></html>`);
    await p.waitForTimeout(9000);
    // ask the player to play (a click on the frame), to see playback errors too
    await p.mouse.click(200, 350).catch(() => {});
    await p.waitForTimeout(5000);
    const ready = msgs.some((m) => m.type === 'onPlayerReady');
    const errors = msgs.filter((m) => /Error/.test(m.type)).map((m) => JSON.stringify(m.value));
    const states = msgs.filter((m) => m.type === 'onStateChange').map((m) => m.value);
    const t = await p.frames().find((f) => f !== p.mainFrame())?.locator('body').innerText({ timeout: 3000 }).catch(() => '');
    const row = { id: r.id, tiktokId: id, title: r.title.slice(0, 40), oembed: oe.status, embedProduct: oe.body?.embed_product_id ?? null, ready, states: [...new Set(states)].join(','), errors: errors.join(' ') || '-', frameText: (t || '').replace(/\s+/g, ' ').slice(0, 50) };
    out.push(row);
    console.log(`#${row.id}`.padEnd(4), `oembed ${row.oembed}`, `ready=${row.ready}`, `states=[${row.states}]`, `errors=${row.errors}`, '|', row.frameText);
    await p.close();
}
fs.writeFileSync('storage/app/staging-checks/videos/embed-probe.json', JSON.stringify(out, null, 2));
await browser.close();
const bad = out.filter((r) => !r.ready || r.errors !== '-' || r.oembed !== 200);
console.log(bad.length ? `\n${bad.length} video(s) did not play cleanly: ${bad.map((b) => '#' + b.id).join(', ')}` : '\nAll TikTok videos: embeddable, player ready, no errors.');
