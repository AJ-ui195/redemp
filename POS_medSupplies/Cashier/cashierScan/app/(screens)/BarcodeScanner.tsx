import { useState, useEffect, useRef } from 'react';
import { View, Text, TextInput, StyleSheet, Alert, ScrollView, Pressable } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';

interface DiscountInfo {
  type: 'senior_citizen' | 'pwd' | null;
  id: string;
  discount_rate: number;
  discount_description: string;
}

export default function BarcodeScanner() {
  const [permission, requestPermission] = useCameraPermissions();
  const [scannedBarcode, setScannedBarcode] = useState('');
  const [discountInfo, setDiscountInfo] = useState<DiscountInfo>({
    type: null,
    id: '',
    discount_rate: 0,
    discount_description: ''  
  });
  const [isScanningDisabled, setIsScanningDisabled] = useState(false);
  const scanTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const cameraRef = useRef(null);

  useEffect(() => {
    if (!permission?.granted) {
      requestPermission();
    }
  }, [permission]);

  const handleBarcodeScanned = async (result: any) => {
    if (isScanningDisabled) return;

    const barcode = result.data;
    setScannedBarcode(barcode);
    setIsScanningDisabled(true);

    try {
      const response = await fetch('http://192.168.1.105/POS_medSupplies/Cashier/api/barcode-webhook.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ barcode }),
      });

      const data = await response.json();

      if (data.success) {
        processBarcodeResult(data.type, barcode, data.data);
      } else {
        Alert.alert('Error', data.message);
      }
    } catch (error) {
      Alert.alert('Connection Error', 'Failed to process barcode');
      console.error(error);
    }

    scanTimeoutRef.current = setTimeout(() => {
      setIsScanningDisabled(false);
    }, 1000);
  };

  const processBarcodeResult = (type: string, barcode: string, data: any) => {
    if (type === 'senior_citizen') {
      handleSeniorCitizenID(barcode);
    } else if (type === 'pwd') {
      handlePWDID(barcode);
    } else {
      handleProductBarcode(barcode);
    }
  };

  const handleSeniorCitizenID = (id: string) => {
    setDiscountInfo({
      type: 'senior_citizen',
      id,
      discount_rate: 0.20, // 20% discount for senior citizens
      discount_description: 'Senior Citizen Discount (20%)',
    });
    Alert.alert('Success', `Senior Citizen ID scanned: ${id}\n20% discount applied!`);
  };

  const handlePWDID = (id: string) => {
    setDiscountInfo({
      type: 'pwd',
      id,
      discount_rate: 0.15, // 15% discount for PWD
      discount_description: 'PWD Discount (15%)',
    });
    Alert.alert('Success', `PWD ID scanned: ${id}\n15% discount applied!`);
  };

  const handleProductBarcode = (barcode: string) => {
    // Handle product barcode logic
    console.log('Product barcode scanned:', barcode);
  };

  const clearDiscount = () => {
    setDiscountInfo({
      type: null,
      id: '',
      discount_rate: 0,
      discount_description: '',
    });
    setScannedBarcode('');
  };

  if (!permission?.granted) {
    return (
      <View style={styles.container}>
        <Text>Camera permission is required</Text>
        <Pressable style={styles.button} onPress={requestPermission}>
          <Text style={styles.buttonText}>Grant Permission</Text>
        </Pressable>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      <View style={styles.cameraContainer}>
        <CameraView
          ref={cameraRef}
          style={styles.camera}
          onBarcodeScanned={!isScanningDisabled ? handleBarcodeScanned : undefined}
        />
      </View>

      <View style={styles.infoSection}>
        <Text style={styles.label}>Last Scanned Barcode:</Text>
        <TextInput
          style={styles.barcodeInput}
          value={scannedBarcode}
          editable={false}
          placeholder="Scan a barcode or ID..."
        />
      </View>

      {discountInfo.type && (
        <View style={[
          styles.discountBox,
          discountInfo.type === 'senior_citizen' ? styles.seniorCitizenBox : styles.pwdBox
        ]}>
          <Text style={styles.discountTitle}>{discountInfo.discount_description}</Text>
          <Text style={styles.discountID}>ID: {discountInfo.id}</Text>
          <Text style={styles.discountRate}>
            Discount Rate: {(discountInfo.discount_rate * 100).toFixed(0)}%
          </Text>
          <Pressable style={styles.clearButton} onPress={clearDiscount}>
            <Text style={styles.clearButtonText}>Clear Discount</Text>
          </Pressable>
        </View>
      )}

      <View style={styles.helpSection}>
        <Text style={styles.helpText}>Scan Instructions:</Text>
        <Text style={styles.helpItem}>• Senior Citizen ID: SC-XXXXXX</Text>
        <Text style={styles.helpItem}>• PWD ID: PWD-XXXXXX</Text>
        <Text style={styles.helpItem}>• Product Barcode: Standard UPC/EAN</Text>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  cameraContainer: {
    height: 300,
    overflow: 'hidden',
    borderRadius: 8,
    margin: 16,
  },
  camera: {
    flex: 1,
  },
  infoSection: {
    paddingHorizontal: 16,
    marginVertical: 12,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    color: '#333',
  },
  barcodeInput: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
    backgroundColor: '#fff',
    fontSize: 16,
  },
  discountBox: {
    marginHorizontal: 16,
    marginVertical: 12,
    padding: 16,
    borderRadius: 8,
    borderWidth: 2,
  },
  seniorCitizenBox: {
    backgroundColor: '#e8f5e9',
    borderColor: '#4caf50',
  },
  pwdBox: {
    backgroundColor: '#e3f2fd',
    borderColor: '#2196f3',
  },
  discountTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 8,
    color: '#333',
  },
  discountID: {
    fontSize: 14,
    marginBottom: 8,
    color: '#555',
  },
  discountRate: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 12,
    color: '#2e7d32',
  },
  clearButton: {
    backgroundColor: '#ff6b6b',
    borderRadius: 6,
    paddingVertical: 10,
    paddingHorizontal: 16,
  },
  clearButtonText: {
    fontSize: 14,
    color: '#fff',
    fontWeight: '600',
    textAlign: 'center',
  },
  helpSection: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#fff3e0',
    margin: 16,
    borderRadius: 8,
  },
  helpText: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    color: '#333',
  },
  helpItem: {
    fontSize: 12,
    color: '#666',
    marginBottom: 4,
  },
  button: {
    backgroundColor: '#4CAF50',
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 6,
    marginTop: 16,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
    textAlign: 'center',
  },
});
