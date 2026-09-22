import { StyleSheet, View, Alert, ActivityIndicator } from 'react-native';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import BarcodeScanner from '@/components/barcode-scanner';
import { useEffect, useState } from 'react';
import { API_BASE_URL, API_ENDPOINTS } from '@/config/api';

// API base URL is now configured in config/api.ts
// Make sure Laravel server is running: php artisan serve --host=0.0.0.0 --port=8000

export default function HomeScreen() {
  const [isInitializing, setIsInitializing] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  // Ensure a visible loader right after the app opens (QR flow)
  useEffect(() => {
    const timer = setTimeout(() => setIsInitializing(false), 5000);
    return () => clearTimeout(timer);
  }, []);

  const handleScan = (data: string, type: string) => {
    console.log('Scanned barcode:', { data, type });
    // You can add your custom logic here
  };

  const handleAddItem = async (barcodeValue: string, barcodeType: string): Promise<void> => {
    console.log('handleAddItem called with:', { barcodeValue, barcodeType });
    setIsSaving(true);
    
    try {
      // First test connectivity
      console.log('Testing connectivity to:', API_ENDPOINTS.testScannedProducts);
      try {
        const testResponse = await fetch(API_ENDPOINTS.testScannedProducts);
        const testData = await testResponse.json();
        console.log('Connectivity test result:', testData);
      } catch (testError) {
        console.error('Connectivity test failed:', testError);
        Alert.alert('Connection Error', `Cannot reach server at ${API_BASE_URL}. Please check:\n1. Server is running\n2. IP address is correct\n3. Device and server are on same network`);
        setIsSaving(false);
        return;
      }
      
      console.log('Sending request to:', API_ENDPOINTS.scannedProducts);
      
      const requestBody = {
        barcode_value: barcodeValue,
        barcode_type: barcodeType,
      };
      console.log('Request body:', requestBody);
      
      const response = await fetch(API_ENDPOINTS.scannedProducts, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(requestBody),
      });

      console.log('Response status:', response.status);
      console.log('Response ok:', response.ok);
      
      // Get response text first to handle both JSON and non-JSON responses
      const responseText = await response.text();
      console.log('Response text:', responseText);
      
      let data;
      try {
        data = JSON.parse(responseText);
        console.log('Response data:', data);
      } catch (e) {
        console.error('Failed to parse JSON response:', e);
        Alert.alert(
          'Error', 
          `Server returned non-JSON response (Status: ${response.status}). Response: ${responseText.substring(0, 100)}`
        );
        return;
      }

      if (data.success) {
        Alert.alert(
          'Success',
          data.message || (data.added_to_inventory
            ? 'Product added to inventory successfully.'
            : 'Item added successfully.')
        );
        return;
      }

      const errorMessage = data.message || data.error || 'Failed to add item';
      console.error('API Error:', errorMessage);
      Alert.alert('Error', errorMessage);
    } catch (error: any) {
      console.error('Error saving scanned product:', error);
      const errorMessage = error?.message || 'Failed to connect to server. Please check your connection.';
      Alert.alert('Error', errorMessage);
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancel = () => {
    // Reset scanner or navigate back
    console.log('Scan cancelled');
  };

  // Show an initial loader so users see feedback before the camera mounts
  if (isInitializing) {
    return (
      <View style={styles.container}>
        <View style={styles.loadingOverlay}>
          <View style={styles.loadingCard}>
            <ActivityIndicator size="large" color="#4CAF50" />
            <ThemedText style={styles.loadingTitle}>Preparing scanner...</ThemedText>
            <ThemedText style={styles.loadingSubtitle}>Optimizing camera & connection</ThemedText>
          </View>
        </View>
        <View style={styles.footer}>
          <ThemedText style={styles.footerText}>Powered By: Techies</ThemedText>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ThemedView style={styles.header}>
        <ThemedText type="title" style={styles.title}>
          Barcode Scanner
        </ThemedText>
      </ThemedView>
      {isSaving && (
        <View style={styles.loadingOverlay} pointerEvents="none">
          <View style={styles.loadingCard}>
          <ActivityIndicator size="large" color="#4CAF50" />
            <ThemedText style={styles.loadingTitle}>Saving item...</ThemedText>
            <ThemedText style={styles.loadingSubtitle}>Please keep the app open</ThemedText>
          </View>
        </View>
      )}
      <BarcodeScanner 
        onScan={handleScan} 
        onAddItem={(barcodeValue, barcodeType) => {
          console.log('📱 HomeScreen received onAddItem call:', { barcodeValue, barcodeType });
          handleAddItem(barcodeValue, barcodeType);
        }}
        onCancel={handleCancel}
        apiBaseUrl={API_BASE_URL}
      />
      <View style={styles.footer}>
        <ThemedText style={styles.footerText}>Powered By: Techies</ThemedText>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    paddingTop: 50,
    paddingBottom: 20,
    paddingHorizontal: 20,
    alignItems: 'center',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
  },
  loadingOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1000,
  },
  loadingText: {
    marginTop: 10,
    color: '#fff',
    fontSize: 16,
  },
  loadingCard: {
    padding: 18,
    backgroundColor: 'rgba(20, 20, 20, 0.9)',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(76, 175, 80, 0.2)',
    width: 240,
    alignItems: 'center',
    gap: 8,
    shadowColor: '#000',
    shadowOpacity: 0.3,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 6 },
    elevation: 6,
  },
  loadingTitle: {
    marginTop: 6,
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
    textAlign: 'center',
  },
  loadingSubtitle: {
    color: '#bdbdbd',
    fontSize: 13,
    textAlign: 'center',
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
});
