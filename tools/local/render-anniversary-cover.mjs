// LOCAL USE ONLY. Renders the 1200x630 cover for the five-year anniversary news article
// from the live ceremony seal (settled gold 5), so the cover matches the site exactly.
// Needs a running site with the campaign active (dates, or ANNIVERSARY_FORCE=true outside production).
//   node tools/local/render-anniversary-cover.mjs http://127.0.0.1:8080
// Writes resources/news/anniversary-five-years-cover.png (the source the news:publish-anniversary command uses).
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const out = path.resolve('resources/news/anniversary-five-years-cover.png');
fs.mkdirSync(path.dirname(out), { recursive: true });

const browser = await chromium.launch();
const ctx = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1200, height: 630 } });
await ctx.route(/tawk\.to|\.(mp4|webm)(\?|$)/i, (r) => r.abort());
const page = await ctx.newPage();

// Take the seal and its styles from the real page.
await page.goto(base + '/services', { waitUntil: 'domcontentloaded' });
const parts = await page.evaluate(() => {
    const svg = document.querySelector('.anniv5-mark');
    const css = [...document.querySelectorAll('style')].map((s) => s.textContent).find((t) => t.includes('.anniv5-mark'));
    return svg && css ? { svg: svg.outerHTML, css } : null;
});
if (!parts) throw new Error('No anniversary seal on /services: is the campaign active on ' + base + '?');

await page.setContent(`<!doctype html><html><head>
<link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400..700&family=Manrope:wght@600..800&display=block" rel="stylesheet">
<style>${parts.css}</style>
<style>
  :root { --font-card-title: 'Bodoni Moda', Georgia, serif; --font-ui: 'Manrope', Arial, sans-serif; }
  html, body { margin: 0; width: 1200px; height: 630px; overflow: hidden; }
  body {
    display: grid; place-items: center; font-synthesis: none;
    background:
      radial-gradient(60% 80% at 50% 38%, rgba(59, 130, 246, .28), transparent 70%),
      radial-gradient(40% 60% at 12% 90%, rgba(34, 211, 238, .16), transparent 70%),
      radial-gradient(40% 60% at 88% 90%, rgba(139, 92, 246, .2), transparent 70%),
      #101A35;
  }
  /* Everything stays inside the centre ~600px: the news cards and featured slot crop the sides. */
  .stage { display: grid; justify-items: center; gap: 22px; max-width: 600px; text-align: center; }
  .anniv5-mark { width: 270px; height: 270px; }
  .title { margin: 0; font-family: 'Bodoni Moda', Georgia, serif; font-optical-sizing: auto; font-weight: 600;
           font-size: 44px; line-height: 1.12; color: #fff; letter-spacing: -.01em; }
  .title b { display: block; color: #F5C451; font-weight: 600; }
  .rule { width: 300px; height: 3px; border-radius: 3px;
          background: linear-gradient(90deg, #22D3EE, #3B82F6, #8B5CF6, #EC4899, #F5C451); }
</style></head><body>
  <div class="anniv5 stage">
    ${parts.svg}
    <p class="title"><b>5 Years</b>Sky Link ICT Solutions</p>
    <div class="rule"></div>
  </div>
</body></html>`, { waitUntil: 'networkidle' });
await page.evaluate(() => document.fonts.ready);
await page.screenshot({ path: out, type: 'png' });
await browser.close();
console.log('Cover written to ' + out);
