@echo off
echo ========================================
echo  Server Stability Test
echo ========================================
echo.
echo This script tests server stability by:
echo 1. Checking if server is running
echo 2. Testing health endpoint
echo 3. Testing connection endpoint
echo 4. Running multiple requests to check stability
echo.

set TEST_COUNT=10
set SUCCESS_COUNT=0
set FAIL_COUNT=0

echo [TEST 1] Checking if server is listening on port 8000...
netstat -ano | findstr ":8000.*LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Server is listening on port 8000
) else (
    echo [FAIL] Server is NOT listening on port 8000
    echo [INFO] Please start the server first using start-server.bat
    pause
    exit /b 1
)

echo.
echo [TEST 2] Testing health endpoint...
curl -s -m 5 http://localhost:8000/api/health >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Health endpoint is responding
    curl -s http://localhost:8000/api/health
    echo.
) else (
    echo [FAIL] Health endpoint is not responding
    set /a FAIL_COUNT+=1
)

echo.
echo [TEST 3] Testing connection endpoint...
curl -s -m 5 http://localhost:8000/api/connection-test >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Connection endpoint is responding
    set /a SUCCESS_COUNT+=1
) else (
    echo [FAIL] Connection endpoint is not responding
    set /a FAIL_COUNT+=1
)

echo.
echo [TEST 4] Running %TEST_COUNT% consecutive requests to test stability...
for /L %%i in (1,1,%TEST_COUNT%) do (
    curl -s -m 3 http://localhost:8000/api/connection-test >nul 2>&1
    if !errorlevel! equ 0 (
        echo [OK] Request %%i successful
        set /a SUCCESS_COUNT+=1
    ) else (
        echo [FAIL] Request %%i failed
        set /a FAIL_COUNT+=1
    )
    timeout /t 1 /nobreak >nul
)

echo.
echo ========================================
echo  Test Results
echo ========================================
echo Successful requests: %SUCCESS_COUNT%
echo Failed requests: %FAIL_COUNT%
echo Total requests: %TEST_COUNT%
echo.

set /a SUCCESS_RATE=(%SUCCESS_COUNT% * 100) / %TEST_COUNT%
echo Success rate: %SUCCESS_RATE%%%

if %SUCCESS_RATE% geq 90 (
    echo [RESULT] Server stability: EXCELLENT
) else if %SUCCESS_RATE% geq 70 (
    echo [RESULT] Server stability: GOOD
) else if %SUCCESS_RATE% geq 50 (
    echo [RESULT] Server stability: FAIR - Consider using watchdog
) else (
    echo [RESULT] Server stability: POOR - Server needs attention
)

echo.
pause
