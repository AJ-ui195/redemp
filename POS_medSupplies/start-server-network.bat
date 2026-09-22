@echo off
echo ========================================
echo Starting Laravel Server for Network Access
echo ========================================
echo.

echo Stopping any existing server on port 8000...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000.*LISTENING"') do (
    echo Stopping process %%a...
    taskkill /F /PID %%a >nul 2>&1
)

timeout /t 2 /nobreak >nul

echo.
echo Starting server with network access...
echo Your IP Address: 192.168.1.105
echo Server will be accessible at: http://192.168.1.105:8000
echo.
echo IMPORTANT: Keep this window open while using the app!
echo The server will stop if you close this window.
echo.
echo ========================================
echo.

cd /d "%~dp0"
php artisan serve --host=0.0.0.0 --port=8000

pause

