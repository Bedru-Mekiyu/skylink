// DIAGNOSIS (read-only): /news "Watch video" pop-up in mobile engines. One page load per variant, long pauses.
//   BASE=https://bedru.skylinkict.com node tools/local/diag-news-video.mjs
import { chromium, webkit, devices } from 'playwright-core';
import fs from 'node:fs';

const base = process.env.BASE || 'https://bedru.skylinkict.com';
fs.mkdirSync('footage/_review/news-video', { recursive: true });
const variants = [
    { name: 'pixel7-chromium-default', engine: chromium, device: 'Pixel 7' },
    { name: 'pixel7-chromium-slow4g-savedata', engine: chromium, device: 'Pixel 7', slow: true, saveData: true },
    { name: 'galaxy-chromium-3pc-blocked', engine: chromium, device: 'Galaxy S9+', args: ['--test-third-party-cookie-phaseout'] },
    { name: 'iphone-webkit', engine: webkit, device: 'iPhone 14' },
];
const only = process.env.ONLY;
const report = [];
for (const v of variants) {
    if (only && !v.name.includes(only)) continue;
    let browser;
    try { browser = await v.engine.launch({ args: v.args || [] }); } catch (e) { report.push({ variant: v.name, error: String(e.message).split('\n')[0] }); continue; }
    const ctx = await browser.newContext({ ...devices[v.device], extraHTTPHeaders: v.saveData ? { 'Save-Data': 'on' } : {} });
    const p = await ctx.newPage();
    const msgs = [], blocked = [], cons = [], t0 = Date.now();
    p.on('console', (m) => { if (['error', 'warning'].includes(m.type())) cons.push(m.text().slice(0, 140)); });
    p.on('requestfailed', (r) => { if (/tiktok|tiktokcdn|byteoversea|ibytedtos|youtube/i.test(r.url())) blocked.push(r.failure()?.errorText + ' ' + r.url().slice(0, 90)); });
    const hdr = {};
    p.on('response', (r) => { if (r.url() === base + '/news') for (const k of ['content-security-policy', 'permissions-policy', 'referrer-policy', 'cross-origin-opener-policy', 'cross-origin-embedder-policy', 'x-frame-options']) hdr[k] = r.headers()[k] || '(none)'; });
    await p.exposeFunction('__diag', (m) => msgs.push({ t: Date.now() - t0, ...m }));
    await p.addInitScript(() => { window.addEventListener('message', (e) => { let d = e.data; if (typeof d === 'string') { try { d = JSON.parse(d); } catch (x) { return; } } if (d && d['x-tiktok-player']) window.__diag({ type: d.type, value: d.value || null }); }); });
    if (v.slow) { const c = await ctx.newCDPSession(p); await c.send('Network.enable'); await c.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 1.6e6 / 8 * 0.75, uploadThroughput: 0.75e6 / 8 }); }
    await p.goto(base + '/news', { waitUntil: 'load', timeout: 90000 });
    await p.waitForTimeout(1500);
    const opener = p.locator('[data-news-player-open]').first();
    const info = { variant: v.name, hasOpener: (await opener.count()) > 0 };
    if (info.hasOpener) {
        info.platform = await opener.getAttribute('data-platform'); info.embed = await opener.getAttribute('data-embed');
        await opener.tap().catch(() => opener.click());
        await p.waitForTimeout(1000);
        info.iframe = await p.evaluate(() => { const f = document.querySelector('#newsPlayer iframe'); return f ? { src: f.src, allow: f.allow, referrerPolicy: f.referrerPolicy, loading: f.getAttribute('loading'), sandbox: f.getAttribute('sandbox') } : null; });
        const tFrame = Date.now();
        await p.waitForTimeout(Number(process.env.WAIT || 30000));
        info.outcome = await p.evaluate(() => ({ fallback: !!document.querySelector('#newsPlayer .sky-video-fallback'), fallbackText: document.querySelector('#newsPlayer .sky-video-fallback__msg')?.textContent || null, loaded: !!document.querySelector('#newsPlayer iframe.is-loaded'), iframeStillThere: !!document.querySelector('#newsPlayer iframe') }));
        info.waitedMs = Date.now() - tFrame;
        await p.screenshot({ path: `footage/_review/news-video/${v.name}.png` });
    }
    info.messages = msgs; info.headers = hdr; info.blocked = blocked.slice(0, 6); info.console = cons.slice(0, 6);
    report.push(info);
    await browser.close();
    await new Promise((r) => setTimeout(r, 4000));
}
fs.writeFileSync('footage/_review/news-video/diagnosis.json', JSON.stringify(report, null, 1));
console.log(JSON.stringify(report, null, 1).slice(0, 6000));
