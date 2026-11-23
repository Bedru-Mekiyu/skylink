import { spawn } from 'node:child_process';
import fs from 'node:fs';

const remotePhp = `<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

$partners = \\App\\Models\\Partner::orderBy('id')->get();
$totalCount = $partners->count();
$activeCount = $partners->where('active', true)->count();
$featuredCount = $partners->where('featured', true)->count();

$filesReferenced = [];

foreach ($partners as $p) {
    $candidates = [
        'logo_path' => $p->logo_path ? 'storage/app/public/' . $p->logo_path : null,
        'logo_fallback' => $p->logo_fallback ? 'storage/app/public/' . $p->logo_fallback : null,
        'legacy_src' => $p->legacy_src ? 'public/' . $p->legacy_src : null,
        'original_path' => $p->original_path ? 'storage/app/' . $p->original_path : null,
    ];
    
    $fileInfo = [];
    foreach ($candidates as $field => $relPath) {
        if (! $relPath) continue;
        $fullPath = base_path($relPath);
        if (file_exists($fullPath)) {
            $stat = [
                'field' => $field,
                'rel_path' => $relPath,
                'filename' => basename($fullPath),
                'size_bytes' => filesize($fullPath),
                'size_formatted' => round(filesize($fullPath) / 1024, 1) . ' KB',
                'mtime' => date('Y-m-d H:i:s', filemtime($fullPath)),
                'exists' => true,
            ];
            $filesReferenced[$relPath] = $stat;
            $fileInfo[$field] = $stat;
        } else {
            $fileInfo[$field] = [
                'field' => $field,
                'rel_path' => $relPath,
                'exists' => false,
            ];
        }
    }
    
    $records[] = [
        'id' => $p->id,
        'name' => $p->name,
        'active' => $p->active,
        'featured' => $p->featured,
        'sort_order' => $p->sort_order,
        'files' => $fileInfo,
    ];
}

// Also check directory storage/app/public/partners
$partnersDir = storage_path('app/public/partners');
$dirFiles = [];
if (is_dir($partnersDir)) {
    foreach (scandir($partnersDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $fPath = $partnersDir . '/' . $f;
        $dirFiles[] = [
            'filename' => $f,
            'rel_path' => 'storage/app/public/partners/' . $f,
            'size_bytes' => filesize($fPath),
            'size_formatted' => round(filesize($fPath) / 1024, 1) . ' KB',
            'mtime' => date('Y-m-d H:i:s', filemtime($fPath)),
        ];
    }
}

$output = [
    'summary' => [
        'total_partners_rows' => $totalCount,
        'active_partners' => $activeCount,
        'featured_partners' => $featuredCount,
        'unique_files_referenced' => count($filesReferenced),
        'files_in_storage_partners_dir' => count($dirFiles),
    ],
    'files_referenced' => array_values($filesReferenced),
    'storage_partners_dir_files' => $dirFiles,
    'records' => $records,
];

echo "===JSON_START===\\n";
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\\n===JSON_END===\\n";
`;

const ssh = spawn('ssh', [
    '-i', 'C:\\Users\\AFRO-EAGLE\\.ssh\\cpanel_bedru',
    '-p', '22',
    'skylinkictcom@91.204.209.13',
    'cd /home/skylinkictcom/s.skylinkict.com && php'
]);

let stdout = '';
ssh.stdout.on('data', d => { stdout += d.toString(); });
ssh.on('close', () => {
    const jsonStr = stdout.split('===JSON_START===')[1]?.split('===JSON_END===')[0]?.trim();
    if (jsonStr) {
        fs.writeFileSync('tools/local/live_partners_data.json', jsonStr);
        const data = JSON.parse(jsonStr);
        console.log('LIVE PARTNERS AUDIT SUMMARY:');
        console.log(JSON.stringify(data.summary, null, 2));
    } else {
        console.log(stdout);
    }
});
ssh.stdin.write(remotePhp);
ssh.stdin.end();
