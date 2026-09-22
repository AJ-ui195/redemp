@echo off
echo ========================================
echo  Laravel Server - With Watchdog
echo ========================================
echo.
echo Starting server with automatic monitoring...
echo.
echo This will:
echo 1. Start the Laravel server on 0.0.0.0:8000
echo 2. Start a watchdog that monitors and restarts if needed
echo.
echo Press Ctrl+C to stop both
echo.

REM Start the server in a new window
start "Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

REM Wait a moment for server to start
timeout /t 3 /nobreak >nul

REM Start the watchdog in another window
start "Server Watchdog" cmd /k "server-watchdog.bat"

echo.
echo [INFO] Server and watchdog started in separate windows
echo [INFO] Check the windows for status
echo.
echo Press any key to stop monitoring (server will continue running)...
pause >nul
