<#
  ONE COMMAND to build a deploy-ready release. Run from the project root:

      powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1 [-Ref HEAD] [-ServerPhp 8.2.35] [-SkipTests] [-SkipVerify] [-FromWorkingTree]

  What it does, from a clean checkout of the chosen commit:
    1. runs the test suite (php artisan test)                    -> stops if anything fails
    2. exports the committed files of -Ref (git archive); builds the front-end assets (npm run build)
    3. composer install --no-dev --optimize-autoloader for PHP -ServerPhp (platform set in the temporary copy only)
    4. builds skylink-app.zip (-> /home/skylinkictcom/s.skylinkict.com) and skylink-public.zip (-> .../public)
       with sha256 files and file lists; media (hero video, posters, photos) is committed, so it is inside
    5. writes RELEASE.txt (commit, date, PHP, migrations, checksums)
    6. runs deploy\verify-release.mjs: unpacks the zips into a temp "server", migrates a fresh database and checks the
       site for Stage 1 (no ASSET_URL) and Stage 2 (ASSET_URL=/sl), including: ceremony HIDDEN on a fresh install
  Output folder to upload:  deploy\.build\release\   (zips, .sha256, RELEASE.txt, server-deploy.sh, server-rollback.sh, server-common.sh)

  Stays out of the zips: .env, node_modules, tests, tools, deploy, footage, *.bat, app.zip/routes.zip/vendor.zip, local captures,
  storage logs/cache/sessions/views, user uploads, any database file, and the cPanel-managed public/.user.ini, public/php.ini,
  public/favicon.ico.
  -FromWorkingTree packages HEAD + staged/tracked changes (not committed); use it only for a trial build.
  Nothing is uploaded or changed on any server by this script.
#>
param([string]$Ref = 'HEAD', [string]$ServerPhp = '8.2.35', [switch]$SkipTests, [switch]$SkipVerify, [switch]$FromWorkingTree)

$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)

$dirty = git status --porcelain --untracked-files=no
if ($dirty -and -not $FromWorkingTree) { throw "Uncommitted changes to tracked files. Commit them first so the zips match a commit (or pass -FromWorkingTree for a trial build).`n$dirty" }
$head = (git rev-parse HEAD).Trim()
if ($FromWorkingTree) {
    $snap = (git stash create).Trim()
    if ($snap) { $Ref = $snap; Write-Host "Packaging working-tree snapshot $($snap.Substring(0,8)) on top of HEAD" }
}
$full  = (git rev-parse $Ref).Trim()
$sha   = $full.Substring(0, 8)
$subject = (git log -1 --format=%s $full).Trim()

$build = Join-Path $PWD 'deploy\.build'
$stage = Join-Path $build 'stage'
$pubStage = Join-Path $build 'stage-public'
$out   = Join-Path $build 'release'
# Windows' own bsdtar: Git's GNU tar reads "C:\..." as a remote host.
$tar   = Join-Path $env:SystemRoot 'System32\tar.exe'
New-Item -ItemType Directory -Force $build, $out | Out-Null
foreach ($d in $stage, $pubStage) { if (Test-Path $d) { Remove-Item $d -Recurse -Force } }
New-Item -ItemType Directory -Force $stage | Out-Null

# 1. tests (they run on the working tree, which must be the commit being released)
$testSummary = $null
if ($SkipTests) { Write-Host '1/6 Tests: SKIPPED (-SkipTests)' }
elseif ($full -ne $head -and -not $FromWorkingTree) { throw "-Ref is not HEAD: the tests would run on a different tree. Check out that commit, or pass -SkipTests knowingly." }
else {
    Write-Host '1/6 Running the test suite'
    $testOut = php artisan test 2>&1
    $code = $LASTEXITCODE
    $testOut | Select-Object -Last 4 | ForEach-Object { Write-Host "   $_" }
    if ($code -ne 0) { throw 'Tests failed. No release was built.' }
    $testSummary = ($testOut | Select-String 'Tests:' | Select-Object -Last 1).Line.Trim()
}

Write-Host "2/6 Exporting committed files of $Ref ($sha) and building assets"
git archive --format=tar --output="$build\export.tar" $Ref
& $tar -xf "$build\export.tar" -C $stage
Remove-Item "$build\export.tar" -Force
if (-not (Test-Path (Join-Path $stage 'composer.json'))) { throw 'Export failed: composer.json is missing from the staging folder.' }
foreach ($p in 'tests','tools','deploy','footage','phpunit.xml','build-video.sh','backup-db.bat','check-layout.bat',
               'check-speed.bat','run-tests.bat','start-site.bat','routes.zip','app.zip','vendor.zip','public\.user.ini','public\php.ini','public\favicon.ico',
               'package.json','package-lock.json','vite.config.js','tailwind.config.js','postcss.config.js','.env','.env.example') {
    $t = Join-Path $stage $p
    if (Test-Path $t) { Remove-Item $t -Recurse -Force }
}
npm run build | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }
Copy-Item (Join-Path $PWD 'public\build') (Join-Path $stage 'public\build') -Recurse -Force

Write-Host "3/6 Installing production PHP packages (no dev packages) for PHP $ServerPhp"
Push-Location $stage
composer config platform.php $ServerPhp          # only in this temporary copy, never in the repo
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist | Out-Null
$code = $LASTEXITCODE
Pop-Location
if ($code -ne 0) { throw 'composer install failed' }
$platform = (Select-String (Join-Path $stage 'vendor\composer\platform_check.php') -Pattern 'PHP_VERSION_ID >= \d+' | Select-Object -First 1).Matches.Value
Write-Host "   platform check: $platform"
foreach ($d in 'storage\logs','storage\framework\cache\data','storage\framework\sessions','storage\framework\views','storage\app\public','bootstrap\cache') {
    New-Item -ItemType Directory -Force (Join-Path $stage $d) | Out-Null
}
Get-ChildItem (Join-Path $stage 'bootstrap\cache') -File -ErrorAction SilentlyContinue | Where-Object Name -ne '.gitignore' | Remove-Item -Force

Write-Host '4/6 Creating skylink-app.zip and skylink-public.zip'
Move-Item (Join-Path $stage 'public') $pubStage
function New-ReleaseZip($Folder, $ZipPath) {
    if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
    & $tar -a -c -f $ZipPath -C $Folder .
    if ($LASTEXITCODE -ne 0) { throw "zip failed: $ZipPath" }
    (& $tar -tf $ZipPath | Where-Object { $_ -and -not $_.EndsWith('/') }) | Set-Content "$ZipPath.files.txt"
    $s = [IO.File]::OpenRead($ZipPath)
    $h = ([BitConverter]::ToString([Security.Cryptography.SHA256]::Create().ComputeHash($s)) -replace '-', '').ToLower()
    $s.Dispose()
    # sha256sum format (two spaces), LF, so `sha256sum` on the server can read it
    [IO.File]::WriteAllText("$ZipPath.sha256", "$h  $(Split-Path $ZipPath -Leaf)`n")
    Write-Host ('   {0}  {1} MB  sha256 {2}' -f (Split-Path $ZipPath -Leaf), [math]::Round((Get-Item $ZipPath).Length / 1MB, 1), $h)
    return $h
}
$hApp = New-ReleaseZip $stage    (Join-Path $out 'skylink-app.zip')
$hPub = New-ReleaseZip $pubStage (Join-Path $out 'skylink-public.zip')

Write-Host '5/6 Writing RELEASE.txt and copying the server scripts'
$lastOnServer = '2025_07_23_081217_add_project_url_to_portfolios_table'   # newest migration found on the server on 2026-11-19
$migs = @(Get-ChildItem (Join-Path $stage 'database\migrations') -Name | Sort-Object)
$newMigs = @($migs | Where-Object { $_ -gt "$lastOnServer.php" })
$lines = @(
    "Sky Link release $sha",
    "commit   : $full  ($subject)",
    "built    : $(Get-Date -Format 'yyyy-MM-dd HH:mm')",
    "PHP      : vendor built for $ServerPhp ($platform)",
    "tests    : $(if ($testSummary) { $testSummary } else { 'not run' })",
    "target   : /home/skylinkictcom/s.skylinkict.com (app zip) and .../public (public zip)",
    "ceremony : OFF until an admin turns it on (Admin > Anniversary Ceremony); the scripts never run news:publish-anniversary",
    "sha256   : skylink-app.zip    $hApp",
    "           skylink-public.zip $hPub",
    "",
    "migrations in this release: $($migs.Count); NEW for the server (after $lastOnServer): $($newMigs.Count)"
) + @($newMigs | ForEach-Object { "  - $($_ -replace '\.php$','')" })
[IO.File]::WriteAllText((Join-Path $out 'RELEASE.txt'), (($lines -join "`n") + "`n"))
foreach ($f in 'server-deploy.sh','server-rollback.sh','server-common.sh') { Copy-Item (Join-Path $PSScriptRoot $f) (Join-Path $out $f) -Force }
$lines | ForEach-Object { Write-Host "   $_" }

if ($SkipVerify) { Write-Host '6/6 Package verification: SKIPPED (-SkipVerify)' }
else {
    Write-Host '6/6 Verifying the package (temp server, fresh database)'
    node deploy\verify-release.mjs $out
    if ($LASTEXITCODE -ne 0) { throw 'Package verification FAILED. Do not deploy this release.' }
    [IO.File]::AppendAllText((Join-Path $out 'RELEASE.txt'), "verified : deploy/verify-release.mjs passed ($(Get-Date -Format 'yyyy-MM-dd HH:mm'))`n")
}
Write-Host "`nDONE. Upload the files in: $out"
