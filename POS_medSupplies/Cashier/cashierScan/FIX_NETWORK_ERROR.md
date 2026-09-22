# Fix "Network request failed" Error

## The Problem
Your app is trying to connect to `http://192.168.1.4:8000` but getting "Network request failed".

This happens because your Laravel server is only listening on `127.0.0.1:8000` (localhost), which is not accessible from your phone.

## Solution: Restart Laravel Server with Correct Host

### Step 1: Stop Current Server
If your Laravel server is running, stop it (press `Ctrl+C` in the terminal where it's running).

### Step 2: Start Server on All Interfaces
Navigate to your Laravel project root and run:

```bash
cd C:\xampp\htdocs\POS_medSupplies
php artisan serve --host=0.0.0.0 --port=8000
```

**Important:** Use `--host=0.0.0.0` (NOT `--host=127.0.0.1` or `--host=localhost`)

This makes the server accessible from your phone on the same network.

### Step 3: Verify Server is Running
You should see:
```
Laravel development server started: http://0.0.0.0:8000
```

### Step 4: Test from Phone
On your phone's browser, try accessing:
```
http://192.168.1.4:8000/api/connection-test
```

If you see a JSON response, the server is accessible!

### Step 5: Try Scanning Again
Now try scanning a barcode in the app - it should work!

## Alternative: Check Firewall

If it still doesn't work, Windows Firewall might be blocking port 8000:

1. **Open Windows Defender Firewall:**
   - Press `Win + R`, type `firewall.cpl`, press Enter

2. **Allow port 8000:**
   - Click "Advanced settings"
   - Click "Inbound Rules" → "New Rule"
   - Select "Port" → Next
   - Select "TCP" and enter port `8000`
   - Select "Allow the connection"
   - Check all profiles (Domain, Private, Public)
   - Name it "Laravel Development Server"
   - Click Finish

## Quick Command Summary

```bash
# Stop current server (Ctrl+C)

# Start server accessible from network
cd C:\xampp\htdocs\POS_medSupplies
php artisan serve --host=0.0.0.0 --port=8000
```

## Verify It's Working

After starting the server, check:
```powershell
netstat -ano | Select-String ":8000"
```

You should see:
```
TCP    0.0.0.0:8000         0.0.0.0:0              LISTENING
```

NOT:
```
TCP    127.0.0.1:8000       ...  (This won't work!)
```
