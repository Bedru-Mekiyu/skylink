import { spawn } from 'node:child_process';

const ssh = spawn('ssh', [
    '-i', 'C:\\Users\\AFRO-EAGLE\\.ssh\\cpanel_bedru',
    '-p', '22',
    'skylinkictcom@91.204.209.13',
    'bash -s'
]);

ssh.stdout.on('data', d => console.log(d.toString()));
ssh.stderr.on('data', d => console.error(d.toString()));

const script = `
cd /home/skylinkictcom/s.skylinkict.com
ls -lh storage/logs/
TODAY=$(date +%Y-%m-%d)
LOG="storage/logs/laravel-$TODAY.log"
if [ -f "$LOG" ]; then
    echo "=== $LOG error check ==="
    grep -E "ERROR|CRITICAL|EMERGENCY|EXCEPTION" "$LOG" | tail -20 || echo "Zero errors in $LOG."
else
    echo "No log file for today ($TODAY). Recent log files:"
    ls -l storage/logs/ | tail -5
fi
`;

ssh.stdin.write(script);
ssh.stdin.end();
