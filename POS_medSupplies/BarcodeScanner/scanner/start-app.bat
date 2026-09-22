@echo off
echo ========================================
echo  Starting Barcode Scanner App
echo ========================================
echo.
echo This will start the Expo development server.
echo.
echo Make sure you have:
echo 1. Expo Go app installed on your phone
echo 2. Phone and computer on same WiFi network
echo.
echo Press any key to start...
pause >nul

cd /d "%~dp0"
echo.
echo Installing dependencies (if needed)...
call npm install

echo.
echo Starting Expo development server...
echo.
echo When you see the QR code:
echo - Android: Open Expo Go app and scan QR code
echo - iPhone: Open Camera app and tap the notification
echo.
echo Press Ctrl+C to stop the server
echo.

npm start

pause

