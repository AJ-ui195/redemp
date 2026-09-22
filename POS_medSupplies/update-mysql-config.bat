@echo off
echo ========================================
echo MySQL Configuration Update for Large Images
echo ========================================
echo.

echo This script will help you update MySQL configuration to handle large images.
echo.
echo IMPORTANT: You need to manually edit the MySQL config file.
echo.

set MYSQL_CONFIG=C:\xampp\mysql\bin\my.ini

if exist "%MYSQL_CONFIG%" (
    echo Found MySQL config file: %MYSQL_CONFIG%
    echo.
    echo Please add or update the following line under [mysqld] section:
    echo max_allowed_packet = 64M
    echo.
    echo Opening config file in notepad...
    timeout /t 3 /nobreak >nul
    notepad "%MYSQL_CONFIG%"
    echo.
    echo After saving the file, you MUST restart MySQL service from XAMPP Control Panel.
    echo.
) else (
    echo MySQL config file not found at: %MYSQL_CONFIG%
    echo.
    echo Please manually find and edit your MySQL config file:
    echo - XAMPP: C:\xampp\mysql\bin\my.ini
    echo - Standard MySQL: Usually in MySQL installation directory
    echo.
    echo Add this line under [mysqld] section:
    echo max_allowed_packet = 64M
    echo.
)

echo ========================================
pause

