<#
  Builds a clean release zip of the site. Run from the project root:

      powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1 [-Ref anniversary-5-years]

  What goes in:   the committed files of -Ref (default HEAD), the built assets (public/build) and
                  vendor installed with `composer install --no-dev --optimize-autoloader`.
  What stays out: .env, node_modules, tests, tools, footage, *.bat, local captures, storage/logs,
                  the cPanel-managed public/.user.ini and public/php.ini, any database file.
  Output:         deploy\.build\skylink-release-<ref>-<short sha>.zip  (+ a .sha256 file next to it)
  Nothing is uploaded or changed on the server by this script.
#>
param([string]$Ref = 'HEAD')

$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)

$dirty = git status --porcelain --untracked-files=no
if ($dirty) { throw "Uncommitted changes to tracked files. Commit them first so the zip matches a commit.`n$dirty" }

$sha   = (git rev-parse --short $Ref).Trim()
$label = ($Ref -replace '[^A-Za-z0-9._-]', '-')
$build = Join-Path $PWD 'deploy\.build'
$stage = Join-Path $build 'stage'
$zip   = Join-Path $build "skylink-release-$label-$sha.zip"

if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force $stage | Out-Null

Write-Host "1/5 Exporting committed files of $Ref ($sha)"
git archive --format=tar $Ref | tar -x -C $stage

Write-Host '2/5 Removing what the server does not need'
foreach ($p in 'tests','tools','deploy','footage','phpunit.xml','build-video.sh','backup-db.bat','check-layout.bat',
               'check-speed.bat','run-tests.bat','start-site.bat','routes.zip','public\.user.ini','public\php.ini',
               'package.json','package-lock.json','vite.config.js','tailwind.config.js','postcss.config.js') {
    $t = Join-Path $stage $p
    if (Test-Path $t) { Remove-Item $t -Recurse -Force }
}

Write-Host '3/5 Building front-end assets'
npm run build | Out-Null
Copy-Item (Join-Path $PWD 'public\build') (Join-Path $stage 'public\build') -Recurse -Force

Write-Host '4/5 Installing production PHP packages (no dev packages)'
Push-Location $stage
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
Pop-Location

# Empty runtime folders the app needs (never any logs, cache or uploads).
foreach ($d in 'storage\logs','storage\framework\cache\data','storage\framework\sessions','storage\framework\views','storage\app\public','bootstrap\cache') {
    New-Item -ItemType Directory -Force (Join-Path $stage $d) | Out-Null
}
Get-ChildItem (Join-Path $stage 'bootstrap\cache') -File -ErrorAction SilentlyContinue | Where-Object Name -ne '.gitignore' | Remove-Item -Force

Write-Host '5/5 Creating the zip'
if (Test-Path $zip) { Remove-Item $zip -Force }
tar -a -c -f $zip -C $stage .
(Get-FileHash $zip -Algorithm SHA256).Hash | Set-Content "$zip.sha256"

$mb = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Done: $zip ($mb MB)"
Write-Host "SHA256: $((Get-Content "$zip.sha256"))"
