@echo off
rem ===========================================================================
rem start-site.bat  -  LOCAL USE ONLY (never upload this file to the server)
rem Starts the website on this computer at http://127.0.0.1:8000
rem   1. refuses to run if .env points at a live database or a real mail server
rem   2. clears Laravel's caches (php artisan optimize:clear)
rem   3. makes sure the public/storage link exists (uploaded images)
rem   4. starts the site with Herd's PHP 8.4
rem Stop the site: press Ctrl+C in this window.
rem It never prints passwords or other values from .env.
rem ===========================================================================
setlocal
cd /d "%~dp0"
set "PHP=%USERPROFILE%\.config\herd\bin\php84\php.exe"
if not exist "%PHP%" (
    echo PHP 8.4 from Herd was not found at %PHP%
    exit /b 1
)
netstat -ano | findstr /r /c:":8000 .*LISTENING" >nul && (
    echo Port 8000 is already in use. Is the site already running in another window?
    echo See HOW-TO-RUN.md, "Port already in use".
    exit /b 1
)
"%PHP%" tools\local\guard.php || exit /b 1
"%PHP%" artisan optimize:clear || exit /b 1
if not exist "public\storage" "%PHP%" artisan storage:link
echo.
echo Open http://127.0.0.1:8000 in your browser. Press Ctrl+C here to stop the site.
echo.
rem --no-reload: without it Laravel strips the environment it passes to PHP and
rem matches names case-sensitively, so Windows' "SystemRoot" is dropped and PHP
rem fails with "Failed to listen on 127.0.0.1:8000 (reason: ?)". Side effect:
rem after editing .env, stop (Ctrl+C) and start this script again.
"%PHP%" artisan serve --host=127.0.0.1 --port=8000 --no-reload
