import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
  ActivityIndicator,
  FlatList,
  TouchableOpacity,
  TextInput,
} from 'react-native';
import { CameraView } from 'expo-camera';
import { useCameraPermissions } from 'expo-camera';
import barcodeService from '../services/barcodeService';
import { API_BASE_URL } from '@/constants/api';
// Barcode conversion removed - using exact scanned values only

interface Product {
  id: number;
  product_name: string;
  barcode_value: string;
  quantity: number;
  price: number;
  [key: string]: any;
}

interface CartItem extends Product {
  scannedQuantity: number;
}

const BarcodeScanner: React.FC = () => {
  const [permission, requestPermission] = useCameraPermissions();
  const [scannedProducts, setScannedProducts] = useState<CartItem[]>([]);
  const [isScanning, setIsScanning] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [manualBarcode, setManualBarcode] = useState('');
  const [lastScannedBarcode, setLastScannedBarcode] = useState('');

  useEffect(() => {
    if (!permission?.granted) {
      requestPermission();
    }
  }, [permission, requestPermission]);

  const searchProductByBarcode = async (barcode: string, barcodeType?: string) => {
    if (!barcode.trim()) {
      Alert.alert('Error', 'Please enter a barcode');
      return;
    }

    setIsLoading(true);
    try {
      // Try exact match first
      let response = await fetch(
        `${API_BASE_URL}/api/products/search-by-barcode.php?barcode=${encodeURIComponent(barcode)}`
      );
      let data = await response.json();

      // No conversion - use barcode exactly as scanned
      // If not found, that's it - no fallback conversion

      if (data.success && data.product) {
        addProductToCart(data.product);
        barcodeService.emitBarcodeScan(barcode, data.product);
        setManualBarcode('');
      } else {
        // Show the exact barcode that was searched
        Alert.alert('Not Found', `Product with barcode "${barcode}" not found in inventory`);
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to fetch product. Check your connection.');
      console.error('Error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleBarcodeScanned = ({ data, type }: { data: string; type: string }) => {
    if (!isScanning || data === lastScannedBarcode) return;

    console.log('🔍 Barcode scanned:', { data, type, raw: JSON.stringify(data) });
    
    // Use barcode exactly as scanned - NO conversion
    // "977000779004" stays "977000779004"
    // "LGS000779004" stays "LGS000779004"
    setLastScannedBarcode(data);
    setIsScanning(false);

    // Search with the exact barcode value as scanned
    searchProductByBarcode(data, type);

    setTimeout(() => setIsScanning(true), 1000);
  };

  const addProductToCart = (product: Product) => {
    setScannedProducts((prev) => {
      const existingItem = prev.find((item) => item.id === product.id);

      if (existingItem) {
        return prev.map((item) =>
          item.id === product.id
            ? { ...item, scannedQuantity: item.scannedQuantity + 1 }
            : item
        );
      }

      return [...prev, { ...product, scannedQuantity: 1 }];
    });

    Alert.alert('Success', `${product.product_name} added to cart`);
  };

  const removeFromCart = (productId: number) => {
    setScannedProducts((prev) => prev.filter((item) => item.id !== productId));
  };

  const updateQuantity = (productId: number, quantity: number) => {
    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }

    setScannedProducts((prev) =>
      prev.map((item) =>
        item.id === productId ? { ...item, scannedQuantity: quantity } : item
      )
    );
  };

  const calculateTotal = () => {
    return scannedProducts.reduce((sum, item) => sum + item.price * item.scannedQuantity, 0);
  };

  if (!permission?.granted) {
    return (
      <View style={styles.container}>
        <Text style={styles.permissionText}>Camera permission is required</Text>
        <TouchableOpacity style={styles.button} onPress={requestPermission}>
          <Text style={styles.buttonText}>Grant Permission</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.cameraContainer}>
        <CameraView
          style={styles.camera}
          onBarcodeScanned={handleBarcodeScanned}
          barcodeScannerSettings={{
            barcodeTypes: [
              'code128',      // Supports alphanumeric (A-Z, 0-9, and special chars) - PRIMARY
              'code39',       // Supports alphanumeric (A-Z, 0-9, and some special chars) - PRIMARY
              'code93',       // Supports alphanumeric - PRIMARY
              'codabar',      // Supports alphanumeric
              'qr',           // Supports alphanumeric and special characters
              // Numeric-only formats removed to prioritize alphanumeric reading
              // Barcodes are used exactly as scanned - no conversion
            ],
          }}
        />
        <View style={styles.scannerOverlay}>
          <Text style={styles.scannerText}>Align barcode with frame</Text>
        </View>
      </View>

      <View style={styles.inputSection}>
        <TextInput
          style={styles.barcodeInput}
          placeholder="Or enter barcode manually..."
          value={manualBarcode}
          onChangeText={setManualBarcode}
          onSubmitEditing={() => searchProductByBarcode(manualBarcode)}
          editable={!isLoading}
        />
        <TouchableOpacity
          style={[styles.searchButton, isLoading && styles.disabledButton]}
          onPress={() => searchProductByBarcode(manualBarcode)}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.buttonText}>Search</Text>
          )}
        </TouchableOpacity>
      </View>

      <View style={styles.cartSection}>
        <Text style={styles.cartTitle}>
          Cart ({scannedProducts.length} items)
        </Text>
        <FlatList
          data={scannedProducts}
          keyExtractor={(item) => `${item.id}`}
          scrollEnabled={false}
          renderItem={({ item }) => (
            <View style={styles.cartItem}>
              <View style={styles.itemInfo}>
                <Text style={styles.itemName}>{item.product_name}</Text>
                <Text style={styles.itemBarcode}>{item.barcode_value}</Text>
                <Text style={styles.itemPrice}>
                  ₱{(item.price * item.scannedQuantity).toFixed(2)}
                </Text>
              </View>

              <View style={styles.quantityControl}>
                <TouchableOpacity
                  style={styles.quantityButton}
                  onPress={() => updateQuantity(item.id, item.scannedQuantity - 1)}
                >
                  <Text style={styles.quantityText}>-</Text>
                </TouchableOpacity>

                <Text style={styles.quantity}>{item.scannedQuantity}</Text>

                <TouchableOpacity
                  style={styles.quantityButton}
                  onPress={() => updateQuantity(item.id, item.scannedQuantity + 1)}
                >
                  <Text style={styles.quantityText}>+</Text>
                </TouchableOpacity>
              </View>

              <TouchableOpacity
                style={styles.deleteButton}
                onPress={() => removeFromCart(item.id)}
              >
                <Text style={styles.deleteText}>✕</Text>
              </TouchableOpacity>
            </View>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No items scanned yet</Text>
          }
        />

        {scannedProducts.length > 0 && (
          <View style={styles.totalSection}>
            <Text style={styles.totalLabel}>Total:</Text>
            <Text style={styles.totalAmount}>
              ₱{calculateTotal().toFixed(2)}
            </Text>
          </View>
        )}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  permissionText: {
    fontSize: 18,
    textAlign: 'center',
    marginBottom: 20,
    color: '#333',
  },
  cameraContainer: {
    height: '40%',
    backgroundColor: '#000',
    position: 'relative',
    overflow: 'hidden',
  },
  camera: {
    flex: 1,
  },
  scannerOverlay: {
    position: 'absolute',
    bottom: 20,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(0,0,0,0.5)',
    paddingVertical: 10,
  },
  scannerText: {
    color: '#fff',
    textAlign: 'center',
    fontSize: 14,
  },
  inputSection: {
    flexDirection: 'row',
    padding: 15,
    backgroundColor: '#fff',
    gap: 10,
  },
  barcodeInput: {
    flex: 1,
    borderColor: '#ddd',
    borderWidth: 1,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
  },
  searchButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
    minWidth: 80,
  },
  disabledButton: {
    opacity: 0.5,
  },
  cartSection: {
    flex: 1,
    backgroundColor: '#fff',
    padding: 15,
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  cartTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 15,
    color: '#333',
  },
  cartItem: {
    flexDirection: 'row',
    backgroundColor: '#f9f9f9',
    padding: 12,
    marginBottom: 10,
    borderRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: '#007AFF',
    alignItems: 'center',
  },
  itemInfo: {
    flex: 1,
  },
  itemName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    marginBottom: 4,
  },
  itemBarcode: {
    fontSize: 12,
    color: '#666',
    marginBottom: 4,
  },
  itemPrice: {
    fontSize: 14,
    fontWeight: '700',
    color: '#007AFF',
  },
  quantityControl: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginRight: 10,
  },
  quantityButton: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#007AFF',
    justifyContent: 'center',
    alignItems: 'center',
  },
  quantityText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  quantity: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    minWidth: 30,
    textAlign: 'center',
  },
  deleteButton: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#ff3b30',
    justifyContent: 'center',
    alignItems: 'center',
  },
  deleteText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  emptyText: {
    textAlign: 'center',
    color: '#999',
    marginTop: 20,
    fontSize: 14,
  },
  totalSection: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingTop: 15,
    borderTopWidth: 1,
    borderTopColor: '#eee',
    marginTop: 15,
  },
  totalLabel: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
  },
  totalAmount: {
    fontSize: 18,
    fontWeight: '700',
    color: '#007AFF',
  },
  button: {
    backgroundColor: '#007AFF',
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 8,
    alignSelf: 'center',
  },
  buttonText: {
    color: '#fff',
    fontWeight: '600',
    fontSize: 14,
  },
});

export default BarcodeScanner;
