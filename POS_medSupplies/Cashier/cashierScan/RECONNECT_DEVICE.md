# Reconnect Your Android Device

## Your device is not showing in ADB. Follow these steps:

### Step 1: Check Physical Connection
1. **Unplug your phone** from the USB cable
2. **Wait 5 seconds**
3. **Plug it back in**
4. Make sure you're using a **data cable** (not charge-only)

### Step 2: Check USB Connection Mode on Phone
1. **Pull down the notification panel** on your phone
2. Look for a **USB notification** (may say "Charging this device via USB")
3. **Tap the notification**
4. Select **"File Transfer"** or **"MTP"** mode
   - ⚠️ NOT "Charging only" - this won't work!

### Step 3: Revoke and Re-authorize USB Debugging
1. On your phone: **Settings** → **Developer options**
2. Tap **"Revoke USB debugging authorizations"**
3. **Disconnect** your phone
4. **Reconnect** your phone
5. You should see a popup: **"Allow USB debugging?"**
6. Check **"Always allow from this computer"**
7. Tap **"Allow"** or **"OK"**

### Step 4: Verify Connection
Run this command:
```powershell
C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe devices
```

You should see:
```
List of devices attached
XXXXXXXXXXXXX    device
```

### Step 5: If Still Not Working - Try Wireless Debugging

Since you mentioned you have wireless debugging enabled:

1. **On your phone:**
   - Settings → Developer options → **Wireless debugging**
   - Make sure it's **ON**
   - Tap **"Pair device with pairing code"**
   - Note the **IP address**, **port**, and **pairing code**

2. **On your computer:**
   ```powershell
   # Replace with your actual IP, port, and code
   C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe pair 192.168.1.100:12345
   # Enter the pairing code when prompted
   
   # Then connect
   C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe connect 192.168.1.100:12345
   
   # Verify
   C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe devices
   ```

### Step 6: Check USB Drivers (Windows)
If your device still doesn't show:

1. Open **Device Manager** (Win + X → Device Manager)
2. Look for your phone under:
   - **Portable Devices**
   - **Other devices** (with yellow warning)
   - **Android Phone**
3. If you see a yellow warning:
   - Right-click → **Update driver**
   - Choose **"Browse my computer"**
   - Or install your phone manufacturer's USB drivers

### Step 7: Restart ADB
```powershell
C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe kill-server
C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe start-server
C:\Users\User\AppData\Local\Android\Sdk\platform-tools\adb.exe devices
```

### Step 8: Try Different USB Port/Cable
- Try a **different USB port** on your laptop
- Try a **different USB cable** (some are charge-only)
- Use a **USB 2.0 port** if available (sometimes more reliable)

## Once Device Shows Up

After `adb devices` shows your device, try building again:
```powershell
cd Cashier\cashierScan
npx expo run:android
```

## Alternative: Use Expo Go (No USB Needed!)

If you're having trouble with USB debugging, you can use Expo Go over WiFi:

```powershell
cd Cashier\cashierScan
npx expo start --lan
```

Then scan the QR code with Expo Go app on your phone. This works over WiFi and doesn't require USB debugging!
