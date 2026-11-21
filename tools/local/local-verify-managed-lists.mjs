// LOCAL USE ONLY (local sqlite site). Testimonials strip (1/3/6/20, long + Amharic) and department cards (1,2,3,4,5,7) in a real browser.
import { chromium } from 'playwright-core';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const base = 'http://127.0.0.1:8000';
const php = process.env.USERPROFILE + '/.config/herd/bin/php84/php.exe';
const out = path.resolve('footage/_review/managed-lists');
fs.mkdirSync(out, { recursive: true });
const guard = spawnSync(php, ['tools/local/guard.php'], { encoding: 'utf8' });
if (guard.status !== 0) { console.error('refusing: not the local database'); process.exit(2); }
const tinker = (code) => spawnSync(php, ['artisan', 'tinker', '--execute=' + code], { encoding: 'utf8' }).stdout.trim().split('\n').pop();
const results = []; const rec = (n, pass, note = '') => { results.push({ n, pass }); console.log(pass ? 'ok  ' : 'FAIL', n, note ? '- ' + note : ''); };
const backupT = tinker('echo json_encode(App\\Models\\Testimonial::all()->toArray());');
const backupD = tinker('echo json_encode(App\\Models\\Department::all()->toArray());');
const browser = await chromium.launch();
const sizes = [[320, 640], [390, 844], [768, 1024], [1440, 900], [1920, 1080]];

for (const n of [1, 3, 6, 20]) {
    tinker(`App\\Models\\Testimonial::query()->delete(); for($i=1;$i<=${n};$i++){ App\\Models\\Testimonial::create(["name"=>"Client $i","role"=>"CUSTOMER","quote_en"=>$i==2?str_repeat("A very long and detailed praise for the work. ",12):"Short quote $i","quote_am"=>$i==3?"በጣም ጥሩ አገልግሎት ነው፣ ሥራቸው ትክክለኛ ነው":null,"sort_order"=>$i,"visible"=>true]); } echo "ok";`);
    for (const [w, h] of sizes) {
        const p = await (await browser.newContext({ viewport: { width: w, height: h } })).newPage();
        await p.goto(base + '/', { waitUntil: 'load' }); await p.addStyleTag({ content: '.anniv5-intro{display:none!important}' });
        await p.locator('#testimonials').scrollIntoViewIfNeeded(); await p.waitForTimeout(1200);
        const m = await p.evaluate(() => { const t = document.getElementById('carouselTrack'), c = t.querySelectorAll('.testimonial-card'); const cs = getComputedStyle(t); return { ovf: document.documentElement.scrollWidth - innerWidth, cards: c.length, anim: cs.animationName, w: Math.round(t.getBoundingClientRect().width), h: Math.round(t.getBoundingClientRect().height) }; });
        rec(`testimonials n=${n} @${w}: no sideways scroll, loop animation, track ${m.w}x${m.h}`, m.ovf <= 0 && /tsLoop/.test(m.anim) && m.cards >= n * 2, JSON.stringify(m));
        if (w === 1440 || w === 390) await p.locator('#testimonials').screenshot({ path: path.join(out, `testimonials-${n}-${w}.png`) });
        await p.close();
    }
}
tinker('App\\Models\\Testimonial::query()->delete(); echo "x";');
for (const [w, h] of [[1440, 900]]) {
    const p = await (await browser.newContext({ viewport: { width: w, height: h }, reducedMotion: 'reduce' })).newPage();
    await p.goto(base + '/', { waitUntil: 'load' });
    rec('no testimonial: the section is gone (home)', (await p.locator('#testimonials').count()) === 0);
    await p.close();
}
for (const n of [1, 2, 3, 4, 5, 7]) {
    tinker(`App\\Models\\Department::query()->delete(); for($i=1;$i<=${n};$i++){ App\\Models\\Department::create(["name"=>"Department $i","tagline"=>"Tagline $i","icon"=>"fas fa-code","image_path"=>$i%2?"assets/images/about/marketing.jpg":null,"image_label"=>"Label $i","stats"=>[["n"=>"1$i+","l"=>"Alpha"],["n"=>"2$i+","l"=>"Beta"]],"sort_order"=>$i,"visible"=>true,"accent"=>["primary","info","success","warning"][$i%4]]); } echo "ok";`);
    for (const [w, h] of sizes) {
        const p = await (await browser.newContext({ viewport: { width: w, height: h }, reducedMotion: 'reduce' })).newPage();
        await p.goto(base + '/about', { waitUntil: 'load' }); await p.addStyleTag({ content: '.anniv5-intro{display:none!important} [data-aos]{opacity:1!important;transform:none!important}' });
        await p.locator('#departments').scrollIntoViewIfNeeded(); await p.waitForTimeout(1000);
        const m = await p.evaluate(() => { const cards = [...document.querySelectorAll('#departments .team-showcase-card')]; const r = cards.map((c) => c.getBoundingClientRect()); const rows = [...new Set(r.map((x) => Math.round(x.top)))].length; const perRow = {}; r.forEach((x) => { const k = Math.round(x.top); perRow[k] = (perRow[k] || 0) + 1; }); const widths = [...new Set(r.map((x) => Math.round(x.width)))]; const last = r[r.length - 1]; const vw = innerWidth; return { n: cards.length, rows, perRow: Object.values(perRow).join('+'), equalWidth: widths.length === 1, lastCentered: Math.abs((last.left + last.right) / 2 - vw / 2) < 6, ovf: document.documentElement.scrollWidth - vw }; });
        const exp = w >= 768 ? Math.ceil(n / 2) : n;
        rec(`departments n=${n} @${w}: ${m.perRow} per row (rows ${m.rows}), equal widths, no overflow${n % 2 && w >= 768 ? ', last centred' : ''}`, m.n === n && m.rows === exp && m.equalWidth && m.ovf <= 0 && (!(n % 2 && w >= 768) || m.lastCentered), JSON.stringify(m));
        if (w === 1440 || w === 390) await p.locator('#departments').screenshot({ path: path.join(out, `departments-${n}-${w}.png`) });
        await p.close();
    }
}
// restore the original data
tinker('App\\Models\\Department::query()->delete(); echo "x";');
tinker(`foreach (json_decode('${backupD.replace(/'/g, "\\'")}', true) as $r) { unset($r["created_at"], $r["updated_at"]); App\\Models\\Department::create($r); } echo "d";`);
tinker(`foreach (json_decode('${backupT.replace(/'/g, "\\'")}', true) as $r) { unset($r["created_at"], $r["updated_at"]); App\\Models\\Testimonial::create($r); } echo "t";`);
await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
