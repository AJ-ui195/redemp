# Fix "adb.exe: device offline" Error

## Quick Fix Steps

### Step 1: Restart ADB Server
```powershell
adb kill-server
adb start-server
adb devices
```

### Step 2: Revoke USB Debugging Authorizations
On your phone:
1. Go to **Settings** → **Developer options**
2. Tap **Revoke USB debugging authorizations**
3. Disconnect and reconnect your phone
4. When prompted on your phone, tap **Allow** or **OK** to authorize the computer

### Step 3: Check USB Connection Mode
On your phone, when connected:
1. Pull down the notification panel
2. Tap the USB notification
3. Select **File Transfer** or **MTP** mode (NOT "Charging only")

### Step 4: Try Different USB Port/Cable
- Try a different USB port on your laptop
- Try a different USB cable (some cables are charge-only)
- Use a USB 2.0 port if available (sometimes more reliable than USB 3.0)

### Step 5: Enable Wireless Debugging (Alternative)
Since you mentioned you have wireless debugging enabled:

1. **On your phone:**
   - Go to **Settings** → **Developer options** → **Wireless debugging**
   - Tap **Pair device with pairing code**
   - Note the IP address and port (e.g., 192.168.1.100:12345)
   - Note the pairing code

2. **On your computer:**
   ```powershell
   adb pair <IP_ADDRESS>:<PORT>
   # Enter the pairing code when prompted
   ```

3. **Then connect:**
   ```powershell
   adb connect <IP_ADDRESS>:<PORT>
   adb devices
   ```

### Step 6: Check USB Drivers
If using Windows:
1. Open **Device Manager**
2. Look for your phone under "Portable Devices" or "Other devices"
3. If there's a yellow warning, right-click → **Update driver**
4. Or install your phone manufacturer's USB drivers

### Step 7: Full Reset
```powershell
# Kill ADB
adb kill-server

# Unplug your phone

# Wait 5 seconds, then plug it back in

# Start ADB
adb start-server

# Check devices
adb devices
```

## Verify Connection
After fixing, run:
```powershell
adb devices
```

You should see:
```
List of devices attached
13195704AE006839    device
```

The word "device" (not "offline" or "unauthorized") means it's working!

## Then Try Building Again
```powershell
npx expo run:android
```

## Common Issues

**"unauthorized" instead of "offline":**
- You need to authorize the computer on your phone
- Check your phone screen for the authorization prompt

**Device keeps disconnecting:**
- Try a different USB cable
- Disable USB power saving in Windows Device Manager
- Check if your phone has "USB debugging" toggle enabled

**Nothing shows in `adb devices`:**
- Make sure USB debugging is enabled on your phone
- Try revoking and re-authorizing
- Check USB drivers
