/**
 * API Configuration
 * 
 * IMPORTANT: For mobile devices/emulators, 'localhost' will NOT work!
 * 
 * Configuration options:
 * 1. For Android Emulator: Use 'http://10.0.2.2' (special alias for host machine)
 * 2. For iOS Simulator: Use 'http://localhost' (works on simulator)
 * 3. For Physical Devices: Use your computer's IP address (e.g., 'http://192.168.1.101:8000')
 * 4. For Production: Use your production domain (e.g., 'https://your-domain.com')
 * 
 * To find your IP address:
 * - Windows: Run 'ipconfig' and look for IPv4 Address
 * - Mac/Linux: Run 'ifconfig' and look for inet address
 * 
 * Make sure your Laravel server is accessible from your device/emulator!
 */

// IMPORTANT: Change this to your actual server URL
// For Android Emulator, use: 'http://10.0.2.2'
// For Physical Device, use: 'http://YOUR_IP_ADDRESS' (e.g., 'http://192.168.1.100')
// For iOS Simulator, use: 'http://localhost'
// For Production: Use your production domain
export const API_BASE_URL = 'http://192.168.8.107:8000';

// API Endpoints
export const API_ENDPOINTS = {
  // Cashier scan endpoint - sends scanned barcode to cashier dashboard
  cashierScan: `${API_BASE_URL}/api/cashier/scan`,
  
  // Get scanned items (for checking what's been scanned)
  cashierScanItems: `${API_BASE_URL}/api/cashier/scan/items`,
  
  // Update quantity for a scanned item
  cashierScanQuantity: `${API_BASE_URL}/api/cashier/scan/quantity`,
  
  // Remove a scanned item
  cashierScanRemove: `${API_BASE_URL}/api/cashier/scan/remove`,
  
  // Test connection
  testConnection: `${API_BASE_URL}/api/connection-test`,
  
  // ID capture endpoint - sends captured Senior/PWD ID to cashier dashboard
  idCapture: `${API_BASE_URL}/api/cashier/id-capture`,
  
  // Get latest captured ID from cashier dashboard
  idLatest: `${API_BASE_URL}/api/cashier/id-latest`,
};

