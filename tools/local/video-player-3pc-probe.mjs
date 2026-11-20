// LOCAL USE ONLY. Does the TikTok player show "Unable to load player" when third-party cookies are blocked
// (the default in Safari/Firefox strict/Brave, and a Chrome option)?   node tools/local/video-player-3pc-probe.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs'; import path from 'node:path';
const out = path.resolve('storage/app/staging-checks/videos'); fs.mkdirSync(out, { recursive: true });
for (const [label, args] of [['default', []], ['third-party-cookies-blocked', ['--test-third-party-cookie-phaseout', '--disable-features=TrackingProtection3pcd', '--enable-features=ThirdPartyCookieDeprecation']]]) {
    const browser = await chromium.launch({ args });
    const ctx = await browser.newContext({ viewport: { width: 500, height: 800 } });
    const p = await ctx.newPage();
    const msgs = [];
    await p.goto('about:blank');
    await p.setContent('<html><body style="margin:0"><iframe width=400 height=700 src="https://www.tiktok.com/player/v1/7525050791875464453?autoplay=0&lang=en-US" allow="autoplay; fullscreen"></iframe></body></html>');
    await p.exposeFunction('seen', (m) => msgs.push(m));
    await p.evaluate(() => window.addEventListener('message', (e) => { if (/tiktok/.test(e.origin)) window.seen(JSON.stringify(e.data).slice(0, 140)); }));
    await p.waitForTimeout(10000);
    let text = '';
    for (const f of p.frames()) if (f !== p.mainFrame()) text = await f.locator('body').innerText({ timeout: 4000 }).catch(() => '(unreadable)');
    console.log(`[${label}] player text: ${text.replace(/\s+/g, ' ').slice(0, 140)}`);
    console.log(`[${label}] postMessages from tiktok: ${msgs.slice(0, 4).join(' | ') || 'none'}`);
    await p.screenshot({ path: path.join(out, `probe-3pc-${label}.png`) });
    await browser.close();
}
