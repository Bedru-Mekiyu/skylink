// LOCAL USE ONLY. Submits the real public forms on bedru (contact: general, quote, support; newsletter), then reads the admin inbox.
// Mail goes through the staging guard: only info@skylinkict.com (company inbox + tester) receives anything.
//   node tools/local/staging-mail-check.mjs <visitor-email>
import { chromium } from 'playwright-core';
import fs from 'node:fs';

const visitor = process.argv[2] || 'info@skylinkict.com';
const lang = process.argv[3] === 'am' ? 'am' : 'en';   // am: Ethiopic name and message (the site picks the confirmation language from them) and an Amharic browser language
const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const base = 'https://bedru.skylinkict.com';
const b = await chromium.launch();
const c = await b.newContext({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined, viewport: { width: 390, height: 844 }, locale: lang === 'am' ? 'am-ET' : 'en-US' });
await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
const p = await c.newPage(); p.setDefaultTimeout(120000);
const out = [];
const only = process.argv[4];   // optional: one form type, e.g. quote (skips the newsletter)
for (const type of only ? [only] : ['contact', 'quote', 'support']) {
    await p.goto(base + '/contact', { waitUntil: 'domcontentloaded' });
    await p.waitForTimeout(4500); // the form's time-to-submit guard
    await p.fill('#cf-name', lang === 'am' ? 'ሙከራ ' + type : 'Mail Test ' + type);
    await p.selectOption('#cf-type', type);
    await p.fill('#cf-email', visitor);
    await p.fill('#cf-phone', '+251911000000');
    if (await p.$('#cf-service')) await p.fill('#cf-service', 'Web Development').catch(() => {});
    await p.fill('#cf-message', lang === 'am' ? `የሙከራ መልዕክት (${type}) ${new Date().toISOString()}። እባክዎ ችላ ይበሉ።` : `Staging mail test (${type}) ${new Date().toISOString()}. Please ignore.`);
    await Promise.all([p.waitForNavigation({ waitUntil: 'domcontentloaded' }), p.click('#contactForm button[type=submit]')]);
    const txt = (await p.textContent('body')).replace(/\s+/g, ' ');
    const ref = txt.match(/SK-\d{6}-[A-Z0-9]{5}/);
    out.push({ lang, form: type, url: p.url().replace(base, ''), reference: ref && ref[0], success: /thank|received|sent/i.test(txt) });
    await p.screenshot({ path: `storage/app/staging-checks/final-polish/mail-${type}.png` });
}
// newsletter (home footer)
await p.goto(base + '/', { waitUntil: 'domcontentloaded' });
await p.waitForTimeout(4500);
const nl = only ? null : await p.$('form.newsletter-form');
if (nl) {
    await nl.scrollIntoViewIfNeeded();
    const email = await nl.$('input[type=email]'); await email.fill(visitor);
    const name = await nl.$('input[name=name]'); if (name) await name.fill('Mail Test Newsletter');
    await Promise.all([p.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}), nl.$eval('button[type=submit]', (x) => x.click())]);
    await p.waitForTimeout(1500);
    out.push({ lang, form: 'newsletter', url: p.url().replace(base, ''), success: /subscri|thank/i.test((await p.textContent('body')).replace(/\s+/g, ' ')) });
}
console.log(JSON.stringify(out, null, 1));
await b.close();
