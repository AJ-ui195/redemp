import { Image } from 'expo-image';
import { StyleSheet, TouchableOpacity } from 'react-native';

import ParallaxScrollView from '@/components/parallax-scroll-view';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { useRouter } from 'expo-router';
import { IconSymbol } from '@/components/ui/icon-symbol';

export default function HomeScreen() {
  const router = useRouter();

  return (
    <ParallaxScrollView
      headerBackgroundColor={{ light: '#A1CEDC', dark: '#1D3D47' }}
      headerImage={
        <Image
          source={require('@/assets/images/android-icon-background.png')}
          style={styles.reactLogo}
          contentFit="contain"
        />
      }>
      <ThemedView style={styles.titleContainer}>
        <ThemedText type="title">Cashier Scanner</ThemedText>
      </ThemedView>
      
      <ThemedView style={styles.scannerButtonContainer}>
        <TouchableOpacity
          style={styles.scannerButton}
          onPress={() => router.push('/scanner')}
        >
          <IconSymbol name="barcode.viewfinder" size={48} color="#fff" />
          <ThemedText style={styles.scannerButtonText}>Start Barcode Scanner</ThemedText>
          <ThemedText style={styles.scannerButtonSubtext}>
            Scan items to add to cashier cart
          </ThemedText>
        </TouchableOpacity>
      </ThemedView>
    </ParallaxScrollView>
  );
}

const styles = StyleSheet.create({
  titleContainer: {
    alignItems: 'center',
    marginBottom: 24,
  },
  reactLogo: {
    height: 178,
    width: 290,
    bottom: 0,
    left: 0,
    position: 'absolute',
  },
  scannerButtonContainer: {
    marginBottom: 24,
    alignItems: 'center',
  },
  scannerButton: {
    backgroundColor: '#0d6efd',
    borderRadius: 16,
    padding: 32,
    alignItems: 'center',
    width: '100%',
    maxWidth: 400,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.3,
    shadowRadius: 4.65,
    elevation: 8,
  },
  scannerButtonText: {
    color: '#fff',
    fontSize: 24,
    fontWeight: 'bold',
    marginTop: 16,
    marginBottom: 8,
  },
  scannerButtonSubtext: {
    color: '#fff',
    fontSize: 14,
    opacity: 0.9,
  },
});
