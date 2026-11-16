@echo off
rem ===========================================================================
rem run-tests.bat  -  LOCAL USE ONLY (never upload this file to the server)
rem Runs the automated tests with Herd's PHP 8.4.
rem   run-tests.bat                                       all tests
rem   run-tests.bat tests\Feature\LayoutIntegrityTest.php  one test file
rem The tests always use an in-memory database and a fake mailer (phpunit.xml),
rem but this script still refuses to run with a live .env, to be safe.
rem 4 old failures are expected: AuthenticationTest (login redirect),
rem RegistrationTest (2) and ExampleTest.
rem ===========================================================================
setlocal
cd /d "%~dp0"
set "PHP=%USERPROFILE%\.config\herd\bin\php84\php.exe"
if not exist "%PHP%" (
    echo PHP 8.4 from Herd was not found at %PHP%
    exit /b 1
)
"%PHP%" tools\local\guard.php || exit /b 1
"%PHP%" artisan test %*
