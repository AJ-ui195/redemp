@echo off
echo ========================================
echo  Fix Server Configuration
echo ========================================
echo.

echo Step 1: Stopping any existing server on port 8000...
echo.

for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000 ^| findstr LISTENING') do (
    echo Found server process %%a, stopping it...
    taskkill /F /PID %%a >nul 2>&1
    if !errorlevel! equ 0 (
        echo [OK] Server stopped
    ) else (
        echo [INFO] No server process found or already stopped
    )
)

timeout /t 2 /nobreak >nul

echo.
echo Step 2: Starting server with network access...
echo.
echo Configuration:
echo - Host: 0.0.0.0 (Network accessible)
echo - Port: 8000
echo - Your IP: 192.168.1.5
echo - Server URL: http://192.168.1.5:8000
echo.
echo IMPORTANT:
echo - Make sure Windows Firewall allows port 8000
echo - Make sure device and server are on same WiFi network
echo.
echo Starting server...
echo Press Ctrl+C to stop the server
echo.
echo ========================================
echo.

php artisan serve --host=0.0.0.0 --port=8000

