// LOCAL USE ONLY. Lighthouse (mobile) on bedru pages; saves JSON + a one-line summary per page.
//   node tools/local/staging-lighthouse.mjs <outDir> [paths...]      default paths: / /news /products /contact
// Uses the bedru basic-auth password from deploy/STAGING-ACCESS.txt (never printed).
import { chromium } from 'playwright-core';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';

const [outDir, ...rest] = process.argv.slice(2);
if (!outDir) { console.error('usage: node tools/local/staging-lighthouse.mjs <outDir> [paths...]'); process.exit(1); }
const paths = rest.length ? rest : ['/', '/news', '/products', '/contact'];
const base = (process.env.LH_BASE || 'https://bedru.skylinkict.com').replace(/\/$/, '');
const access = fs.readFileSync('deploy/STAGING-ACCESS.txt', 'utf8');
const pw = (access.match(/user\s+staging\s+password\s+(\S+)/) || [])[1];
const headers = path.join(os.tmpdir(), 'lh-headers.json');
fs.writeFileSync(headers, JSON.stringify(pw ? { Authorization: 'Basic ' + Buffer.from('staging:' + pw).toString('base64') } : {}));
fs.mkdirSync(outDir, { recursive: true });
const chrome = chromium.executablePath();
for (const p of paths) {
    const name = p === '/' ? 'home' : p.replace(/^\//, '').replace(/\W+/g, '-');
    const out = path.join(outDir, name + '.json');
    const r = spawnSync('npx', ['--yes', 'lighthouse@12.6.1', base + p, '--output=json', '--output-path=' + out, '--only-categories=performance,accessibility,best-practices,seo',
        '--form-factor=mobile', '--chrome-flags=--headless=new --no-sandbox', '--extra-headers=' + headers, '--quiet'], { shell: true, env: { ...process.env, CHROME_PATH: chrome }, encoding: 'utf8', timeout: 400000 });
    if (!fs.existsSync(out)) { console.log(name.padEnd(9), 'FAILED', (r.stderr || '').split('\n').slice(-3).join(' ').slice(0, 200)); continue; }
    const j = JSON.parse(fs.readFileSync(out, 'utf8')); const a = j.audits; const c = j.categories;
    console.log(name.padEnd(9), `perf ${Math.round(c.performance.score * 100)}  a11y ${Math.round(c.accessibility.score * 100)}  bp ${Math.round(c['best-practices'].score * 100)}  seo ${Math.round(c.seo.score * 100)}  LCP ${(a['largest-contentful-paint'].numericValue / 1000).toFixed(1)}s  CLS ${a['cumulative-layout-shift'].numericValue.toFixed(3)}  TBT ${Math.round(a['total-blocking-time'].numericValue)}ms  bytes ${(a['total-byte-weight'].numericValue / 1024).toFixed(0)}KB`);
}
fs.rmSync(headers, { force: true });
