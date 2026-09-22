import React, { useState, useEffect } from 'react';
import { StyleSheet, View, Text, TouchableOpacity, Alert, ScrollView, ActivityIndicator } from 'react-native';
import { CameraView, useCameraPermissions, BarcodeScanningResult } from 'expo-camera';
import { ThemedText } from './themed-text';
import { ThemedView } from './themed-view';
import { API_BASE_URL, API_ENDPOINTS } from '@/config/api';

interface BarcodeScannerProps {
  onScan?: (data: string, type: string) => void;
  onAddItem?: (data: string, type: string) => Promise<void> | void;
  onCancel?: () => void;
  apiBaseUrl?: string;
}

interface BarcodeInfo {
  item_name: string | null;
  price: number | null;
  price_type: string | null;
  unit: string | null;
  expiration_date: string | null;
  barcode_value: string;
  active_status: string | null;
  description: string | null;
  quantity_on_hand: number | null;
  brand: string | null;
}

export default function BarcodeScanner({ onScan, onAddItem, onCancel, apiBaseUrl = API_BASE_URL }: BarcodeScannerProps) {
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  const [scannedData, setScannedData] = useState<string | null>(null);
  const [scannedType, setScannedType] = useState<string | null>(null);
  const [barcodeInfo, setBarcodeInfo] = useState<BarcodeInfo | null>(null);
  const [loadingInfo, setLoadingInfo] = useState(false);
  const [connectionError, setConnectionError] = useState<string | null>(null);
  
  // Debug: Log props on mount and when they change
  useEffect(() => {
    console.log('🔍 BarcodeScanner props updated:', {
      hasOnScan: !!onScan,
      hasOnAddItem: !!onAddItem,
      hasOnCancel: !!onCancel,
      apiBaseUrl: apiBaseUrl,
    });
  }, [onScan, onAddItem, onCancel, apiBaseUrl]);
  
  // Helper function for fetch with timeout
  const fetchWithTimeout = (url: string, options: RequestInit = {}, timeout: number = 30000): Promise<Response> => {
    return Promise.race([
      fetch(url, options),
      new Promise<Response>((_, reject) => {
        const timeoutId = setTimeout(() => {
          const timeoutError = new Error('Request timeout');
          (timeoutError as any).isTimeout = true;
          reject(timeoutError);
        }, timeout);
        // Clear timeout if fetch completes first (though Promise.race handles this)
      }),
    ]);
  };

  // Test connection on component mount (non-blocking, silent failures)
  useEffect(() => {
    // Run connection test silently - don't block if it fails
    testConnection().catch(() => {
      // Silently handle any uncaught errors - connection test is optional
    });
  }, [apiBaseUrl]);
  
  const testConnection = async () => {
    try {
      // First try simple connection test
      const simpleTestUrl = `${apiBaseUrl}/api/connection-test`;
      console.log('🔌 [TEST 1] Testing basic connection to:', simpleTestUrl);
      
      try {
        const simpleResponse = await fetchWithTimeout(simpleTestUrl, {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
        }, 10000); // 10 second timeout
        
        if (!simpleResponse.ok) {
          // If we get a response but it's not OK, server is reachable but endpoint might have issues
          console.warn(`⚠️ [TEST 1] Server responded with status ${simpleResponse.status}`);
          // Don't set error - server is reachable, just endpoint might be different
          setConnectionError(null);
          return;
        }
        
        const simpleData = await simpleResponse.json();
        console.log('✅ [TEST 1] Basic connection OK:', simpleData);
        
        // If basic test passes, clear any previous errors
        setConnectionError(null);
        
        // Try full test (non-blocking - don't fail if this doesn't work)
        try {
          const fullTestUrl = `${apiBaseUrl}/api/connection-test/full`;
          console.log('🔌 [TEST 2] Testing full connection to:', fullTestUrl);
          
          const fullResponse = await fetchWithTimeout(fullTestUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
          }, 10000);
          
          if (fullResponse.ok) {
            const fullData = await fullResponse.json();
            console.log('✅ [TEST 2] Full connection test:', fullData);
            
            if (fullData.success && fullData.tests?.barcodes_table) {
              const barcodeCount = fullData.tests.barcodes_table.total_barcodes || 0;
              console.log(`✅ Connected! Found ${barcodeCount} barcodes in database`);
            }
            // Success - no error to set
            setConnectionError(null);
          } else {
            // Full test failed but basic test passed - server is reachable
            console.warn(`⚠️ [TEST 2] Full test returned status ${fullResponse.status}, but basic connection works`);
            setConnectionError(null); // Clear error since basic connection works
          }
        } catch (fullTestError: any) {
          // Full test failed but that's OK - basic connection works
          console.warn('⚠️ [TEST 2] Full test failed, but basic connection is OK:', fullTestError.message);
          setConnectionError(null); // Don't show error if basic connection works
        }
      } catch (testError: any) {
        // Check if it's a timeout (expected if server isn't running)
        const isTimeout = testError.message?.includes('timeout') || 
                         testError.message?.includes('Request timeout');
        
        // Check if it's a real network error (not just timeout)
        const isNetworkError = testError.message?.includes('Network request failed') || 
                               testError.message?.includes('Failed to fetch') ||
                               testError.name === 'TypeError';
        
        if (isTimeout) {
          // Timeout is expected if server isn't running - log quietly
          console.log('⏱️ [TEST 1] Connection test timeout (server may not be running)');
          // Only show error if user explicitly wants to see connection status
          // For now, don't show error for timeouts - let user try scanning instead
          setConnectionError(null);
        } else if (isNetworkError) {
          // Real network error (not timeout)
          console.warn('⚠️ [TEST 1] Network error:', testError.message);
          const errorMsg = `Cannot reach server at ${apiBaseUrl}\n\nTroubleshooting:\n1. Server running: php artisan serve --host=0.0.0.0 --port=8000\n2. Same WiFi network\n3. Firewall allows port 8000\n4. IP address correct: ${apiBaseUrl}`;
          setConnectionError(errorMsg);
        } else {
          // Other errors - might be temporary, don't show error
          console.log('ℹ️ [TEST 1] Connection test info:', testError.message);
          setConnectionError(null); // Don't show error for non-network issues
        }
      }
    } catch (error: any) {
      // Outer catch - only for unexpected errors
      const isTimeout = error.message?.includes('timeout') || error.message?.includes('Request timeout');
      
      if (isTimeout) {
        // Timeout is expected - don't log as error
        console.log('⏱️ Connection test timeout (server may not be running)');
        setConnectionError(null);
      } else if (error.message?.includes('Network request failed')) {
        // Real network error
        console.warn('⚠️ Connection test network error:', error.message);
        const errorMsg = `Cannot reach server at ${apiBaseUrl}\n\nTroubleshooting:\n1. Server running: php artisan serve --host=0.0.0.0 --port=8000\n2. Same WiFi network\n3. Firewall allows port 8000\n4. IP address correct: ${apiBaseUrl}`;
        setConnectionError(errorMsg);
      } else {
        // Don't show error for unexpected non-network issues
        console.log('ℹ️ Connection test info:', error.message);
        setConnectionError(null);
      }
    }
  };

  useEffect(() => {
    if (permission && !permission.granted) {
      requestPermission();
    }
  }, [permission, requestPermission]);

  const handleBarCodeScanned = (result: BarcodeScanningResult) => {
    if (!scanned && result.data) {
      const scannedValue = result.data.trim(); // Trim whitespace
      console.log('📷 Barcode scanned:', {
        value: scannedValue,
        type: result.type,
        length: scannedValue.length,
      });
      
      setScanned(true);
      setScannedData(scannedValue);
      setScannedType(result.type);
      
      if (onScan) {
        onScan(scannedValue, result.type);
      }
      
      // Fetch barcode information from database immediately
      fetchBarcodeInfo(scannedValue);
    }
  };

  const fetchBarcodeInfo = async (barcodeValue: string) => {
    if (!barcodeValue || !barcodeValue.trim()) {
      console.warn('⚠️ Empty barcode value, skipping fetch');
      return;
    }
    
    const cleanBarcodeValue = barcodeValue.trim();
    setLoadingInfo(true);
    setConnectionError(null);
    
    // Try simple endpoint first (easier to parse)
    const trySimpleEndpoint = async (): Promise<boolean> => {
      try {
        const url = `${apiBaseUrl}/barcodes/lookup-simple?value=${encodeURIComponent(cleanBarcodeValue)}`;
        console.log('🔍 [SIMPLE] Trying simple endpoint:', url);
        
        const response = await fetchWithTimeout(url, {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
        }, 30000); // 30 second timeout for barcode lookup

        if (!response.ok) {
          console.warn('⚠️ [SIMPLE] Response not OK:', response.status);
          return false;
        }

        const data = await response.json();
        console.log('📦 [SIMPLE] Response:', data);

        if (data.found === true && (data.item_name || data.price != null)) {
          console.log('✅ [SIMPLE] Item found!');
          setBarcodeInfo({
            item_name: data.item_name || null,
            price: data.price != null ? parseFloat(data.price) : null,
            price_type: data.price_type || null,
            unit: data.unit || null,
            expiration_date: data.expiration_date || null,
            barcode_value: data.barcode_value || cleanBarcodeValue,
            active_status: data.active_status || null,
            description: data.description || null,
            quantity_on_hand: data.quantity_on_hand != null ? parseFloat(data.quantity_on_hand) : null,
            brand: data.brand || null,
          });
          return true;
        }
        return false;
      } catch (error: any) {
        const isTimeout = error.message?.includes('timeout') || error.message?.includes('Request timeout');
        if (isTimeout) {
          // Don't log here - let main handler log it once
          throw error; // Re-throw timeout so we can handle it differently
        }
        console.warn('⚠️ [SIMPLE] Error:', error.message);
        return false;
      }
    };
    
    // Try standard endpoint
    const tryStandardEndpoint = async () => {
      try {
        const url = `${apiBaseUrl}/barcodes/lookup?value=${encodeURIComponent(cleanBarcodeValue)}`;
        console.log('🔍 [STANDARD] Trying standard endpoint:', url);
        
        const response = await fetchWithTimeout(url, {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
        }, 30000); // 30 second timeout for barcode lookup

        if (!response.ok) {
          const errorText = await response.text();
          throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const responseText = await response.text();
        const data = JSON.parse(responseText);
        
        console.log('📊 [STANDARD] Response:', {
          success: data.success,
          found: data.found,
          hasBarcode: !!data.barcode
        });

        if (data.success === true && data.found === true && data.barcode) {
          console.log('✅ [STANDARD] Item found!');
          const b = data.barcode;
          setBarcodeInfo({
            item_name: b.item_name || b.name || null,
            price: b.price != null ? parseFloat(b.price) : null,
            price_type: b.price_type || null,
            unit: b.unit || null,
            expiration_date: b.expiration_date || null,
            barcode_value: cleanBarcodeValue,
            active_status: b.active_status || null,
            description: b.description || null,
            quantity_on_hand: b.quantity_on_hand != null ? parseFloat(b.quantity_on_hand) : null,
            brand: b.brand || null,
          });
          return true;
        }
        // Check if response explicitly says "not found"
        if (data.success === true && data.found === false) {
          console.log('ℹ️ [STANDARD] Server confirmed barcode not found');
          return false; // Explicitly not found
        }
        return false;
      } catch (error: any) {
        const isTimeout = error.message?.includes('timeout') || error.message?.includes('Request timeout');
        if (isTimeout) {
          // Don't log here - let main handler log it once
          throw error; // Re-throw timeout so we can handle it differently
        }
        console.warn('⚠️ [STANDARD] Error:', error.message);
        return false;
      }
    };
    
    try {
      // Prefer Product lookup (standard), then flat simple endpoint
      const standardSuccess = await tryStandardEndpoint();
      if (standardSuccess) {
        setLoadingInfo(false);
        return;
      }

      const simpleSuccess = await trySimpleEndpoint();
      if (simpleSuccess) {
        setLoadingInfo(false);
        return;
      }
      
      // Both endpoints returned but barcode not found
      console.log('ℹ️ [NOT FOUND] Barcode not in database:', cleanBarcodeValue);
      setBarcodeInfo({
        item_name: null,
        price: null,
        price_type: null,
        unit: null,
        expiration_date: null,
        barcode_value: cleanBarcodeValue,
        active_status: null,
        description: null,
        quantity_on_hand: null,
        brand: null,
      });
      setLoadingInfo(false);
    } catch (error: any) {
      // Check if it's a timeout error
      const isTimeout = error.message?.includes('timeout') || 
                       error.message?.includes('Request timeout') ||
                       error.name === 'AbortError';
      
      // Handle timeout errors - don't show as "not found"
      if (isTimeout) {
        // Only log once, not for each endpoint attempt
        console.log('⏱️ Request timed out - server may be slow or unreachable');
        setConnectionError(`Request timed out.\n\nTroubleshooting:\n1) Make sure the server is running and reachable at ${apiBaseUrl}\n2) Verify device and server are on the same network\n3) Then try scanning again`);
        // Still show the barcode value so user knows what was scanned
        setBarcodeInfo({
          item_name: null,
          price: null,
          price_type: null,
          unit: null,
          expiration_date: null,
          barcode_value: cleanBarcodeValue,
          active_status: null,
          description: null,
          quantity_on_hand: null,
          brand: null,
        });
        setLoadingInfo(false);
        return; // Exit early - don't show as "not found"
      }
      
      // Handle network errors
      if (error.name === 'TypeError' && error.message?.includes('Network request failed')) {
        const errorMsg = `Cannot connect to server at ${apiBaseUrl}\n\nTroubleshooting:\n1. Server running: php artisan serve --host=0.0.0.0 --port=8000\n2. Same WiFi network\n3. Firewall allows port 8000`;
        console.warn('🌐 [NETWORK ERROR]', errorMsg);
        setConnectionError(errorMsg);
        setBarcodeInfo({
          item_name: null,
          price: null,
          price_type: null,
          unit: null,
          expiration_date: null,
          barcode_value: cleanBarcodeValue,
          active_status: null,
          description: null,
          quantity_on_hand: null,
          brand: null,
        });
        setLoadingInfo(false);
        return; // Exit early - don't show as "not found"
      }
      
      // Other errors - log but don't show as "not found"
      console.warn('⚠️ Error fetching barcode info:', error.message);
      setConnectionError(`Error: ${error.message || 'Unknown error'}`);
      setBarcodeInfo({
        item_name: null,
        price: null,
        price_type: null,
        unit: null,
        expiration_date: null,
        barcode_value: cleanBarcodeValue,
        active_status: null,
        description: null,
        quantity_on_hand: null,
        brand: null,
      });
      setLoadingInfo(false);
    }
  };

  // Show a simple loader while we are still checking camera permission
  if (!permission) {
    return (
      <ThemedView style={styles.container}>
        <View style={styles.loadingCenter}>
          <ActivityIndicator size="large" color="#4CAF50" />
          <ThemedText style={styles.loadingText}>Preparing scanner...</ThemedText>
        </View>
        <View style={styles.footer}>
          <ThemedText style={styles.footerText}>Powered By: Techies</ThemedText>
        </View>
      </ThemedView>
    );
  }

  if (!permission.granted) {
    return (
      <ThemedView style={styles.container}>
        <ThemedText style={styles.message}>Camera permission is required to scan barcodes.</ThemedText>
        <TouchableOpacity style={styles.button} onPress={requestPermission}>
          <Text style={styles.buttonText}>Grant Permission</Text>
        </TouchableOpacity>
        <View style={styles.footer}>
          <ThemedText style={styles.footerText}>Powered By: Techies</ThemedText>
        </View>
      </ThemedView>
    );
  }

  return (
    <View style={styles.container}>
      <CameraView
        style={styles.camera}
        facing="back"
        onBarcodeScanned={scanned ? undefined : handleBarCodeScanned}
        barcodeScannerSettings={{
          barcodeTypes: [
            'qr',
            'ean13',
            'ean8',
            'upc_a',
            'upc_e',
            'code128',
            'code39',
            'code93',
            'codabar',
            'itf14',
            'datamatrix',
            'aztec',
            'pdf417',
          ],
        }}
      />
      <View style={styles.overlay}>
        <View style={styles.scanArea}>
          <View style={[styles.corner, styles.topLeft]} />
          <View style={[styles.corner, styles.topRight]} />
          <View style={[styles.corner, styles.bottomLeft]} />
          <View style={[styles.corner, styles.bottomRight]} />
        </View>
        <ThemedText style={styles.instruction}>
          Position the barcode within the frame
        </ThemedText>
      </View>
      
      {scannedData && (
        <ThemedView style={styles.resultContainer}>
          <View style={styles.headerRow}>
            <ThemedText style={styles.resultLabel}>Item Information</ThemedText>
            <TouchableOpacity 
              style={styles.refreshButton}
              onPress={() => {
                if (scannedData) {
                  console.log('🔄 Manual refresh triggered');
                  fetchBarcodeInfo(scannedData);
                }
              }}
            >
              <Text style={styles.refreshButtonText}>🔄</Text>
            </TouchableOpacity>
          </View>
          
          {connectionError && (
            <View style={styles.errorBox}>
              <ThemedText style={styles.errorText}>⚠️ {connectionError}</ThemedText>
              <TouchableOpacity 
                style={styles.retryButton}
                onPress={() => {
                  console.log('🔄 Retrying connection test...');
                  setConnectionError(null);
                  testConnection();
                }}
              >
                <Text style={styles.retryButtonText}>🔄 Retry Connection</Text>
              </TouchableOpacity>
            </View>
          )}
          
          {loadingInfo ? (
            <ThemedText style={styles.loadingText}>Loading item details from database...</ThemedText>
          ) : barcodeInfo ? (
            <ScrollView style={styles.infoContainer} contentContainerStyle={styles.infoContentContainer}>
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Name Item:</ThemedText>
                <ThemedText style={styles.infoValue}>
                  {barcodeInfo.item_name || 'N/A'}
                </ThemedText>
              </View>
              
              {barcodeInfo.brand && (
                <View style={styles.infoRow}>
                  <ThemedText style={styles.infoLabel}>Brand:</ThemedText>
                  <ThemedText style={styles.infoValue}>
                    {barcodeInfo.brand}
                  </ThemedText>
                </View>
              )}
              
              {barcodeInfo.description && (
                <View style={styles.infoRow}>
                  <ThemedText style={styles.infoLabel}>Description:</ThemedText>
                  <ThemedText style={[styles.infoValue, styles.descriptionText]}>
                    {barcodeInfo.description}
                  </ThemedText>
                </View>
              )}
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Price:</ThemedText>
                <ThemedText style={styles.infoValue}>
                  {barcodeInfo.price !== null ? `₱${barcodeInfo.price.toFixed(2)}` : 'N/A'}
                </ThemedText>
              </View>
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Price Type:</ThemedText>
                <ThemedText style={styles.infoValue}>
                  {barcodeInfo.price_type ? barcodeInfo.price_type.charAt(0).toUpperCase() + barcodeInfo.price_type.slice(1) : 'N/A'}
                </ThemedText>
              </View>
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Unit:</ThemedText>
                <ThemedText style={styles.infoValue}>
                  {barcodeInfo.unit ? barcodeInfo.unit.toUpperCase() : 'N/A'}
                </ThemedText>
              </View>
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Quantity on Hand:</ThemedText>
                <ThemedText style={styles.infoValue}>
                  {barcodeInfo.quantity_on_hand !== null ? barcodeInfo.quantity_on_hand.toFixed(2) : 'N/A'}
                </ThemedText>
              </View>
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Expiration Date:</ThemedText>
                <ThemedText style={[styles.infoValue, barcodeInfo.expiration_date && new Date(barcodeInfo.expiration_date) < new Date() ? styles.expiredText : null]}>
                  {barcodeInfo.expiration_date ? new Date(barcodeInfo.expiration_date).toLocaleDateString() : 'N/A'}
                </ThemedText>
              </View>
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Active Status:</ThemedText>
                <ThemedText style={[styles.infoValue, barcodeInfo.active_status === 'Active' ? styles.activeStatus : styles.inactiveStatus]}>
                  {barcodeInfo.active_status || 'N/A'}
                </ThemedText>
              </View>
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Barcode Value:</ThemedText>
                <ThemedText style={[styles.infoValue, styles.barcodeValue]}>{barcodeInfo.barcode_value}</ThemedText>
              </View>
              
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Barcode Type:</ThemedText>
                <ThemedText style={styles.infoValue}>{scannedType || 'N/A'}</ThemedText>
              </View>
            </ScrollView>
          ) : (
            <ScrollView style={styles.infoContainer} contentContainerStyle={styles.infoContentContainer}>
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Barcode Value:</ThemedText>
                <ThemedText style={[styles.infoValue, styles.barcodeValue]}>{scannedData}</ThemedText>
              </View>
              <View style={styles.infoRow}>
                <ThemedText style={styles.infoLabel}>Barcode Type:</ThemedText>
                <ThemedText style={styles.infoValue}>{scannedType || 'N/A'}</ThemedText>
              </View>
              <ThemedText style={styles.warningText}>Item not found in database</ThemedText>
            </ScrollView>
          )}
          
          <View style={styles.buttonContainer}>
            <TouchableOpacity
              style={[styles.button, styles.cancelButton]}
              onPress={() => {
                setScanned(false);
                setScannedData(null);
                setScannedType(null);
                setBarcodeInfo(null);
                if (onCancel) {
                  onCancel();
                }
              }}
            >
              <Text style={styles.buttonText}>CANCEL</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.button, styles.addButton]}
              onPress={() => {
                console.log('🔘 ADD ITEM button pressed');
                console.log('   scannedData:', scannedData);
                console.log('   scannedType:', scannedType);
                console.log('   onAddItem type:', typeof onAddItem);
                console.log('   onAddItem value:', onAddItem);
                
                if (!scannedData) {
                  console.error('❌ scannedData is null or undefined');
                  Alert.alert('Error', 'No barcode data to add. Please scan again.');
                  return;
                }
                
                if (!onAddItem) {
                  console.error('❌ onAddItem callback is not available');
                  console.error('   Props received:', { onScan: !!onScan, onAddItem: !!onAddItem, onCancel: !!onCancel });
                  Alert.alert('Error', 'Add item function is not available. Please check app configuration.');
                  return;
                }
                
                console.log('✅ Calling onAddItem with:', { barcodeValue: scannedData, barcodeType: scannedType || '' });
                try {
                  const result = onAddItem(scannedData, scannedType || '');
                  // If onAddItem returns a promise, wait for it and reset on success
                  if (result && typeof result.then === 'function') {
                    result
                      .then(() => {
                        // Reset scanner state after successful addition
                        console.log('✅ Item added successfully, resetting scanner...');
                        setScanned(false);
                        setScannedData(null);
                        setScannedType(null);
                        setBarcodeInfo(null);
                      })
                      .catch((error: any) => {
                        console.error('❌ Error in onAddItem promise:', error);
                        // Don't reset on error - let user see the error
                      });
                  } else {
                    // If it's not a promise, reset immediately (for backward compatibility)
                    // Small delay to ensure the alert shows first
                    setTimeout(() => {
                      setScanned(false);
                      setScannedData(null);
                      setScannedType(null);
                      setBarcodeInfo(null);
                    }, 500);
                  }
                } catch (error: any) {
                  console.error('❌ Error calling onAddItem:', error);
                  Alert.alert('Error', `Failed to add item: ${error?.message || error}`);
                }
              }}
            >
              <Text style={styles.buttonText}>ADD ITEM</Text>
            </TouchableOpacity>
          </View>
        </ThemedView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  camera: {
    flex: 1,
  },
  overlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'transparent',
    justifyContent: 'center',
    alignItems: 'center',
  },
  scanArea: {
    width: 250,
    height: 250,
    position: 'relative',
  },
  corner: {
    position: 'absolute',
    width: 30,
    height: 30,
    borderColor: '#fff',
  },
  topLeft: {
    top: 0,
    left: 0,
    borderTopWidth: 3,
    borderLeftWidth: 3,
  },
  topRight: {
    top: 0,
    right: 0,
    borderTopWidth: 3,
    borderRightWidth: 3,
  },
  bottomLeft: {
    bottom: 0,
    left: 0,
    borderBottomWidth: 3,
    borderLeftWidth: 3,
  },
  bottomRight: {
    bottom: 0,
    right: 0,
    borderBottomWidth: 3,
    borderRightWidth: 3,
  },
  instruction: {
    marginTop: 30,
    fontSize: 16,
    color: '#fff',
    textAlign: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
    padding: 10,
    borderRadius: 5,
  },
  resultContainer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(0,0,0,0.9)',
    padding: 20,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '70%',
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 15,
  },
  resultLabel: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#fff',
    flex: 1,
  },
  refreshButton: {
    backgroundColor: '#4CAF50',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  refreshButtonText: {
    color: '#fff',
    fontSize: 16,
  },
  loadingText: {
    fontSize: 14,
    color: '#fff',
    textAlign: 'center',
    marginVertical: 20,
  },
  infoContainer: {
    marginBottom: 15,
    maxHeight: 300,
  },
  infoContentContainer: {
    paddingBottom: 10,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
    paddingBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.1)',
  },
  infoLabel: {
    fontSize: 14,
    color: '#ccc',
    flex: 1,
    fontWeight: '600',
  },
  infoValue: {
    fontSize: 14,
    color: '#fff',
    flex: 1,
    textAlign: 'right',
  },
  barcodeValue: {
    color: '#4CAF50',
    fontWeight: 'bold',
    fontFamily: 'monospace',
  },
  expiredText: {
    color: '#f44336',
    fontWeight: 'bold',
  },
  activeStatus: {
    color: '#4CAF50',
    fontWeight: 'bold',
  },
  inactiveStatus: {
    color: '#ff9800',
    fontWeight: 'bold',
  },
  descriptionText: {
    fontSize: 12,
    fontStyle: 'italic',
    flex: 1,
    flexWrap: 'wrap',
  },
  warningText: {
    fontSize: 12,
    color: '#ff9800',
    textAlign: 'center',
    marginTop: 10,
    fontStyle: 'italic',
  },
  errorBox: {
    backgroundColor: 'rgba(244, 67, 54, 0.2)',
    padding: 12,
    borderRadius: 8,
    marginBottom: 15,
    borderWidth: 1,
    borderColor: '#f44336',
  },
  retryButton: {
    backgroundColor: '#2196F3',
    padding: 10,
    borderRadius: 6,
    marginTop: 10,
    alignItems: 'center',
  },
  retryButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: 'bold',
  },
  errorText: {
    color: '#ffcdd2',
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  errorHint: {
    color: '#ffcdd2',
    fontSize: 12,
  },
  buttonContainer: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 10,
  },
  button: {
    flex: 1,
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
  },
  addButton: {
    backgroundColor: '#4CAF50',
  },
  cancelButton: {
    backgroundColor: '#f44336',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  loadingCenter: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  footer: {
    position: 'absolute',
    bottom: 15,
    width: '100%',
    alignItems: 'center',
  },
  footerText: {
    color: '#9e9e9e',
    fontSize: 14,
    letterSpacing: 0.5,
  },
  message: {
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 20,
    padding: 20,
  },
});
