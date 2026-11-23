import { chromium } from 'playwright-core';
import fs from 'node:fs';

const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const basicPw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const base = 'https://bedru.skylinkict.com';

const browser = await chromium.launch();
const ctx = await browser.newContext({
    httpCredentials: basicPw ? { username: 'staging', password: basicPw } : undefined,
    viewport: { width: 1440, height: 900 },
    reducedMotion: 'reduce'
});
const page = await ctx.newPage();

await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
await page.waitForTimeout(2000);

const logos = await page.evaluate(() => {
    const list = [...document.querySelectorAll('.partners-track img.partner-logo')];
    // deduplicate by src
    const seen = new Set();
    const unique = [];
    for (const img of list) {
        if (!seen.has(img.src)) {
            seen.add(img.src);
            const style = window.getComputedStyle(img);
            const rect = img.getBoundingClientRect();
            unique.push({
                src: img.src,
                alt: img.alt,
                naturalWidth: img.naturalWidth,
                naturalHeight: img.naturalHeight,
                renderedWidth: Math.round(rect.width),
                renderedHeight: Math.round(rect.height),
                objectFit: style.objectFit,
                borderRadius: style.borderRadius,
                complete: img.complete
            });
        }
    }
    return unique;
});

console.log(`Audited ${logos.length} unique logos rendered in the marquee:`);

const issues = [];
for (const l of logos) {
    const naturalRatio = l.naturalHeight > 0 ? (l.naturalWidth / l.naturalHeight).toFixed(2) : 'N/A';
    const renderedRatio = l.renderedHeight > 0 ? (l.renderedWidth / l.renderedHeight).toFixed(2) : 'N/A';
    
    // Check if rendered ratio deviates significantly from natural ratio (stretching)
    const isStretched = (l.naturalHeight > 0 && l.renderedHeight > 0) 
        ? Math.abs(parseFloat(naturalRatio) - parseFloat(renderedRatio)) > 0.1 
        : false;
        
    const isSmall = l.renderedWidth < 30 || l.renderedHeight < 30;
    const isCircular = l.borderRadius === '50%' || parseFloat(l.borderRadius) > 10;
    
    if (isCircular || isStretched || !l.complete) {
        issues.push({ logo: l.alt || l.src, isCircular, isStretched, complete: l.complete });
    }
    
    console.log(`- [${l.alt || 'No alt'}]: natural=${l.naturalWidth}x${l.naturalHeight} (ratio ${naturalRatio}), rendered=${l.renderedWidth}x${l.renderedHeight} (ratio ${renderedRatio}), objectFit=${l.objectFit}, radius=${l.borderRadius}`);
}

console.log('\n--- ISSUES FOUND ---');
if (issues.length === 0) {
    console.log('No cropping, stretching, or forced circle issues found on any logo!');
} else {
    console.log(JSON.stringify(issues, null, 2));
}

await browser.close();
