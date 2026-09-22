/**
 * API Configuration
 * 
 * IMPORTANT: For mobile devices/emulators, 'localhost' will NOT work!
 * 
 * Configuration options:
 * 1. For Android Emulator: Use 'http://10.0.2.2:8000' (special alias for host machine)
 * 2. For iOS Simulator: Use 'http://localhost:8000' (works on simulator)
 * 3. For Physical Devices: Use your computer's IP address (e.g., 'http://192.168.1.101:8000')
 * 4. For Production: Use your production domain (e.g., 'https://redempmedsupplies.com')
 * 
 * To find your IP address:
 * - Windows: Run 'ipconfig' and look for IPv4 Address
 * - Mac/Linux: Run 'ifconfig' and look for inet address
 * 
 * Make sure your Laravel server is accessible from your device/emulator!
 */

// API Base URL - Change this to your actual server URL
export const API_BASE_URL = __DEV__ 
  ? 'http://192.168.8.107:8000' // Local development server with IP and port
  : 'https://redempmedsupplies.com'; // Production server

// API Endpoints
export const API_ENDPOINTS = {
  // Test endpoints
  testConnection: `${API_BASE_URL}/api/connection-test`,
  testScannedProducts: `${API_BASE_URL}/api/scanned-products/test`,
  
  // Barcode lookup
  barcodeLookup: (barcode: string) => `${API_BASE_URL}/barcodes/lookup?value=${encodeURIComponent(barcode)}`,
  
  // Scanned products
  scannedProducts: `${API_BASE_URL}/api/scanned-products`,
  
  // Cashier endpoints
  cashierScan: `${API_BASE_URL}/api/cashier/scan`,
  cashierScanItems: `${API_BASE_URL}/api/cashier/scan/items`,
  cashierIdCapture: `${API_BASE_URL}/api/cashier/id-capture`,
  cashierIdLatest: `${API_BASE_URL}/api/cashier/id-latest`,
};

// Server setup instructions
export const SERVER_SETUP = {
  developmentUrl: __DEV__ ? API_BASE_URL : null,
  productionUrl: 'https://redempmedsupplies.com',
  notes: [
    __DEV__ 
      ? `Development server: ${API_BASE_URL} - Make sure Laravel server is running with: php artisan serve --host=0.0.0.0 --port=8000`
      : 'Production server is hosted at https://redempmedsupplies.com',
    'Ensure your device and server are on the same network (for development)',
    'All API endpoints are available at the configured domain',
  ],
};

