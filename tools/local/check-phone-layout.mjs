// Phone number layout check: nav bar + footer at 1440/1024/768/360, number text, tap target, wrapping, overflow.
// usage: node tools/local/check-phone-layout.mjs <base-url> <label>
import { chromium } from 'playwright-core';
import fs from 'node:fs';
const base = process.argv[2] || 'http://127.0.0.1:8000'; const label = process.argv[3] || 'local';
const out = `footage/_review/phone/${label}`; fs.mkdirSync(out, { recursive: true });
const results = []; const rec = (n, pass, note = '') => { results.push(pass); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
for (const w of [1440, 1024, 768, 360]) {
    const ctx = await browser.newContext({ viewport: { width: w, height: w < 700 ? 800 : 900 }, hasTouch: w < 700 });
    const p = await ctx.newPage();
    await p.goto(base + '/', { waitUntil: 'load', timeout: 90000 }); await p.waitForTimeout(2500);
    if (w < 992) { const t = p.locator('.navbar-toggler').first(); if (await t.isVisible()) { await t.click(); await p.waitForTimeout(800); } }
    const nav = await p.evaluate(() => {
        const links = [...document.querySelectorAll('.nav-contact a[href^="tel:"]')];
        return { visible: links.map((a) => { const r = a.getBoundingClientRect(); return { t: a.textContent.trim(), href: a.getAttribute('href'), w: Math.round(r.width), h: Math.round(r.height), shown: r.width > 0 && getComputedStyle(a.closest('.nav-contact')).display !== 'none', lines: Math.round(r.height / parseFloat(getComputedStyle(a).lineHeight || 20)) }; }), ovf: document.documentElement.scrollWidth - innerWidth };
    });
    await p.screenshot({ path: `${out}/nav-${w}.png`, clip: { x: 0, y: 0, width: w, height: w < 992 ? 700 : 110 } });
    const shown = nav.visible.filter((v) => v.shown);
    rec(`${w}px nav: 2 numbers in the Ethiopian format, tel: clean, one line each, no sideways scroll`, shown.length === 2 && shown.every((v) => /^\+251 \d{3} \d{3} \d{3}$/.test(v.t) && /^tel:\+251\d{9}$/.test(v.href) && v.lines <= 1) && nav.ovf <= 0, JSON.stringify(shown.map((v) => v.t.replace(/ /g, '_') + ' ' + v.w + 'x' + v.h)) + ' ovf=' + nav.ovf);
    await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight)); await p.waitForTimeout(1200);
    const foot = await p.evaluate(() => {
        const a = document.querySelector('footer a[href^="tel:"]'); if (!a) return null; const r = a.getBoundingClientRect(); const f = document.querySelector('footer').getBoundingClientRect();
        const icons = [...document.querySelectorAll('footer .social-icons a, footer .footer-social a, footer [class*="social"] a')].map((i) => i.getBoundingClientRect());
        return { t: a.textContent.trim(), href: a.getAttribute('href'), h: Math.round(r.height), inside: r.left >= 0 && r.right <= innerWidth, iconsOut: icons.filter((i) => i.right > innerWidth + 1 || i.left < -1).length, ovf: document.documentElement.scrollWidth - innerWidth };
    });
    await p.locator('footer').first().screenshot({ path: `${out}/footer-${w}.png` });
    rec(`${w}px footer: number formatted, tappable, inside the screen, social icons inside`, !!foot && /^\+251 \d{3} \d{3} \d{3}$/.test(foot.t) && /^tel:\+251\d{9}$/.test(foot.href) && foot.inside && foot.iconsOut === 0 && foot.ovf <= 0, JSON.stringify(foot));
    await ctx.close();
}
await browser.close();
process.exit(results.every(Boolean) ? 0 : 1);
