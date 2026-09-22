# Script to fix ADB "device offline" error
# Run this script to restart ADB and reconnect your device

$adbPath = "C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe"

if (-not (Test-Path $adbPath)) {
    Write-Host "ADB not found at: $adbPath" -ForegroundColor Red
    Write-Host "Please update the path in this script to your Android SDK platform-tools location" -ForegroundColor Yellow
    exit 1
}

Write-Host "Stopping ADB server..." -ForegroundColor Yellow
& $adbPath kill-server

Start-Sleep -Seconds 2

Write-Host "Starting ADB server..." -ForegroundColor Yellow
& $adbPath start-server

Start-Sleep -Seconds 2

Write-Host "`nChecking connected devices..." -ForegroundColor Yellow
& $adbPath devices

Write-Host "`nIf your device shows 'offline' or 'unauthorized':" -ForegroundColor Cyan
Write-Host "1. On your phone: Go to Settings → Developer options" -ForegroundColor White
Write-Host "2. Tap 'Revoke USB debugging authorizations'" -ForegroundColor White
Write-Host "3. Disconnect and reconnect your phone" -ForegroundColor White
Write-Host "4. When prompted on your phone, tap 'Allow' to authorize this computer" -ForegroundColor White
Write-Host "5. Make sure USB connection mode is set to 'File Transfer' or 'MTP'" -ForegroundColor White
Write-Host "`nThen run this script again or run: adb devices" -ForegroundColor Cyan
