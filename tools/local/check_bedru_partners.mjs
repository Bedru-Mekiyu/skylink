import { spawn } from 'node:child_process';

const remotePhp = `<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

echo "BEDRU_PARTNERS_COUNT: " . \\App\\Models\\Partner::count() . "\\n";
`;

const ssh = spawn('ssh', [
    '-i', 'C:\\Users\\AFRO-EAGLE\\.ssh\\cpanel_bedru',
    '-p', '22',
    'skylinkictcom@91.204.209.13',
    'cd /home/skylinkictcom/bedru.skylinkict.com && php'
]);

let stdout = '';
ssh.stdout.on('data', d => { stdout += d.toString(); });
ssh.on('close', () => { console.log(stdout); });
ssh.stdin.write(remotePhp);
ssh.stdin.end();
