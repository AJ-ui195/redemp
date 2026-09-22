# Cashier Scanner App Setup

This is a React Native/Expo app for scanning barcodes and sending them to the POS cashier dashboard.

## Prerequisites

- Node.js installed
- Expo CLI (`npm install -g expo-cli`)
- For iOS: macOS with Xcode
- For Android: Android Studio

## Installation

1. Install dependencies:
```bash
npm install
```

2. Configure API URL:
   - Open `constants/api.ts`
   - Change `API_BASE_URL` to your server URL:
     - For local development: Use your computer's IP address (e.g., `http://192.168.1.100`)
     - For production: Use your production domain
   - Find your IP address:
     - Windows: Run `ipconfig` and look for IPv4 Address
     - Mac/Linux: Run `ifconfig` and look for inet address

## Running the App

1. Start the development server:
```bash
npm start
# or
npx expo start
```

2. Run on your device:
   - **iOS**: Press `i` in the terminal or scan the QR code with Camera app
   - **Android**: Press `a` in the terminal or scan the QR code with Expo Go app
   - **Web**: Press `w` in the terminal

## Using the Scanner

1. Open the app and tap "Start Barcode Scanner"
2. Grant camera permission when prompted
3. Point the camera at a barcode
4. The app will automatically:
   - Scan the barcode
   - Send it to the server (`/api/cashier/scan`)
   - Display success/error messages
   - Show the last scanned item

## API Configuration

The app sends scanned barcodes to:
- Endpoint: `POST /api/cashier/scan`
- Body: `{ barcode_value: string, barcode_type: string }`

Make sure your Laravel server is running and accessible from your device/emulator.

## Troubleshooting

### Camera not working
- Make sure you granted camera permissions
- For iOS: Check Settings > Privacy > Camera
- For Android: Check App Settings > Permissions

### Cannot connect to server
- Verify the API_BASE_URL in `constants/api.ts` is correct
- Make sure your device/emulator is on the same network as the server
- For Android emulator, use `10.0.2.2` instead of `localhost`
- For iOS simulator, use `localhost` or your Mac's IP address
- Check firewall settings on your server

### Barcode not recognized
- Make sure the barcode is clear and well-lit
- Hold the device steady
- Move closer or farther from the barcode

## Building for Production

```bash
# Build for iOS
eas build --platform ios

# Build for Android
eas build --platform android
```

## Project Structure

```
app/
  scanner.tsx          # Scanner screen
  (tabs)/
    index.tsx          # Home screen
constants/
  api.ts              # API configuration
```

## Notes

- The scanner automatically sends scanned barcodes to the cashier dashboard
- Scanned items appear in the cashier cart automatically (via polling)
- The app supports multiple barcode formats (CODE128, CODE39, EAN, UPC, etc.)

