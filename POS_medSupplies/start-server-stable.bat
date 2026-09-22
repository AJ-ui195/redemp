@echo off
setlocal enabledelayedexpansion

echo ========================================
echo  Laravel Server - Stable Mode with Auto-Restart
echo ========================================
echo.

:CHECK_PORT
echo Checking if port 8000 is already in use...
netstat -ano | findstr ":8000.*LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    echo [WARNING] Port 8000 is already in use!
    echo Attempting to free the port...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000.*LISTENING"') do (
        echo Killing process %%a...
        taskkill /F /PID %%a >nul 2>&1
    )
    timeout /t 2 /nobreak >nul
)

:START_SERVER
echo.
echo ========================================
echo Starting Laravel server...
echo ========================================
echo Server URL: http://0.0.0.0:8000
echo Network IP: http://192.168.1.5:8000
echo.
echo [INFO] Server will auto-restart on errors
echo [INFO] Press Ctrl+C to stop
echo.
echo ========================================
echo.

:LOOP
php artisan serve --host=0.0.0.0 --port=8000
set EXIT_CODE=%errorlevel%

if %EXIT_CODE% neq 0 (
    echo.
    echo [ERROR] Server stopped unexpectedly (Exit code: %EXIT_CODE%)
    echo [INFO] Waiting 5 seconds before restart...
    timeout /t 5 /nobreak >nul
    echo [INFO] Restarting server...
    echo.
    goto LOOP
) else (
    echo.
    echo [INFO] Server stopped normally
    echo [INFO] Exiting...
)

endlocal
