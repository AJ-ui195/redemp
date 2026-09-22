# Troubleshooting Expo Go Connection Issues

## Error: "Failed to download remote update"

This error occurs when Expo Go cannot connect to the Expo development server. Follow these steps:

### Step 1: Clear Expo Cache
```bash
cd Cashier/cashierScan
npx expo start --clear
```

### Step 2: Check Connection Mode
Try different connection modes:

**Option A: LAN Mode (Recommended for same WiFi network)**
```bash
npx expo start --lan
```

**Option B: Tunnel Mode (If LAN doesn't work)**
```bash
npx expo start --tunnel
```

**Option C: Localhost (Only for emulator/simulator)**
```bash
npx expo start --localhost
```

### Step 3: Verify Network Connection
1. **Check if your device and computer are on the same WiFi network**
2. **Check your computer's IP address:**
   - Windows: Run `ipconfig` in Command Prompt
   - Mac/Linux: Run `ifconfig` in Terminal
   - Look for IPv4 Address (e.g., 192.168.1.4)

### Step 4: Check Firewall Settings
- **Windows Firewall**: Allow Node.js and Expo through firewall
- **Antivirus**: Temporarily disable to test if it's blocking connections
- **Router**: Check if router has any firewall rules blocking connections

### Step 5: Restart Everything
1. Close Expo Go app completely
2. Stop the Expo server (Ctrl+C in terminal)
3. Restart Expo server: `npx expo start --clear`
4. Reopen Expo Go and scan QR code again

### Step 6: Use Development Build (If Expo Go still fails)
If Expo Go continues to have issues, consider using a development build:
```bash
npx expo run:android
# or
npx expo run:ios
```

### Step 7: Check Expo CLI Version
Make sure you have the latest Expo CLI:
```bash
npm install -g expo-cli@latest
# or
npm install -g @expo/cli@latest
```

### Step 8: Network-Specific Issues

**If using mobile data:**
- Switch to WiFi (Expo Go requires same network as development server)

**If using VPN:**
- Disable VPN temporarily to test

**If using corporate/school network:**
- May have restrictions blocking connections
- Try using mobile hotspot instead

### Step 9: Alternative - Use Expo Dev Tools
1. Open Expo Dev Tools in browser: http://localhost:19002
2. Try connecting from there
3. Check for any error messages

### Step 10: Check Port Availability
Make sure ports 19000, 19001, 19002 are not blocked:
```bash
# Windows
netstat -ano | findstr :19000

# Mac/Linux
lsof -i :19000
```

### Common Solutions Summary:
1. ✅ Clear cache: `npx expo start --clear`
2. ✅ Try tunnel mode: `npx expo start --tunnel`
3. ✅ Ensure same WiFi network
4. ✅ Check firewall settings
5. ✅ Restart Expo server and app
6. ✅ Update Expo CLI to latest version

### Still Having Issues?
- Check Expo documentation: https://docs.expo.dev/
- Check Expo status: https://status.expo.dev/
- Try creating a new Expo project to test if it's project-specific
