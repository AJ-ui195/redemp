@echo off
setlocal enabledelayedexpansion

echo ========================================
echo  Server Watchdog - Auto-Restart Monitor
echo ========================================
echo.
echo This script monitors the server and restarts it if it stops
echo It also checks server health via API endpoint
echo Press Ctrl+C to stop monitoring
echo.

set CHECK_INTERVAL=15
set RESTART_COUNT=0
set MAX_RESTARTS=20

:MONITOR_LOOP
echo.
echo ========================================
echo [%date% %time%] Server Status Check
echo ========================================

REM Check if port is listening
netstat -ano | findstr ":8000.*LISTENING" >nul 2>&1
if %errorlevel% neq 0 (
    echo [WARNING] Server is NOT running on port 8000!
    set /a RESTART_COUNT+=1
    
    if !RESTART_COUNT! gtr %MAX_RESTARTS% (
        echo [ERROR] Maximum restart attempts reached!
        echo [ERROR] Please check server logs manually
        pause
        exit /b 1
    )
    
    echo [INFO] Restart attempt #!RESTART_COUNT!
    echo [INFO] Starting server...
    
    REM Kill any stuck processes
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000"') do (
        taskkill /F /PID %%a >nul 2>&1
    )
    timeout /t 2 /nobreak >nul
    
    REM Start server in background
    start /B cmd /c "cd /d %~dp0 && php artisan serve --host=0.0.0.0 --port=8000"
    
    echo [INFO] Waiting for server to start...
    timeout /t 5 /nobreak >nul
    
    REM Verify server started
    netstat -ano | findstr ":8000.*LISTENING" >nul 2>&1
    if %errorlevel% equ 0 (
        echo [SUCCESS] Server started successfully
        set RESTART_COUNT=0
    ) else (
        echo [ERROR] Failed to start server
        echo [INFO] Check if PHP is in PATH and Laravel is configured correctly
    )
) else (
    echo [OK] Server is listening on port 8000
    
    REM Check if server is responding (health check)
    curl -s -m 5 http://localhost:8000/api/health >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] Server is responding to health checks
        set RESTART_COUNT=0
    ) else (
        echo [WARNING] Server is listening but not responding to requests
        echo [INFO] Server may be stuck, attempting restart...
        for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000.*LISTENING"') do (
            taskkill /F /PID %%a >nul 2>&1
        )
        timeout /t 2 /nobreak >nul
        start /B cmd /c "cd /d %~dp0 && php artisan serve --host=0.0.0.0 --port=8000"
        timeout /t 3 /nobreak >nul
    )
)

echo [INFO] Next check in %CHECK_INTERVAL% seconds...
timeout /t %CHECK_INTERVAL% /nobreak >nul
goto MONITOR_LOOP

endlocal
