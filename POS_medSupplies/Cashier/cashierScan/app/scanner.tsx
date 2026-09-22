import React, { useState, useEffect, useRef } from 'react';
import {
  StyleSheet,
  View,
  Text,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  Platform,
  TextInput,
  Modal,
  ScrollView,
} from 'react-native';
import { Image } from 'expo-image';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useRouter } from 'expo-router';
import * as Haptics from 'expo-haptics';
import { API_ENDPOINTS } from '@/constants/api';
import { convertNumericToAlphanumeric } from '@/constants/barcodePrefixes';

type Mode = 'barcode' | 'id-capture';

export default function ScannerScreen() {
  const router = useRouter();
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [mode, setMode] = useState<Mode>('barcode');
  const [discountType, setDiscountType] = useState<'senior_citizen' | 'pwd' | null>(null);
  const [showManualEntry, setShowManualEntry] = useState(false);
  type ManualData = {
    customerName: string;
    idNumber: string;
    issuingLgu: string;
  };

  const [manualData, setManualData] = useState<ManualData>({
    customerName: '',
    idNumber: '',
    issuingLgu: '',
  });
  const [capturedIdImage, setCapturedIdImage] = useState<string | null>(null);
  const cameraRef = useRef<any>(null);
  const [lastScannedItem, setLastScannedItem] = useState<{
    name: string;
    barcode: string;
  } | null>(null);
  const [scanCount, setScanCount] = useState(0);
  const [fetchedIdData, setFetchedIdData] = useState<any>(null);
  const [isFetchingId, setIsFetchingId] = useState(false);
  const [showFetchedId, setShowFetchedId] = useState(false);

  useEffect(() => {
    // Request camera permission on mount
    if (permission && !permission.granted) {
      requestPermission();
    }
  }, [permission]);

  const handleBarCodeScanned = async ({ data, type }: { data: string; type: string }) => {
    if (scanned || isSaving) return; // Prevent multiple scans
    
    setScanned(true);
    
    // Trim whitespace and preserve the exact scanned value (supports alphanumeric: letters + numbers)
    const barcodeValue = data.trim();
    
    // Log barcode details including alphanumeric detection
    const isAlphanumeric = /[A-Za-z]/.test(barcodeValue);
    const isNumericOnly = /^[0-9]+$/.test(barcodeValue);
    const isNumericFormat = ['ean13', 'ean8', 'upc_a', 'upc_e', 'itf14'].includes(type?.toLowerCase() || '');
    
    console.log('Barcode scanned:', { 
      data: barcodeValue, 
      type, 
      raw: JSON.stringify(barcodeValue),
      isAlphanumeric: isAlphanumeric, // Check if contains letters
      hasNumbers: /\d/.test(barcodeValue), // Check if contains numbers
      length: barcodeValue.length,
      format: isAlphanumeric ? 'Alphanumeric (letters + numbers)' : 'Numeric only',
      detectedFormat: type
    });
    
    // Warn if numeric-only barcode is detected as numeric format (might be misread alphanumeric)
    if (isNumericOnly && isNumericFormat) {
      console.warn(`⚠️ Numeric-only barcode detected as ${type}. If this should be alphanumeric (like "LGS000779004"), the system will try conversion if not found.`);
    }
    
    // Use barcode exactly as scanned - supports both numeric and alphanumeric (mixed letters/numbers)
    // Examples:
    // - "977000779004" (numeric) stays "977000779004"
    // - "LGS000779004" (alphanumeric) stays "LGS000779004"
    // - "ABC123XYZ" (alphanumeric) stays "ABC123XYZ"
    // - "123ABC456" (mixed letters/numbers) stays "123ABC456"
    // - "PROD-2024-001" (alphanumeric with special chars) stays "PROD-2024-001"
    
    // Update last scanned item with exact scanned value
    setLastScannedItem({
      name: 'Scanning...',
      barcode: barcodeValue, // Use exact scanned value (supports letters and numbers)
    });

    // Haptic feedback
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);

    // IMPORTANT: Only convert NUMERIC-ONLY barcodes to alphanumeric
    // If scanner reads "LGS000779004" (alphanumeric), use it AS-IS - NO CONVERSION
    // Only convert if scanner reads "977000779004" (numeric-only) -> "LGS000779004"
    let finalBarcodeValue = barcodeValue;
    
    // Only attempt conversion if barcode is purely numeric (contains NO letters)
    if (isNumericOnly) {
      console.log(`🔍 Numeric barcode detected: "${barcodeValue}" - checking for conversion...`);
      const alphanumericVariants = convertNumericToAlphanumeric(barcodeValue);
      if (alphanumericVariants.length > 0) {
        // Use the first (most likely) alphanumeric variant
        finalBarcodeValue = alphanumericVariants[0];
        console.log(`✅ Converted numeric "${barcodeValue}" → alphanumeric "${finalBarcodeValue}"`);
        // Update display to show the converted barcode
        setLastScannedItem({
          name: 'Scanning...',
          barcode: finalBarcodeValue, // Show the alphanumeric version
        });
      } else {
        console.log(`⚠️ No conversion mapping found for "${barcodeValue}" - using as-is`);
      }
    } else {
      // Barcode contains letters (alphanumeric) - use exactly as scanned, NO CONVERSION
      console.log(`✅ Alphanumeric barcode "${barcodeValue}" - using as-is (no conversion)`);
    }

    // Send the barcode value to server
    // - If alphanumeric like "LGS000779004": sends "LGS000779004" (no change)
    // - If numeric like "977000779004": sends "LGS000779004" (converted)
    console.log(`📤 Sending to server: "${finalBarcodeValue}"`);
    await sendBarcodeToServer(finalBarcodeValue, type);

    // Reset scan state after 2 seconds to allow next scan
    setTimeout(() => {
      setScanned(false);
    }, 2000);
  };

  const captureIdImage = async () => {
    if (!cameraRef.current || !discountType) {
      Alert.alert('Error', 'Please select discount type first');
      return;
    }

    try {
      setIsSaving(true);
      Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);

      // Take picture using CameraView with lower quality to reduce size
      const photo = await cameraRef.current.takePictureAsync({
        quality: 0.3, // Further reduced to minimize file size and prevent MySQL errors
        base64: true,
        skipProcessing: false, // Allow some processing to optimize
      });

      if (photo?.base64) {
        const imageDataUri = `data:image/jpeg;base64,${photo.base64}`;
        const imageSizeKB = Math.round(photo.base64.length * 3 / 4 / 1024);
        const imageSizeMB = (imageSizeKB / 1024).toFixed(2);
        
        console.log('Image captured:', {
          sizeKB: imageSizeKB,
          sizeMB: imageSizeMB,
          base64Length: photo.base64.length,
        });
        
        setCapturedIdImage(imageDataUri);
        
        // Show confirmation dialog with image size info
        Alert.alert(
          'ID Captured',
          `ID image captured successfully (${imageSizeMB} MB).\n\nWould you like to send it to the cashier dashboard?`,
          [
            { text: 'Retake', style: 'cancel', onPress: () => setCapturedIdImage(null) },
            { 
              text: 'Send', 
              onPress: () => sendIdToServer(imageDataUri)
            },
          ]
        );
      }
    } catch (error: any) {
      console.error('Error capturing ID:', error);
      Alert.alert('Error', `Failed to capture ID: ${error.message}`);
    } finally {
      setIsSaving(false);
    }
  };

  const sendIdToServer = async (idImage: string, manualData?: ManualData) => {
    if (!discountType) {
      Alert.alert('Error', 'Please select discount type');
      return;
    }

    setIsSaving(true);

    try {
      // Calculate image size for logging
      const base64Data = idImage.includes(',') ? idImage.split(',')[1] : idImage;
      const imageSizeKB = Math.round(base64Data.length * 3 / 4 / 1024);
      const imageSizeMB = (imageSizeKB / 1024).toFixed(2);
      
      console.log('Sending ID to server:', API_ENDPOINTS.idCapture);
      console.log('Image size:', `${imageSizeMB} MB (${imageSizeKB} KB)`);

      const requestBody: any = {
        id_image: idImage,
        discount_type: discountType,
      };

      // Add manual data if provided
      if (manualData && manualData.customerName && manualData.idNumber && manualData.issuingLgu) {
        requestBody.customer_name = manualData.customerName;
        requestBody.id_number = manualData.idNumber;
        requestBody.issuing_lgu = manualData.issuingLgu;
      }

      // Create AbortController for timeout (5 minutes for large images)
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 300000); // 5 minutes

      console.log('Starting upload...');
      const response = await fetch(API_ENDPOINTS.idCapture, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(requestBody),
        signal: controller.signal,
      });

      clearTimeout(timeoutId);
      console.log('Upload completed, status:', response.status);

      const responseText = await response.text();
      console.log('Response status:', response.status);
      console.log('Response text:', responseText);

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Failed to parse JSON response:', parseError);
        throw new Error('Invalid server response');
      }

      if (response.ok && data.success) {
        try {
          await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        } catch (error) {
          console.log('Haptics not available');
        }

        Alert.alert(
          'Success',
          `${discountType === 'senior_citizen' ? 'Senior Citizen' : 'PWD'} ID captured and sent to cashier dashboard successfully!`,
          [
            {
              text: 'OK',
              onPress: () => {
                // Reset state
                setCapturedIdImage(null);
                setManualData({ customerName: '', idNumber: '', issuingLgu: '' });
                setShowManualEntry(false);
                setDiscountType(null);
                setMode('barcode');
              },
            },
          ]
        );
      } else {
        throw new Error(data.message || 'Failed to send ID');
      }
    } catch (error: any) {
      console.error('Error sending ID:', error);
      try {
        await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      } catch (hapticError) {
        console.log('Haptics not available');
      }

      let errorMessage = '';
      
      if (error.name === 'AbortError' || error.message.includes('timeout') || error.message.includes('Aborted')) {
        errorMessage = `Request timed out. The image may be too large or network is slow.\n\nPlease try:\n1. Retake the photo (the app will automatically compress it)\n2. Make sure you have a stable internet connection\n3. Try again - the image will be compressed automatically\n4. If problem persists, use Manual Entry instead`;
      } else if (error.message.includes('Network request failed') || error.message.includes('Failed to fetch')) {
        errorMessage = `Network connection failed.\n\n⚠️ Server may not be accessible from your device!\n\nPlease check:\n1. Server is running with: php artisan serve --host=0.0.0.0 --port=8000\n   (NOT --host=127.0.0.1)\n2. Device and computer are on the same WiFi network\n3. Windows Firewall allows port 8000\n4. Server URL: ${API_ENDPOINTS.idCapture}\n\nTo restart server correctly:\n1. Stop current server (Ctrl+C)\n2. Run: cd C:\\xampp\\htdocs\\POS_medSupplies\n3. Run: php artisan serve --host=0.0.0.0 --port=8000`;
      } else {
        errorMessage = `Failed to send ID to server.\n\nError: ${error.message}\n\nPlease check:\n1. Server is running\n2. API URL is correct\n3. Device and server are on same network\n4. Firewall allows connections`;
      }

      Alert.alert('Connection Error', errorMessage, [{ text: 'OK' }]);
    } finally {
      setIsSaving(false);
    }
  };

  const fetchLatestId = async () => {
    setIsFetchingId(true);
    try {
      console.log('Fetching latest ID from server:', API_ENDPOINTS.idLatest);
      
      const response = await fetch(API_ENDPOINTS.idLatest, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Cache-Control': 'no-cache',
        },
      });

      const responseText = await response.text();
      console.log('Response status:', response.status);
      console.log('Response text:', responseText.substring(0, 200));

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Failed to parse JSON response:', parseError);
        throw new Error('Invalid server response');
      }

      if (response.ok && data.success && data.data) {
        const idData = data.data;
        
        // Format image data as base64 data URI
        let imageData = idData.id_image;
        if (imageData && !imageData.startsWith('data:')) {
          imageData = `data:image/jpeg;base64,${imageData}`;
        }
        
        setFetchedIdData({
          ...idData,
          id_image: imageData,
        });
        setShowFetchedId(true);
        
        try {
          await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        } catch (error) {
          console.log('Haptics not available');
        }
        
        Alert.alert(
          'ID Retrieved',
          `Found ${idData.discount_type === 'senior_citizen' ? 'Senior Citizen' : 'PWD'} ID from cashier dashboard.`,
          [{ text: 'OK' }]
        );
      } else {
        Alert.alert(
          'No ID Found',
          'No ID has been captured yet in the cashier dashboard.',
          [{ text: 'OK' }]
        );
      }
    } catch (error: any) {
      console.error('Error fetching ID:', error);
      try {
        await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      } catch (hapticError) {
        console.log('Haptics not available');
      }
      
      Alert.alert(
        'Error',
        `Failed to fetch ID: ${error.message}\n\nMake sure the server is running and accessible.`,
        [{ text: 'OK' }]
      );
    } finally {
      setIsFetchingId(false);
    }
  };

  const sendBarcodeToServer = async (barcodeValue: string, barcodeType: string) => {
    setIsSaving(true);
    
    try {
      console.log('📤 Sending barcode to server:', {
        endpoint: API_ENDPOINTS.cashierScan,
        barcode_value: barcodeValue,
        barcode_type: barcodeType,
        isAlphanumeric: /[A-Za-z]/.test(barcodeValue),
      });
      
      const response = await fetch(API_ENDPOINTS.cashierScan, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          barcode_value: barcodeValue,
          barcode_type: barcodeType,
        }),
      });

      const responseText = await response.text();
      console.log('Response status:', response.status);
      console.log('Response text:', responseText);

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Failed to parse JSON response:', parseError);
        throw new Error('Invalid server response');
      }

      if (response.ok && data.success && data.found) {
        // Success - item found
        setLastScannedItem({
          name: data.data.item_name || 'Unknown Item',
          barcode: barcodeValue,
        });
        setScanCount(prev => prev + 1);
        
        // Success haptic feedback
        try {
          await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        } catch (error) {
          console.log('Haptics not available');
        }
        
        Alert.alert(
          'Item Scanned',
          `${data.data.item_name}\nBarcode: ${barcodeValue}`,
          [{ text: 'OK' }]
        );
      } else {
        // Item not found
        try {
          await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning);
        } catch (error) {
          console.log('Haptics not available');
        }
        Alert.alert(
          'Item Not Found',
          `Barcode ${barcodeValue} not found in inventory.`,
          [{ text: 'OK' }]
        );
      }
    } catch (error: any) {
      console.error('Error sending barcode:', error);
      try {
        await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      } catch (hapticError) {
        console.log('Haptics not available');
      }
      
      const errorMessage = Platform.OS === 'android' && API_ENDPOINTS.cashierScan.includes('localhost')
        ? `Failed to send barcode to server.\n\n${error.message}\n\n⚠️ IMPORTANT: 'localhost' doesn't work on Android emulator!\n\nPlease:\n1. Open constants/api.ts\n2. Change API_BASE_URL to:\n   - Android Emulator: 'http://10.0.2.2'\n   - Physical Device: 'http://YOUR_IP_ADDRESS'\n   - iOS Simulator: 'http://localhost'\n3. Make sure server is running\n4. Device and server are on same network`
        : `Failed to send barcode to server.\n\n${error.message}\n\nPlease check:\n1. Server is running\n2. API URL is correct (check constants/api.ts)\n3. Device and server are on same network\n4. Firewall allows connections`;
      
      Alert.alert('Connection Error', errorMessage, [{ text: 'OK' }]);
    } finally {
      setIsSaving(false);
    }
  };

  if (!permission) {
    return (
      <View style={styles.container}>
        <View style={styles.permissionContainer}>
          <ActivityIndicator size="large" />
          <Text style={styles.message}>Requesting camera permission...</Text>
        </View>
      </View>
    );
  }

  if (!permission.granted) {
    return (
      <View style={styles.container}>
        <View style={styles.permissionContainer}>
          <Text style={styles.permissionTitle}>Camera Permission Required</Text>
          <Text style={styles.permissionMessage}>
            We need access to your camera to scan barcodes.
          </Text>
          <TouchableOpacity style={styles.button} onPress={requestPermission}>
            <Text style={styles.buttonText}>Grant Permission</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <CameraView
        ref={cameraRef}
        style={styles.camera}
        facing="back"
        onBarcodeScanned={mode === 'barcode' && !scanned ? handleBarCodeScanned : undefined}
        barcodeScannerSettings={{
          barcodeTypes: [
            // PRIORITIZE alphanumeric formats first
            'code128',      // PRIMARY - Best for alphanumeric (A-Z, 0-9, special chars) - reads "LGS000779004" correctly
            'code39',       // PRIMARY - Good for alphanumeric (A-Z, 0-9) - reads "LGS000779004" correctly
            'code93',       // PRIMARY - Supports alphanumeric - reads "LGS000779004" correctly
            'codabar',      // Supports alphanumeric
            'qr',           // Supports alphanumeric and special characters
            'datamatrix',   // Supports alphanumeric and special characters
            'aztec',        // Supports alphanumeric and special characters
            'pdf417',       // Supports alphanumeric and special characters
            // Include numeric formats as fallback (will auto-convert to alphanumeric if prefix matches)
            // Example: EAN13 "977000779004" will auto-convert to Code128 "LGS000779004"
            'ean13',        // Numeric-only - will auto-convert if prefix matches (e.g., 977 -> LGS)
            'ean8',         // Numeric-only - will auto-convert if prefix matches
            'upc_a',        // Numeric-only - will auto-convert if prefix matches
            'upc_e',        // Numeric-only - will auto-convert if prefix matches
            'itf14',        // Numeric-only - will auto-convert if prefix matches
          ],
        }}
      />
      {/* Overlay with absolute positioning */}
      <View style={styles.overlay} pointerEvents="box-none">
        {/* Top bar */}
        <View style={styles.topBar}>
          <TouchableOpacity
            style={styles.backButton}
            onPress={() => router.back()}
          >
            <Text style={styles.backButtonText}>← Back</Text>
          </TouchableOpacity>
          <View style={styles.titleContainer}>
            <Image
              source={require('@/assets/images/android-icon-background.png')}
              style={styles.logo}
              contentFit="contain"
            />
            <Text style={styles.titleText}>
              {mode === 'barcode' ? 'Barcode Scanner' : 'ID Capture'}
            </Text>
          </View>
          <TouchableOpacity
            style={styles.modeButton}
            onPress={() => {
              setMode(mode === 'barcode' ? 'id-capture' : 'barcode');
              setDiscountType(null);
              setCapturedIdImage(null);
              setShowManualEntry(false);
            }}
          >
            <Text style={styles.modeButtonText}>
              {mode === 'barcode' ? 'ID' : 'Barcode'}
            </Text>
          </TouchableOpacity>
        </View>

        {mode === 'barcode' ? (
          <>
            {/* Scanning area overlay */}
            <View style={styles.scanAreaContainer} pointerEvents="none">
              <View style={styles.scanArea} />
              <Text style={styles.scanAreaLabel}>Align barcode within this area</Text>
            </View>

            {/* Bottom section */}
            <View style={styles.bottomSection}>
              {isSaving && (
                <View style={styles.savingContainer}>
                  <ActivityIndicator size="small" color="#fff" />
                  <Text style={styles.savingText}>Sending to server...</Text>
                </View>
              )}

              {lastScannedItem && !isSaving && (
                <View style={styles.lastScannedContainer}>
                  <Text style={styles.lastScannedLabel}>Last Scanned:</Text>
                  <Text style={styles.lastScannedName}>{lastScannedItem.name}</Text>
                  <Text style={styles.lastScannedBarcode}>{lastScannedItem.barcode}</Text>
                </View>
              )}

              <View style={styles.statsContainer}>
                <Text style={styles.statsText}>Scanned: {scanCount} items</Text>
              </View>

              <Text style={styles.instruction}>
                {scanned ? 'Processing...' : 'Point camera at barcode'}
              </Text>
            </View>
          </>
        ) : (
          <>
            {/* ID Capture Mode */}
            <View style={styles.idCaptureContainer} pointerEvents="box-none">
              <View style={styles.idScanArea} />
              <Text style={styles.idScanAreaLabel}>
                Align ID card within this area
              </Text>
            </View>

            <View style={styles.idBottomSection}>
              {!discountType ? (
                <View style={styles.discountTypeSelection}>
                  <Text style={styles.discountTypeTitle}>Select Discount Type</Text>
                  <TouchableOpacity
                    style={[styles.discountTypeButton, styles.seniorButton]}
                    onPress={() => setDiscountType('senior_citizen')}
                  >
                    <Text style={styles.discountTypeButtonText}>Senior Citizen</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[styles.discountTypeButton, styles.pwdButton]}
                    onPress={() => setDiscountType('pwd')}
                  >
                    <Text style={styles.discountTypeButtonText}>PWD</Text>
                  </TouchableOpacity>
                </View>
              ) : (
                <>
                  <View style={styles.selectedDiscountType}>
                    <Text style={styles.selectedDiscountText}>
                      {discountType === 'senior_citizen' ? 'Senior Citizen' : 'PWD'} ID
                    </Text>
                    <TouchableOpacity
                      style={styles.changeTypeButton}
                      onPress={() => {
                        setDiscountType(null);
                        setCapturedIdImage(null);
                        setShowManualEntry(false);
                      }}
                    >
                      <Text style={styles.changeTypeButtonText}>Change</Text>
                    </TouchableOpacity>
                  </View>

                  {capturedIdImage && (
                    <View style={styles.capturedImageContainer}>
                      <Text style={styles.capturedImageLabel}>Captured ID:</Text>
                      <Text style={styles.capturedImageText}>Image ready to send</Text>
                    </View>
                  )}

                  {isSaving && (
                    <View style={styles.savingContainer}>
                      <ActivityIndicator size="small" color="#fff" />
                      <Text style={styles.savingText}>
                        {capturedIdImage ? 'Sending to server...' : 'Capturing...'}
                      </Text>
                    </View>
                  )}

                  <View style={styles.idCaptureButtons}>
                    <TouchableOpacity
                      style={[styles.captureButton, isSaving && styles.buttonDisabled]}
                      onPress={captureIdImage}
                      disabled={isSaving}
                    >
                      <Text style={styles.captureButtonText}>
                        {capturedIdImage ? 'Retake Photo' : 'Capture ID'}
                      </Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[styles.manualButton, isSaving && styles.buttonDisabled]}
                      onPress={() => setShowManualEntry(true)}
                      disabled={isSaving}
                    >
                      <Text style={styles.manualButtonText}>Manual Entry</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[styles.fetchButton, (isSaving || isFetchingId) && styles.buttonDisabled]}
                      onPress={fetchLatestId}
                      disabled={isSaving || isFetchingId}
                    >
                      {isFetchingId ? (
                        <ActivityIndicator size="small" color="#fff" />
                      ) : (
                        <Text style={styles.fetchButtonText}>Fetch from Dashboard</Text>
                      )}
                    </TouchableOpacity>
                  </View>
                </>
              )}
            </View>
          </>
        )}
      </View>

      {/* Fetched ID Display Modal */}
      <Modal
        visible={showFetchedId}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowFetchedId(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {fetchedIdData?.discount_type === 'senior_citizen' ? 'Senior Citizen' : 'PWD'} ID from Dashboard
              </Text>
              <TouchableOpacity
                onPress={() => {
                  setShowFetchedId(false);
                  setFetchedIdData(null);
                }}
                style={styles.closeButton}
              >
                <Text style={styles.closeButtonText}>✕</Text>
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalBody}>
              {fetchedIdData?.id_image && (
                <View style={styles.fetchedImageContainer}>
                  <Text style={styles.inputLabel}>ID Image:</Text>
                  <Image
                    source={{ uri: fetchedIdData.id_image }}
                    style={styles.fetchedImage}
                    resizeMode="contain"
                  />
                </View>
              )}

              {fetchedIdData?.customer_name && (
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Customer Name</Text>
                  <Text style={styles.fetchedDataText}>{fetchedIdData.customer_name}</Text>
                </View>
              )}

              {fetchedIdData?.id_number && (
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>ID Number</Text>
                  <Text style={styles.fetchedDataText}>{fetchedIdData.id_number}</Text>
                </View>
              )}

              {fetchedIdData?.issuing_lgu && (
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Issuing LGU</Text>
                  <Text style={styles.fetchedDataText}>{fetchedIdData.issuing_lgu}</Text>
                </View>
              )}

              {fetchedIdData?.id_type && (
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>ID Type</Text>
                  <Text style={styles.fetchedDataText}>{fetchedIdData.id_type}</Text>
                </View>
              )}

              {fetchedIdData?.captured_at && (
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Captured At</Text>
                  <Text style={styles.fetchedDataText}>{fetchedIdData.captured_at}</Text>
                </View>
              )}

              <TouchableOpacity
                style={styles.closeModalButton}
                onPress={() => {
                  setShowFetchedId(false);
                  setFetchedIdData(null);
                }}
              >
                <Text style={styles.closeModalButtonText}>Close</Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Manual Entry Modal */}
      <Modal
        visible={showManualEntry}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowManualEntry(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Manual ID Entry</Text>
              <TouchableOpacity
                onPress={() => setShowManualEntry(false)}
                style={styles.closeButton}
              >
                <Text style={styles.closeButtonText}>✕</Text>
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalBody}>
              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>Customer Name</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Enter customer name"
                  value={manualData.customerName}
                  onChangeText={(text) =>
                    setManualData({ ...manualData, customerName: text })
                  }
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>ID Number</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Enter ID number"
                  value={manualData.idNumber}
                  onChangeText={(text) =>
                    setManualData({ ...manualData, idNumber: text })
                  }
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>Issuing LGU</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Enter issuing LGU"
                  value={manualData.issuingLgu}
                  onChangeText={(text) =>
                    setManualData({ ...manualData, issuingLgu: text })
                  }
                />
              </View>

              <TouchableOpacity
                style={[
                  styles.sendButton,
                  (!manualData.customerName || !manualData.idNumber || !manualData.issuingLgu) &&
                    styles.buttonDisabled,
                ]}
                onPress={() => {
                  if (
                    manualData.customerName &&
                    manualData.idNumber &&
                    manualData.issuingLgu
                  ) {
                    // For manual entry, send a minimal placeholder image (1x1 transparent PNG)
                    // The backend requires an image, but we'll send manual data along with it
                    // This is a minimal base64 encoded 1x1 transparent PNG
                    const placeholderImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
                    sendIdToServer(placeholderImage, manualData);
                  } else {
                    Alert.alert('Error', 'Please fill in all fields');
                  }
                }}
                disabled={
                  !manualData.customerName || !manualData.idNumber || !manualData.issuingLgu
                }
              >
                <Text style={styles.sendButtonText}>Send to Dashboard</Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    position: 'relative',
  },
  camera: {
    flex: 1,
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'transparent',
  },
  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: Platform.OS === 'ios' ? 50 : 20,
    paddingHorizontal: 20,
    paddingBottom: 20,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
  },
  backButton: {
    padding: 8,
  },
  backButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  titleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    gap: 8,
  },
  logo: {
    width: 32,
    height: 32,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
  },
  titleText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#fff',
  },
  placeholder: {
    width: 60,
  },
  scanAreaContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scanArea: {
    width: 280,
    height: 180,
    borderWidth: 3,
    borderColor: '#0d6efd',
    borderRadius: 10,
    backgroundColor: 'transparent',
  },
  scanAreaLabel: {
    marginTop: 20,
    color: '#fff',
    fontSize: 16,
    fontWeight: '500',
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
  },
  bottomSection: {
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    padding: 20,
    paddingBottom: Platform.OS === 'ios' ? 40 : 20,
  },
  savingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  savingText: {
    color: '#fff',
    marginLeft: 10,
    fontSize: 16,
  },
  lastScannedContainer: {
    backgroundColor: 'rgba(13, 110, 253, 0.3)',
    borderRadius: 10,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#0d6efd',
  },
  lastScannedLabel: {
    color: '#fff',
    fontSize: 12,
    opacity: 0.8,
    marginBottom: 4,
  },
  lastScannedName: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  lastScannedBarcode: {
    color: '#fff',
    fontSize: 14,
    opacity: 0.9,
  },
  statsContainer: {
    alignItems: 'center',
    marginBottom: 16,
  },
  statsText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  instruction: {
    color: '#fff',
    fontSize: 14,
    textAlign: 'center',
    opacity: 0.9,
  },
  permissionContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
    backgroundColor: '#fff',
  },
  permissionTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 16,
    textAlign: 'center',
    color: '#000',
  },
  permissionMessage: {
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 32,
    color: '#666',
    paddingHorizontal: 20,
  },
  message: {
    marginTop: 20,
    fontSize: 16,
    textAlign: 'center',
    paddingHorizontal: 20,
    color: '#666',
  },
  button: {
    marginTop: 20,
    backgroundColor: '#0d6efd',
    paddingHorizontal: 32,
    paddingVertical: 12,
    borderRadius: 8,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  modeButton: {
    padding: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderRadius: 8,
    minWidth: 60,
    alignItems: 'center',
  },
  modeButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
  idCaptureContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  idScanArea: {
    width: 300,
    height: 200,
    borderWidth: 3,
    borderColor: '#28a745',
    borderRadius: 10,
    backgroundColor: 'transparent',
  },
  idScanAreaLabel: {
    marginTop: 20,
    color: '#fff',
    fontSize: 16,
    fontWeight: '500',
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
  },
  idBottomSection: {
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    padding: 20,
    paddingBottom: Platform.OS === 'ios' ? 40 : 20,
  },
  discountTypeSelection: {
    alignItems: 'center',
  },
  discountTypeTitle: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 20,
  },
  discountTypeButton: {
    width: '100%',
    padding: 16,
    borderRadius: 10,
    marginBottom: 12,
    alignItems: 'center',
  },
  seniorButton: {
    backgroundColor: '#0d6efd',
  },
  pwdButton: {
    backgroundColor: '#28a745',
  },
  discountTypeButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  selectedDiscountType: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: 'rgba(13, 110, 253, 0.3)',
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
  },
  selectedDiscountText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  changeTypeButton: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderRadius: 6,
  },
  changeTypeButtonText: {
    color: '#fff',
    fontSize: 14,
  },
  capturedImageContainer: {
    backgroundColor: 'rgba(40, 167, 69, 0.3)',
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
  },
  capturedImageLabel: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 4,
  },
  capturedImageText: {
    color: '#fff',
    fontSize: 12,
    opacity: 0.9,
  },
  idCaptureButtons: {
    gap: 12,
  },
  captureButton: {
    backgroundColor: '#28a745',
    padding: 16,
    borderRadius: 10,
    alignItems: 'center',
  },
  captureButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  manualButton: {
    backgroundColor: '#0d6efd',
    padding: 16,
    borderRadius: 10,
    alignItems: 'center',
  },
  manualButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  fetchButton: {
    backgroundColor: '#ffc107',
    padding: 16,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 8,
  },
  fetchButtonText: {
    color: '#000',
    fontSize: 16,
    fontWeight: '600',
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  fetchedImageContainer: {
    marginBottom: 20,
  },
  fetchedImage: {
    width: '100%',
    height: 200,
    borderRadius: 8,
    marginTop: 8,
    backgroundColor: '#f0f0f0',
  },
  fetchedDataText: {
    fontSize: 16,
    color: '#333',
    padding: 12,
    backgroundColor: '#f9f9f9',
    borderRadius: 8,
    marginTop: 4,
  },
  closeModalButton: {
    backgroundColor: '#6c757d',
    padding: 16,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 20,
  },
  closeModalButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  modalContainer: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '80%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#000',
  },
  closeButton: {
    padding: 8,
  },
  closeButtonText: {
    fontSize: 24,
    color: '#666',
  },
  modalBody: {
    padding: 20,
  },
  inputGroup: {
    marginBottom: 20,
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    marginBottom: 8,
  },
  input: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    backgroundColor: '#fff',
  },
  sendButton: {
    backgroundColor: '#28a745',
    padding: 16,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 10,
  },
  sendButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});

