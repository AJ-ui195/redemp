# BarcodeScanner App - Quick Fix for Network Error

## ✅ IP Address Updated to: **192.168.1.5**

All API endpoints have been updated to use your new IP address.

## 🔧 Steps to Fix "Network request failed" Error:

### 1. Make Sure Server is Running
```bash
cd C:\xampp\htdocs\POS_medSupplies
php artisan serve --host=0.0.0.0 --port=8000
```

**Important:** Use `--host=0.0.0.0` (not 127.0.0.1) so the server is accessible from your phone!

### 2. Verify Server is Accessible
Open a browser and visit:
```
http://192.168.1.5:8000
```

You should see your Laravel application.

### 3. Rebuild the Scanner App

#### Option A: Quick Restart (Try this first)
1. **Close the scanner app** completely on your phone
2. **Stop the Metro bundler** (if running in terminal, press Ctrl+C)
3. **Clear cache and restart:**
   ```bash
   cd C:\xampp\htdocs\POS_medSupplies\BarcodeScanner\scanner
   npx expo start --clear
   ```
4. **Scan the QR code** again with Expo Go app

#### Option B: Full Rebuild (if Option A doesn't work)
```bash
cd C:\xampp\htdocs\POS_medSupplies\BarcodeScanner\scanner
npm install
npx expo start --clear
```

### 4. Test Connection
When the app loads:
1. Point camera at any barcode
2. Watch the console for connection logs
3. If you see connection errors, check:
   - ✅ Phone and computer are on the same WiFi
   - ✅ Firewall allows port 8000
   - ✅ Server is running with `--host=0.0.0.0`

## 📱 Configuration File

The IP address is now centrally managed in:
```
BarcodeScanner/scanner/config/api.ts
```

**To change IP in the future:**
1. Open `config/api.ts`
2. Change `SERVER_IP` to your new IP
3. Rebuild the app (Option A above)

## 🧪 Quick Test

After starting the app, test the connection by visiting this URL in a browser:
```
http://192.168.1.5:8000/api/connection-test
```

Should return:
```json
{
  "success": true,
  "message": "Server is reachable!"
}
```

## 🔥 Troubleshooting

### Error: "Network request failed"
- **Cause:** Phone can't reach the server
- **Fix:** Make sure both devices are on the same WiFi network

### Error: "Cannot reach server"
- **Cause:** Server not running or wrong IP
- **Fix:** Restart server with `--host=0.0.0.0 --port=8000`

### Error: "Connection timeout"
- **Cause:** Firewall blocking port 8000
- **Fix:** Allow port 8000 in Windows Firewall

### QR Code won't scan
- **Cause:** Old cached version of app
- **Fix:** Run `npx expo start --clear`

## ✨ All Updated Files:
- ✅ `config/api.ts` (NEW - central config)
- ✅ `app/(tabs)/index.tsx` 
- ✅ `app/scanner.tsx`
- ✅ `components/barcode-scanner.tsx`

---
**Current IP:** 192.168.1.5:8000
**Last Updated:** December 21, 2025

