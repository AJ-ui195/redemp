@echo off
echo ========================================
echo Testing Server Connection
echo ========================================
echo.
echo Testing connection to: http://192.168.1.5:8000
echo.

curl -s http://192.168.1.5:8000/api/connection-test

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo SUCCESS: Server is reachable!
    echo ========================================
) else (
    echo.
    echo ========================================
    echo ERROR: Cannot connect to server
    echo ========================================
    echo.
    echo Please check:
    echo 1. Server is running (run start-server.bat)
    echo 2. Firewall allows port 8000
    echo 3. IP address is correct (192.168.1.5)
    echo.
)

echo.
pause

