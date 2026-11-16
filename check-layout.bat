@echo off
rem ===========================================================================
rem check-layout.bat  -  LOCAL USE ONLY (never upload this file to the server)
rem Opens every page at 14 screen sizes x 3 zoom levels (plus landscape phones)
rem and reports sideways scrolling, small text and buttons on phones, small
rem form text, stretched images; saves screenshots at 360/768/1024/1440 px.
rem Start the site first (start-site.bat) in another window.
rem   check-layout.bat                 everything (about 15-30 min)
rem   check-layout.bat --quick         100% zoom only (about 5 min)
rem   check-layout.bat --label=after   name of the result folder
rem   check-layout.bat --pages=home    only these pages
rem Results: footage\_review\layout\<label>\summary.md and shots\
rem Exit code 1 means at least one page scrolls sideways.
rem ===========================================================================
setlocal
cd /d "%~dp0"
set "PHP=%USERPROFILE%\.config\herd\bin\php84\php.exe"
where node >nul 2>nul || (
    echo Node.js was not found. Install it, then run "npm install" once in this folder.
    exit /b 1
)
"%PHP%" tools\local\guard.php || exit /b 1
node tools\local\check-layout.mjs %*
