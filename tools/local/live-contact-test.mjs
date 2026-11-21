// ONE clearly labelled test enquiry on the live contact form (deleted afterwards on the server).
import { chromium } from 'playwright-core';
const b = await chromium.launch(); const p = await (await b.newContext()).newPage();
await p.goto('https://skylinkict.com/contact', { waitUntil: 'load', timeout: 60000 });
await p.waitForTimeout(5000); // the form has a minimum fill time
await p.fill('#cf-name', 'GOLIVE TEST - please delete');
await p.selectOption('#cf-type', { index: 1 });
await p.fill('#cf-email', 'golive-test@example.com');
await p.fill('#cf-message', 'GOLIVE TEST - automatic check after go-live, please delete.');
await p.click('#contactForm button[type=submit]');
await p.waitForLoadState('load'); await p.waitForTimeout(2500);
console.log(p.url(), '|', (await p.evaluate(() => document.body.innerText)).match(/thank|success|received|sent|error|wrong/i)?.[0] ?? 'no-message-word');
await b.close();
