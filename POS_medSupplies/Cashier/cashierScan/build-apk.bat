@echo off
echo ========================================
echo  Build Cashier Scanner APK
echo ========================================
echo.
echo This will build an APK file for Android.
echo The build process takes about 10-20 minutes.
echo.
echo Make sure you have:
echo 1. EAS CLI installed: npm install -g eas-cli
echo 2. Expo account and logged in: eas login
echo.
echo Press any key to continue...
pause >nul

cd /d "%~dp0"

echo.
echo Checking EAS CLI installation...
where eas >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ERROR: EAS CLI is not installed!
    echo.
    echo Installing EAS CLI globally...
    npm install -g eas-cli
    if %errorlevel% neq 0 (
        echo.
        echo Failed to install EAS CLI. Please install manually:
        echo npm install -g eas-cli
        pause
        exit /b 1
    )
)

echo.
echo Checking if logged in to Expo...
eas whoami >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo You are not logged in to Expo.
    echo Please log in now...
    eas login
    if %errorlevel% neq 0 (
        echo.
        echo Failed to log in. Please try again.
        pause
        exit /b 1
    )
)

echo.
echo Building APK...
echo.

eas build --platform android --profile preview

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo  Build Complete!
    echo ========================================
    echo.
    echo Check the link above to download your APK.
    echo The APK will be available for download from Expo's servers.
    echo.
) else (
    echo.
    echo ========================================
    echo  Build Failed!
    echo ========================================
    echo.
    echo Please check the error messages above.
    echo.
)

pause

