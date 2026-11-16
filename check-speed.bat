@echo off
rem ===========================================================================
rem check-speed.bat  -  LOCAL USE ONLY (never upload this file to the server)
rem Measures every page like a phone on a slow mobile connection (empty cache)
rem and prints a table: size, requests, FCP, LCP, CLS, blocking time.
rem Start the site first (start-site.bat) in another window.
rem   check-speed.bat                    all pages, 3 runs each (about 20-40 min)
rem   check-speed.bat --runs=1           faster, one run per page
rem   check-speed.bat --label=after      name of the result file
rem   check-speed.bat --pages=home,about only these pages
rem Results: footage\_review\speed\<label>.md
rem ===========================================================================
setlocal
cd /d "%~dp0"
set "PHP=%USERPROFILE%\.config\herd\bin\php84\php.exe"
where node >nul 2>nul || (
    echo Node.js was not found. Install it, then run "npm install" once in this folder.
    exit /b 1
)
"%PHP%" tools\local\guard.php || exit /b 1
node tools\local\check-speed.mjs %*
