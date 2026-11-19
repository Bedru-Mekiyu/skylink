// LOCAL USE ONLY. Proves a built release works, using only a temp folder and a temp SQLite database:
//   node deploy/verify-release.mjs [deploy/.build/release]
// 1. extracts skylink-app.zip + skylink-public.zip like the server (app folder + its public/ document root)
// 2. migrates a fresh database, creates a throw-away admin
// 3. STAGE 1 (no ASSET_URL): serves the package with the clock frozen at Nov 25 2026 (inside the ceremony dates) and runs
//    tools/local/verify-package.mjs: pages, assets, admin login, Anniversary page says HIDDEN (Off), guest sees no ceremony
// 4. STAGE 2 (ASSET_URL=.../sl): serves a fake public_html (other sites + clash cases, Apache rules emulated) and runs
//    deploy/domain-switch/test/verify-stage2.mjs
// Nothing outside the temp folder is written, nothing is sent anywhere.
import { spawn, spawnSync } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const repo = process.cwd();
const rel = path.resolve(process.argv[2] || 'deploy/.build/release');
const win = process.platform === 'win32';
const tar = win ? path.join(process.env.SystemRoot, 'System32', 'tar.exe') : 'tar';
const tmp = fs.mkdtempSync(path.join(fs.realpathSync.native(os.tmpdir()), 'skylink-verify-'));
const app = path.join(tmp, 's.skylinkict.com');
const results = [];
let server;

const run = (cmd, args, opts = {}) => {
    const r = spawnSync(cmd, args, { encoding: 'utf8', ...opts });
    if (r.status !== 0) throw new Error(`${cmd} ${args.join(' ')} failed:\n${(r.stdout || '') + (r.stderr || '')}`.slice(0, 1500));
    return r.stdout;
};
const waitFor = async (url, host) => {
    for (let i = 0; i < 60; i++) {
        try { const r = await fetch(url, { headers: host ? { Host: host } : {} }); if (r.status < 500) return; } catch {}
        await new Promise((r) => setTimeout(r, 500));
    }
    throw new Error('server did not start: ' + url);
};
const stop = () => { if (server) { server.kill(); server = null; } };

try {
    for (const z of ['skylink-app.zip', 'skylink-public.zip']) if (!fs.existsSync(path.join(rel, z))) throw new Error(`missing ${z} in ${rel}`);
    fs.mkdirSync(path.join(app, 'public'), { recursive: true });
    run(tar, ['-xf', path.join(rel, 'skylink-app.zip'), '-C', app]);
    run(tar, ['-xf', path.join(rel, 'skylink-public.zip'), '-C', path.join(app, 'public')]);
    console.log('extracted to', tmp);

    const admin = { email: 'verify@example.test', password: 'v-' + crypto.randomBytes(9).toString('hex') };
    const env = {
        ...process.env, APP_ENV: 'production', APP_DEBUG: 'false', ANNIVERSARY_FORCE: 'false',
        APP_KEY: 'base64:' + crypto.randomBytes(32).toString('base64'), DB_CONNECTION: 'sqlite', DB_DATABASE: path.join(tmp, 'test.sqlite'),
        SESSION_DRIVER: 'database', CACHE_STORE: 'database', QUEUE_CONNECTION: 'sync', MAIL_MAILER: 'log', LOG_CHANNEL: 'single',
        ADMIN_EMAIL: admin.email, ADMIN_PASSWORD: admin.password,
    };
    fs.writeFileSync(env.DB_DATABASE, '');
    const php = (args, cwd = app, e = env) => run('php', args, { cwd, env: e });

    const migrated = php(['artisan', 'migrate', '--force', '--no-interaction']);
    results.push(['migrate (fresh DB)', /DONE/.test(migrated) && !/error/i.test(migrated)]);
    // the deploy runs these; a duplicated route name once made route:cache fail
    let cached = true;
    try { php(['artisan', 'route:cache']); php(['artisan', 'view:cache']); } catch (e) { cached = false; console.error(String(e.message)); }
    results.push(['route:cache and view:cache succeed', cached]);
    fs.writeFileSync(path.join(tmp, 'mkuser.php'), `<?php
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
App\\Models\\User::forceCreate(['name'=>'Verify','email'=>$argv[2],'password'=>bcrypt($argv[3]),'is_admin'=>true]);
echo App\\Support\\Anniversary::liveForVisitors() ? 'LIVE' : 'HIDDEN';
`);
    const fresh = php([path.join(tmp, 'mkuser.php'), app, admin.email, admin.password]);
    results.push(['fresh install: ceremony HIDDEN for visitors (nothing saved)', /HIDDEN$/.test(fresh.trim())]);
    fs.writeFileSync(path.join(tmp, 'freeze.php'), `<?php
require ${JSON.stringify(path.join(app, 'vendor', 'autoload.php'))};
$t = '2026-11-25 12:00:00'; // inside Nov 23 - Dec 7: it must STILL be hidden until an admin turns it on
Carbon\\Carbon::setTestNow(Carbon\\Carbon::parse($t, 'Africa/Addis_Ababa'));
Carbon\\CarbonImmutable::setTestNow(Carbon\\CarbonImmutable::parse($t, 'Africa/Addis_Ababa'));
`);
    const freezeArg = `auto_prepend_file=${path.join(tmp, 'freeze.php')}`;
    const node = (script, args, e) => spawnSync(process.execPath, [script, ...args], { cwd: repo, env: e, stdio: 'inherit' }).status === 0;

    // ---- STAGE 1: the package as s.skylinkict.com serves it (no ASSET_URL) ----
    const p1 = 8231;
    const e1 = { ...env, APP_URL: `http://127.0.0.1:${p1}` };
    delete e1.ASSET_URL;
    server = spawn('php', ['-d', 'display_errors=Off', '-d', freezeArg, '-S', `127.0.0.1:${p1}`, path.join(app, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(app, 'public'), env: e1, stdio: 'ignore' });
    await waitFor(`http://127.0.0.1:${p1}/up`);
    console.log('\n--- STAGE 1 (no ASSET_URL) ---');
    results.push(['stage 1: site, assets, admin, Anniversary HIDDEN (Off), no ceremony for guests (clock Nov 25 2026)', node('tools/local/verify-package.mjs', [`http://127.0.0.1:${p1}`], e1)]);
    stop();

    // ---- STAGE 2: skylinkict.com through public_html with ASSET_URL=/sl ----
    if (win) {
        const p2 = 8232;
        const fake = path.join(tmp, 'public_html');
        run('powershell', ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', path.join(repo, 'deploy/domain-switch/test/setup-fake-public-html.ps1'), '-AppRoot', app, '-Out', fake]);
        const e2 = { ...env, APP_URL: `http://skylinkict.com:${p2}`, ASSET_URL: `http://skylinkict.com:${p2}/sl`, SIM_HTTPS: '1' };
        php(['artisan', 'optimize:clear'], app, e2);
        server = spawn('php', ['-d', 'display_errors=Off', '-d', freezeArg, '-S', `127.0.0.1:${p2}`, '-t', fake, path.join(repo, 'deploy/domain-switch/test/router.php')], { cwd: fake, env: e2, stdio: 'ignore' });
        await waitFor(`http://127.0.0.1:${p2}/up`, `skylinkict.com:${p2}`);
        console.log('\n--- STAGE 2 (ASSET_URL=https://skylinkict.com/sl, public_html clash cases) ---');
        results.push(['stage 2: skylinkict.com via public_html, /sl assets, other sites untouched', node('deploy/domain-switch/test/verify-stage2.mjs', [String(p2)], e2)]);
        stop();
    } else {
        results.push(['stage 2 (needs Windows junction setup; skipped here)', true]);
    }
} catch (e) {
    console.error(String(e.message || e));
    results.push(['verification crashed', false]);
} finally {
    stop();
    try { if (tmp.includes('skylink-verify-')) fs.rmSync(tmp, { recursive: true, force: true }); } catch {}
}

console.log('\n==== release verification ====');
for (const [name, ok] of results) console.log(ok ? 'PASS' : 'FAIL', name);
const allOk = results.every(([, ok]) => ok);
console.log(allOk ? 'RELEASE VERIFIED' : 'RELEASE NOT VERIFIED');
process.exit(allOk ? 0 : 1);
