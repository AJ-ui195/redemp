# Fix "Failed to download remote update" Error in Expo Go

## The Problem
When you scan the QR code with Expo Go, you see:
```
Uncaught Error: java.io.IOException: Failed to download remote update
```

This means Expo Go cannot connect to your development server.

## Solution 1: Use Tunnel Mode (Most Reliable)

Tunnel mode works even if your phone and computer are on different networks:

```powershell
cd Cashier\cashierScan
npx expo start --tunnel
```

Then scan the QR code again. Tunnel mode is slower but more reliable.

## Solution 2: Use LAN Mode (Same WiFi Network)

Make sure your phone and computer are on the **same WiFi network**:

```powershell
cd Cashier\cashierScan
npx expo start --lan
```

Then scan the QR code.

## Solution 3: Check Firewall Settings

Windows Firewall might be blocking Expo:

1. **Open Windows Defender Firewall:**
   - Press `Win + R`, type `firewall.cpl`, press Enter

2. **Allow Node.js through firewall:**
   - Click "Allow an app or feature through Windows Defender Firewall"
   - Find "Node.js" in the list
   - Make sure both "Private" and "Public" are checked
   - If Node.js is not in the list, click "Allow another app" and add it

3. **Or temporarily disable firewall** to test:
   - Right-click on WiFi icon → Open Network & Internet settings
   - Windows Security → Firewall & network protection
   - Turn off firewall temporarily to test

## Solution 4: Check Network Connection

1. **Verify same WiFi network:**
   - On your phone: Settings → WiFi → Check network name
   - On your computer: Check WiFi network name
   - They must be the **same network**

2. **Check your computer's IP address:**
   ```powershell
   ipconfig
   ```
   Look for "IPv4 Address" under your WiFi adapter (should be something like 192.168.1.4)

3. **Verify Expo is using correct IP:**
   When you run `npx expo start --lan`, it should show:
   ```
   Metro waiting on exp://192.168.1.4:8081
   ```
   Make sure this IP matches your computer's IP.

## Solution 5: Clear Expo Cache

```powershell
cd Cashier\cashierScan
npx expo start --clear
```

## Solution 6: Try Different Connection Methods

**Option A: Manual URL Entry**
1. When Expo starts, it shows a QR code and URL
2. Instead of scanning, manually enter the URL in Expo Go:
   - Open Expo Go app
   - Tap "Enter URL manually"
   - Type the URL shown in terminal (e.g., `exp://192.168.1.4:8081`)

**Option B: Use Development Build Instead**
If Expo Go keeps failing, build the app directly:
```powershell
npx expo run:android
```

## Solution 7: Check Antivirus/Network Security

- Temporarily disable antivirus to test
- Check if your router has any security settings blocking connections
- Try using mobile hotspot instead of WiFi

## Solution 8: Restart Everything

1. **Close Expo Go app** completely on your phone
2. **Stop Expo server** (Ctrl+C in terminal)
3. **Restart Expo:**
   ```powershell
   cd Cashier\cashierScan
   npx expo start --clear --tunnel
   ```
4. **Reopen Expo Go** and scan QR code again

## Quick Test: Verify Server is Accessible

Test if your phone can reach the server:
1. On your phone, open a web browser
2. Go to: `http://YOUR_IP:8081` (replace YOUR_IP with your computer's IP)
3. If you see a page, the connection works
4. If not, there's a network/firewall issue

## Most Common Fix

Try this first:
```powershell
cd Cashier\cashierScan
npx expo start --clear --tunnel
```

Tunnel mode usually works even with network issues!
