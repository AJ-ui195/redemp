@echo off
echo ========================================
echo Starting Laravel Server for Scanner App
echo ========================================
echo.

echo Checking if server is already running...
netstat -ano | findstr ":8000.*LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    echo [WARNING] Server is already running on port 8000!
    echo.
    echo Checking if it's network accessible...
    netstat -ano | findstr "0.0.0.0:8000.*LISTENING" >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] Server is running and network accessible!
        echo Server URL: http://192.168.1.103:8000
        echo.
        echo If you want to restart, please stop the existing server first.
        echo Or use restart-server.bat to automatically restart.
        echo.
        pause
        exit /b 0
    ) else (
        echo [WARNING] Server is running but NOT network accessible (only localhost)!
        echo Stopping incorrect server instance...
        for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000.*LISTENING"') do (
            taskkill /F /PID %%a >nul 2>&1
        )
        timeout /t 2 /nobreak >nul
        echo [OK] Server stopped. Starting new instance...
        echo.
    )
) else (
    echo [OK] No server running. Starting new server...
    echo.
)

echo Your IP Address: 192.168.1.103
echo Server will be accessible at: http://192.168.1.103:8000
echo.
echo IMPORTANT: Keep this window open while using the app!
echo The server will stop if you close this window.
echo.
echo Press Ctrl+C to stop the server
echo.
echo ========================================
echo.

cd /d "%~dp0"
php artisan serve --host=0.0.0.0 --port=8000

pause
