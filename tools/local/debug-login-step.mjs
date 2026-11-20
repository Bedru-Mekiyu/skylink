// LOCAL USE ONLY. After a failed login on the bedru test site, what does /login show? (debugging the check script)
import { chromium } from 'playwright-core';
import fs from 'node:fs';
const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const b = await chromium.launch();
const c = await b.newContext({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined });
await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
const p = await c.newPage(); p.setDefaultTimeout(40000);
p.on('response', (r) => { if (/\/login/.test(r.url())) console.log('  ', r.request().method(), r.status(), r.url().replace(base, '')); });
await p.goto(base + '/login', { waitUntil: 'domcontentloaded' });
await p.fill('input[name=email]', 'admin@gmail.com'); await p.fill('input[name=password]', adminPw);
await Promise.all([p.waitForNavigation().catch(() => {}), p.click('button[type=submit]')]);
console.log('after failed login ->', p.url().replace(base, ''), '| email input present:', !!(await p.$('input[name=email]')));
await p.goto(base + '/login', { waitUntil: 'domcontentloaded' });
console.log('second /login ->', p.url().replace(base, ''), '| email input present:', !!(await p.$('input[name=email]')), '| text:', (await p.locator('body').innerText()).replace(/\s+/g, ' ').slice(0, 160));
await b.close();
