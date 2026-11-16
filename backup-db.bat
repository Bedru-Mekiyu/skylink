@echo off
rem ===========================================================================
rem backup-db.bat  -  LOCAL USE ONLY (never upload this file to the server)
rem Copies the local SQLite database to footage\_review\db-backup\ with the
rem date and time in the name. Safe while the site is running; the database
rem itself is only read. Refuses to run with a live .env.
rem   backup-db.bat                    name ends in .manual.bak
rem   backup-db.bat before-images      name ends in .before-images.bak
rem How to restore a backup: see HOW-TO-RUN.md, "Restore the database".
rem ===========================================================================
setlocal
cd /d "%~dp0"
set "PHP=%USERPROFILE%\.config\herd\bin\php84\php.exe"
if not exist "%PHP%" (
    echo PHP 8.4 from Herd was not found at %PHP%
    exit /b 1
)
"%PHP%" tools\local\backup-db.php %*
