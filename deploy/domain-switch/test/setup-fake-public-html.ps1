<#
  LOCAL TEST ONLY. Builds a fake copy of the server's public_html (dummy other sites + the clash cases) next to an
  extracted release package, and installs the stage-2 files into it.
      setup-fake-public-html.ps1 -AppRoot <extracted s.skylinkict.com folder> -Out <new empty folder>
  Nothing outside -Out is written.
#>
param([Parameter(Mandatory)][string]$AppRoot, [Parameter(Mandatory)][string]$Out)
$ErrorActionPreference = 'Stop'
$here = Split-Path -Parent $PSScriptRoot
New-Item -ItemType Directory -Force $Out | Out-Null
function Put($rel, $text) { $p = Join-Path $Out $rel; New-Item -ItemType Directory -Force (Split-Path $p) | Out-Null; Set-Content $p $text -NoNewline }

# other sites and the clash cases found on the real server (names only; contents are dummies)
Put 'erp\index.php'            '<?php echo "ERP-OK";'
Put 'webpro\index.php'         '<?php echo "WEBPRO-OK";'
Put 'newsite\index.html'       'NEWSITE-OK'
Put 'privacy-policy\index.html' 'PRIVACY-OK'
Put 'readymeal\sub\page.html'  'READYMEAL-OK'
Put 'empty-folder\.keep'       ''
Put 'assets\index-DI8Yc8se.js' 'BASIRAH-ASSET'                 # clash: Sky Link also has /assets
Put 'assets\images\logo.png'   'BASIRAH-IMAGE'                 # clash: /assets/images exists in both
Put 'build\manifest.json'      'OLD-SKYLINK-BUILD'             # clash: /build
New-Item -ItemType Directory -Force (Join-Path $Out 'storage') | Out-Null   # clash: empty /storage
Put 'index.html'               '<title>besirah-react</title>BASIRAH-INDEX'
Put 'index.php'                '<?php require __DIR__."/skylink/vendor/autoload.php";'   # the old, broken front controller
Put 'robots.txt'               "User-agent: *`nDisallow:"
Put 'cwpord_test.php'          '<?php echo "CWPORD-OK";'

# stage-2 files: front controller with the test path, symlink-style folders (junctions on Windows)
$front = (Get-Content (Join-Path $here 'skylink-front.php') -Raw).Replace("'/home/skylinkictcom/s.skylinkict.com'", "'" + ($AppRoot -replace '\\', '/') + "'")
Set-Content (Join-Path $Out 'skylink-front.php') $front -NoNewline
New-Item -ItemType Directory -Force (Join-Path $Out 'sl') | Out-Null
New-Item -ItemType Junction -Path (Join-Path $Out 'sl\assets')  -Target (Join-Path $AppRoot 'public\assets')   | Out-Null
New-Item -ItemType Junction -Path (Join-Path $Out 'sl\build')   -Target (Join-Path $AppRoot 'public\build')    | Out-Null
New-Item -ItemType Junction -Path (Join-Path $Out 'sl\storage') -Target (Join-Path $AppRoot 'storage\app\public') | Out-Null
# the release never ships favicon.ico (the server keeps its own); give the test app a stand-in
$fav = Join-Path $AppRoot 'public\favicon.ico'
if (-not (Test-Path $fav)) { [IO.File]::WriteAllBytes($fav, [byte[]](0, 0, 1, 0, 1, 0)) }
Copy-Item $fav (Join-Path $Out 'favicon.ico')
Write-Host "Fake public_html ready: $Out"
