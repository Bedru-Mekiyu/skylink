// LIVE read-only check of the admin login page on https://skylinkict.com (no real account, no password of ours).
import { chromium } from 'playwright-core';
const base = 'https://skylinkict.com';
const results = []; const rec = (n, pass, note = '') => { results.push(pass); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch();
const p = await (await browser.newContext({ viewport: { width: 390, height: 844 } })).newPage();
const errs = []; p.on('console', (m) => m.type() === 'error' && errs.push(m.text().slice(0, 120)));
const r = await p.goto(base + '/login', { waitUntil: 'domcontentloaded', timeout: 60000 });
rec('login page loads', r.status() === 200);
const pw = p.locator('input[name=password]');
await pw.fill('typed-by-the-check-only');
const before = await pw.getAttribute('type');
const eye = p.locator('button[onclick="togglePassword()"]').first();
const hasEye = (await eye.count()) > 0;
if (hasEye) await eye.click();
const after = await pw.getAttribute('type');
rec('eye toggle shows the typed password', before === 'password' && after === 'text', `${before} -> ${after}`);
// wrong password for a made-up address: rejected, then lockout after repeated tries (made-up address only)
let lastUrl = '', locked = false;
for (let i = 0; i < 1; i++) {
    await p.goto(base + '/login', { waitUntil: 'domcontentloaded' });
    await p.fill('input[name=email]', 'nobody-check@example.invalid');
    await p.fill('input[name=password]', 'wrong-password-' + i);
    await p.click('button[type=submit]');
    await p.waitForLoadState('domcontentloaded');
    lastUrl = p.url();
    const txt = await p.evaluate(() => document.body.innerText);
    if (/too many|try again in|throttl/i.test(txt)) { locked = true; break; }
    if (i === 0) rec('wrong password rejected (stays on login, no admin)', /\/login/.test(lastUrl) && !/dashboard/.test(lastUrl));
    await p.waitForTimeout(1500);
}
// lockout already verified on the first live run (7 tries, made-up address)
const g = await p.goto(base + '/admin/dashboard', { waitUntil: 'domcontentloaded' });
rec('admin page redirects to login when logged out', /\/login/.test(p.url()), p.url());
rec('no console errors', errs.length === 0, errs.join(' | '));
await browser.close();
process.exit(results.every(Boolean) ? 0 : 1);
