import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const adminPw = access.match(/email\s+admin@gmail\.com\s+password\s+(\S+)/)[1];
const base = 'https://bedru.skylinkict.com';
const out = path.resolve('footage/_review');
fs.mkdirSync(out, { recursive: true });

const nap = (ms = 2500) => new Promise((r) => setTimeout(r, ms));
const browser = await chromium.launch();
const ctx = await browser.newContext({
    httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined,
    viewport: { width: 1440, height: 900 }
});
const page = await ctx.newPage();
page.setDefaultTimeout(90000);

const results = [];
function report(name, pass, detail = '') {
    results.push({ name, pass, detail });
    console.log(pass ? '[PASS]' : '[FAIL]', name, detail ? `- ${detail}` : '');
}

console.log('--- 1. HOME PAGE VERIFICATION ---');
await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
await nap(1000);
const marqueeHeading = await page.textContent('.partners-section h5');
report('Home page marquee title text is "Trusted Clients"', marqueeHeading?.trim() === 'Trusted Clients', `Found: "${marqueeHeading?.trim()}"`);

console.log('--- 2. ABOUT PAGE & MARKETING PHOTO VERIFICATION ---');
await page.goto(base + '/about', { waitUntil: 'domcontentloaded' });
await nap(1500);

const aboutInfo = await page.evaluate(() => {
    const cards = [...document.querySelectorAll('.team-card')].map(c => {
        const name = c.querySelector('h3')?.innerText?.trim();
        const role = c.querySelector('p, .team-card__role')?.innerText?.trim();
        const img = c.querySelector('img');
        return {
            name,
            role,
            imgSrc: img ? img.getAttribute('src') : null,
            imgAlt: img ? img.getAttribute('alt') : null,
            naturalWidth: img ? img.naturalWidth : 0,
            naturalHeight: img ? img.naturalHeight : 0,
            complete: img ? img.complete : false
        };
    });
    return cards;
});

const frezerCard = aboutInfo.find(c => c.name && c.name.includes('Frezer Maru'));
report('Frezer Maru card exists on About page', !!frezerCard, JSON.stringify(frezerCard));

if (frezerCard) {
    report('Frezer Maru alt text is "Frezer Maru, Head of Marketing"', frezerCard.imgAlt === 'Frezer Maru, Head of Marketing', frezerCard.imgAlt);
    report('Frezer Maru image loaded with non-zero dimensions', frezerCard.complete && frezerCard.naturalWidth > 0 && frezerCard.naturalHeight > 0, `${frezerCard.naturalWidth}x${frezerCard.naturalHeight}`);
    report('Image ratio is 4:5 (~0.80)', Math.abs((frezerCard.naturalWidth / frezerCard.naturalHeight) - 0.8) < 0.05, (frezerCard.naturalWidth / frezerCard.naturalHeight).toFixed(3));
}

// Test image direct fetch status and size
const imgRes = await fetch(base + '/assets/images/about/head-of-marketing.jpg');
const imgBuffer = await imgRes.arrayBuffer();
const imgSizeKb = Math.round(imgBuffer.byteLength / 1024);
report('Photo returns HTTP 200 and is under 200 KB', imgRes.status === 200 && imgSizeKb < 200, `HTTP ${imgRes.status}, ${imgSizeKb} KB`);

// Test layout across 1440, 1024, 768, 360 px
const viewports = [1440, 1024, 768, 360];
for (const vp of viewports) {
    await page.setViewportSize({ width: vp, height: 800 });
    await nap(1000);
    const layout = await page.evaluate(() => {
        const ceoCard = document.querySelector('.team-card--lead, .ceo-card');
        const normalCards = document.querySelectorAll('.team-card:not(.team-card--lead):not(.ceo-card)');
        const ceoRect = ceoCard ? ceoCard.getBoundingClientRect() : null;
        const firstNormalRect = normalCards[0] ? normalCards[0].getBoundingClientRect() : null;
        return {
            ceoTop: ceoRect ? Math.round(ceoRect.top) : null,
            normalCount: normalCards.length,
            firstNormalTop: firstNormalRect ? Math.round(firstNormalRect.top) : null,
            ceoIsAboveOthers: ceoRect && firstNormalRect ? ceoRect.bottom <= firstNormalRect.top : false
        };
    });
    report(`Team layout at ${vp}px: CEO on top, four below`, layout.normalCount === 4 && layout.ceoIsAboveOthers, JSON.stringify(layout));
}

// Take screenshot of team section
await page.setViewportSize({ width: 1440, height: 900 });
const teamSection = page.locator('#team, .team-section, .about-team').first();
if (await teamSection.count()) {
    await teamSection.scrollIntoViewIfNeeded();
    await nap(1000);
    await teamSection.screenshot({ path: path.join(out, 'bedru-team-frezer-maru.png') });
    report('Saved team section screenshot', true, 'footage/_review/bedru-team-frezer-maru.png');
}

console.log('--- 3. ADMIN-CONTROLLED PRICE VISIBILITY TOGGLE TEST ---');
// First, discover a service category URL from /services
await page.goto(base + '/services', { waitUntil: 'domcontentloaded' });
await nap(1000);
const categoryLink = await page.evaluate(() => {
    const a = document.querySelector('a[href*="/services/category/"]');
    return a ? a.getAttribute('href') : null;
});
console.log('Discovered service category link:', categoryLink);

// Log into admin
await page.goto(base + '/login', { waitUntil: 'domcontentloaded' });
await page.waitForSelector('input[name=email]');
await page.fill('input[name=email]', 'admin@gmail.com');
await page.fill('input[name=password]', adminPw);
await Promise.all([
    page.waitForNavigation({ timeout: 90000 }),
    page.click('button[type=submit]')
]);
report('Admin login on Bedru successful', page.url().includes('/admin/'));

// Navigate to Settings
await page.goto(base + '/admin/settings', { waitUntil: 'domcontentloaded' });
await nap(1500);
const toggleInitial = await page.isChecked('#show_prices');
report('Global switch initial state is ON', toggleInitial === true, `checked: ${toggleInitial}`);

const pageCtx = await browser.newContext({
    httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined,
    viewport: { width: 1440, height: 900 }
});
const pubPage = await pageCtx.newPage();

// Verify prices visible initially when switch is ON
if (categoryLink) {
    const targetUrl = categoryLink.startsWith('http') ? categoryLink : base + categoryLink;
    await pubPage.goto(targetUrl, { waitUntil: 'domcontentloaded' });
    await nap(1000);
    const catHtml = await pubPage.content();
    report('Service category page shows price when global switch is ON', catHtml.includes('Price:'));
}
await pubPage.goto(base + '/products', { waitUntil: 'domcontentloaded' });
await nap(1000);
const prodHtml = await pubPage.content();
report('Products page shows prices when global switch is ON', prodHtml.includes('ETB ') || prodHtml.includes('From '));

// Test TOGGLE OFF
console.log('Toggling price visibility OFF...');
await page.goto(base + '/admin/settings', { waitUntil: 'domcontentloaded' });
await nap(1000);
await page.uncheck('#show_prices');
await Promise.all([
    page.waitForNavigation({ timeout: 90000 }),
    page.click('button[type=submit]')
]);
await nap(1500);
const toggleAfterOff = await page.isChecked('#show_prices');
report('Settings saved: toggle is now OFF', toggleAfterOff === false, `checked: ${toggleAfterOff}`);

// Check public pages with switch OFF
if (categoryLink) {
    const targetUrl = categoryLink.startsWith('http') ? categoryLink : base + categoryLink;
    await pubPage.goto(targetUrl, { waitUntil: 'domcontentloaded' });
    await nap(1000);
    const catHtmlOff = await pubPage.content();
    report('Service category page hides price completely (no Price:, no Price: N/A) when switch is OFF', !catHtmlOff.includes('Price:'));
}

await pubPage.goto(base + '/products', { waitUntil: 'domcontentloaded' });
await nap(1000);
const productsHtmlOff = await pubPage.content();
report('Products page hides prices when switch is OFF', !productsHtmlOff.includes('ETB ') && !productsHtmlOff.includes('From '));

// Test TOGGLE ON
console.log('Toggling price visibility back ON...');
await page.goto(base + '/admin/settings', { waitUntil: 'domcontentloaded' });
await nap(1000);
await page.check('#show_prices');
await Promise.all([
    page.waitForNavigation({ timeout: 90000 }),
    page.click('button[type=submit]')
]);
await nap(1500);
const toggleAfterOn = await page.isChecked('#show_prices');
report('Settings saved: toggle is now back ON', toggleAfterOn === true, `checked: ${toggleAfterOn}`);

// Check public services and products pages with switch ON again
if (categoryLink) {
    const targetUrl = categoryLink.startsWith('http') ? categoryLink : base + categoryLink;
    await pubPage.goto(targetUrl, { waitUntil: 'domcontentloaded' });
    await nap(1000);
    const catHtmlOn = await pubPage.content();
    report('Service category page shows price again when switch is back ON', catHtmlOn.includes('Price:'));
}

await pubPage.goto(base + '/products', { waitUntil: 'domcontentloaded' });
await nap(1000);
const productsHtmlOn = await pubPage.content();
report('Products page shows prices again when switch is back ON', productsHtmlOn.includes('ETB '));

await pubPage.close();
await pageCtx.close();

console.log('--- 4. PUBLIC PAGES HTTP 200 SPOT CHECK ---');
const routes = ['/', '/about', '/news', '/contact', '/services', '/products'];
for (const r of routes) {
    const res = await fetch(base + r, {
        headers: basicPw ? { Authorization: 'Basic ' + Buffer.from('staging:' + basicPw).toString('base64') } : {}
    });
    report(`Page ${r} returns HTTP 200`, res.status === 200, `HTTP ${res.status}`);
}

await browser.close();

const total = results.length;
const passed = results.filter(r => r.pass).length;
console.log(`\n========================================`);
console.log(`BEDRU VERIFICATION COMPLETE: ${passed}/${total} passed`);
console.log(`========================================\n`);
