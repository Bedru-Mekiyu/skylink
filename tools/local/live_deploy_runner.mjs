import { spawn } from 'node:child_process';
import path from 'node:path';
import fs from 'node:fs';

const SSH_KEY = 'C:\\Users\\AFRO-EAGLE\\.ssh\\cpanel_bedru';
const SSH_PORT = '22';
const SSH_HOST = '91.204.209.13';
const SSH_USER = 'skylinkictcom';
const REMOTE_APP = '/home/skylinkictcom/s.skylinkict.com';

function runProcess(cmd, args, stdinData = null) {
    return new Promise((resolve, reject) => {
        console.log(`[EXEC] ${cmd} ${args.join(' ')}`);
        const proc = spawn(cmd, args, { stdio: ['pipe', 'pipe', 'pipe'] });
        let stdout = '';
        let stderr = '';

        proc.stdout.on('data', d => {
            const str = d.toString();
            stdout += str;
            process.stdout.write(str);
        });

        proc.stderr.on('data', d => {
            const str = d.toString();
            stderr += str;
            process.stderr.write(str);
        });

        proc.on('close', code => {
            if (code === 0) {
                resolve({ stdout, stderr, code });
            } else {
                reject(new Error(`Command exited with code ${code}\nSTDERR: ${stderr}`));
            }
        });

        if (stdinData) {
            proc.stdin.write(stdinData);
            proc.stdin.end();
        }
    });
}

async function main() {
    console.log('====================================================');
    console.log('STARTING PRODUCTION DEPLOYMENT TO https://skylinkict.com');
    console.log('====================================================');

    const releaseDir = path.resolve('deploy/.build/release');
    const filesToUpload = [
        'skylink-app.zip',
        'skylink-app.zip.sha256',
        'skylink-public.zip',
        'skylink-public.zip.sha256',
        'RELEASE.txt',
        'server-deploy.sh',
        'server-rollback.sh',
        'server-common.sh'
    ];

    console.log('\n--- Step 1: Uploading release files to live deploy/ ---');
    for (const f of filesToUpload) {
        const localPath = path.join(releaseDir, f);
        const remoteTarget = `${SSH_USER}@${SSH_HOST}:${REMOTE_APP}/deploy/${f}`;
        console.log(`Uploading ${f}...`);
        await runProcess('scp', ['-i', SSH_KEY, '-P', SSH_PORT, localPath, remoteTarget]);
    }
    console.log('All release files uploaded successfully.\n');

    console.log('\n--- Step 2: Executing server-deploy.sh on Live ---');
    const deployScript = `
cd "${REMOTE_APP}"
echo yes | bash deploy/server-deploy.sh
`;
    await runProcess('ssh', ['-i', SSH_KEY, '-p', SSH_PORT, `${SSH_USER}@${SSH_HOST}`, deployScript]);
    console.log('server-deploy.sh completed successfully.\n');

    console.log('\n--- Step 3: Updating .env and rebuilding cache on Live ---');
    const envUpdateScript = `
cd "${REMOTE_APP}"
# Replace TEAM_SHOW_PLACEHOLDER=true with TEAM_SHOW_PLACEHOLDER=false (keeping the line)
sed -i 's/^TEAM_SHOW_PLACEHOLDER=.*/TEAM_SHOW_PLACEHOLDER=false/' .env
grep TEAM_SHOW_PLACEHOLDER .env
php artisan config:cache
php artisan route:cache
php artisan view:cache
`;
    await runProcess('ssh', ['-i', SSH_KEY, '-p', SSH_PORT, `${SSH_USER}@${SSH_HOST}`, envUpdateScript]);
    console.log('.env updated and caches rebuilt successfully.\n');

    console.log('\n====================================================');
    console.log('DEPLOYMENT COMPLETE. PROCEEDING TO VERIFICATION.');
    console.log('====================================================');
}

main().catch(err => {
    console.error('DEPLOYMENT FAILED:', err);
    process.exit(1);
});
