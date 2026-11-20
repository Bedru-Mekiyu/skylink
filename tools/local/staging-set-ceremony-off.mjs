// LOCAL USE ONLY. Saves the Anniversary Ceremony as Off on the password-protected test site (login from deploy/STAGING-ACCESS.txt).
import { chromium } from 'playwright-core';
import fs from 'node:fs';
const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const b = await chromium.launch();
const c = await b.newContext({ httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined });
await c.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
const p = await c.newPage(); p.setDefaultTimeout(120000);
await p.goto(base + '/login', { waitUntil: 'domcontentloaded' });
await p.fill('input[name=email]', 'admin@gmail.com'); await p.fill('input[name=password]', adminPw);
await Promise.all([p.waitForNavigation(), p.click('button[type=submit]')]);
await p.goto(base + '/admin/anniversary', { waitUntil: 'domcontentloaded' });
await p.check('#mode-off'); await p.uncheck('#admin_preview');
await Promise.all([p.waitForNavigation(), p.click('#anniversary-form button[type=submit]')]);
console.log((await p.textContent('#anniversary-status')).trim().replace(/\s+/g, ' '));
await b.close();
