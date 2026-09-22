# Build APK for Cashier Scanner App

This guide will help you build an APK file for the Cashier Scanner app.

## Prerequisites

1. **Node.js and npm** installed
2. **Expo account** (free) - Sign up at https://expo.dev
3. **EAS CLI** (Expo Application Services CLI)

## Step 1: Install EAS CLI

Open a terminal/command prompt and run:

```bash
npm install -g eas-cli
```

## Step 2: Login to Expo

```bash
eas login
```

Enter your Expo account credentials when prompted.

## Step 3: Build APK

### Option A: Using the Batch Script (Windows)

1. Double-click `build-apk.bat`
2. Follow the on-screen instructions
3. Wait for the build to complete (10-20 minutes)

### Option B: Using Command Line

Navigate to the cashierScan directory:

```bash
cd Cashier/cashierScan
```

Then run:

```bash
eas build --platform android --profile preview
```

## Step 4: Download APK

After the build completes:

1. You'll see a link in the terminal output
2. Open the link in your browser
3. Download the APK file
4. Transfer to your Android device
5. Install the APK (you may need to enable "Install from Unknown Sources" in Android settings)

## Build Profiles

- **preview**: Creates an APK file for testing (no Google Play Store)
- **production**: Creates a signed APK for Google Play Store

## Troubleshooting

### "EAS CLI not found"
- Install EAS CLI: `npm install -g eas-cli`
- Make sure npm is in your PATH

### "Not logged in"
- Run: `eas login`
- Create a free account at https://expo.dev if you don't have one

### Build fails
- Check your internet connection
- Make sure all dependencies are installed: `npm install`
- Verify `app.json` and `eas.json` are correct

### APK won't install
- Enable "Install from Unknown Sources" in Android settings
- Make sure the APK file is not corrupted (re-download if needed)

## Notes

- The first build may take longer (20-30 minutes)
- Subsequent builds are usually faster (10-15 minutes)
- The APK file will be available for download for 30 days
- Builds are done on Expo's cloud servers (no local Android SDK needed)

