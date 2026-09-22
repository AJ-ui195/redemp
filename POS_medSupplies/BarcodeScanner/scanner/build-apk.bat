@echo off
echo ========================================
echo  Build Barcode Scanner APK
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
echo Building APK...
echo.

eas build --platform android --profile preview

echo.
echo Build complete! Check the link above to download your APK.
echo.
pause

