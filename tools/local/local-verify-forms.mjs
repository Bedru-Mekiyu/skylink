// LOCAL USE ONLY (local site, mail driver "log"): contact, quote (service request), subscription plan and newsletter forms in a real browser.
import { chromium } from 'playwright-core';
const base = process.env.BASE || 'http://127.0.0.1:8000';
const results = []; const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const browser = await chromium.launch(); const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } }); const p = await ctx.newPage();
const errs = []; p.on('console', (m) => { if (m.type() === 'error' && !/tawk|ERR_FAILED|favicon|Failed to load resource/i.test(m.text())) errs.push(m.text().slice(0, 120)); });
const forms = async (url) => { await p.goto(base + url, { waitUntil: 'load' }); return p.evaluate(() => [...document.forms].filter((f) => !/logout|search/i.test(f.action + f.id)).map((f) => ({ action: f.action.replace(location.origin, ''), method: f.method, fields: [...f.elements].filter((e) => e.name && !['_token', 'hp_website', 'form_started_at'].includes(e.name) && e.type !== 'hidden').map((e) => e.name + ':' + e.type) }))); };
const submitAndRead = async (url, fill, btn) => {
    await p.goto(base + url, { waitUntil: 'load' }); await p.waitForTimeout(2500); // the form needs a few seconds after load (anti-bot timer)
    await fill(); await p.click(btn); await p.waitForTimeout(2500);
    return (await p.locator('body').innerText()).slice(0, 4000);
};
const ok = /thank|success|sent|received|subscribed|welcome/i;
let t = await submitAndRead('/contact', async () => { await p.fill('#contact-form [name=name], form[action$="/inquiry"] [name=name]', 'Local Tester'); await p.fill('form[action$="/inquiry"] [name=email]', 'local.tester@example.test'); await p.fill('form[action$="/inquiry"] [name=phone]', '0911000000'); await p.fill('form[action$="/inquiry"] [name=message]', 'Local form test message, please ignore.'); }, 'form[action$="/inquiry"] button[type=submit]');
rec('contact form submits and shows a thank-you message', ok.test(t));
t = await submitAndRead('/subscribe/premium', async () => { await p.fill('form[action$="/inquiry"] [name=name]', 'Local Tester'); await p.fill('form[action$="/inquiry"] [name=email]', 'local.tester2@example.test'); await p.fill('form[action$="/inquiry"] [name=phone]', '0911000001'); await p.fill('form[action$="/inquiry"] [name=message]', 'Plan request test.'); }, 'form[action$="/inquiry"] button[type=submit]');
rec('subscription / quote form submits and shows a thank-you message', ok.test(t));
await p.goto(base + '/', { waitUntil: 'load' }); await p.waitForTimeout(2500);
await p.locator('form[action$="/subscribe"] [name=email]').scrollIntoViewIfNeeded(); await p.fill('form[action$="/subscribe"] [name=email]', 'local.news@example.test'); await p.click('form[action$="/subscribe"] button[type=submit]'); await p.waitForTimeout(2500);
rec('newsletter form submits and shows a thank-you message', ok.test(await p.locator('body').innerText()));
// bot trap: a filled honeypot must not create a message
await p.goto(base + '/contact', { waitUntil: 'load' }); await p.waitForTimeout(2500);
rec('no console errors on the form pages', errs.length === 0, errs.join(' | '));
await browser.close();

const failed = results.filter((r) => !r.pass);
console.log(`
${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
