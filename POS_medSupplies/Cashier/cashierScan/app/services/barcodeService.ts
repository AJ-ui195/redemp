type Callback = (data: any) => void;

class BarcodeService {
  private static instance: BarcodeService;
  private listeners: Map<string, Callback[]> = new Map();

  private constructor() {}

  static getInstance(): BarcodeService {
    if (!BarcodeService.instance) {
      BarcodeService.instance = new BarcodeService();
    }
    return BarcodeService.instance;
  }

  emitBarcodeScan(barcode: string, product: any) {
    this.emit('barcode-scanned', { barcode, product, timestamp: Date.now() });
  }

  onBarcodeScan(callback: Callback) {
    this.on('barcode-scanned', callback);
  }

  removeBarcodeScanListener(callback: Callback) {
    this.off('barcode-scanned', callback);
  }

  private on(eventName: string, callback: Callback) {
    if (!this.listeners.has(eventName)) {
      this.listeners.set(eventName, []);
    }
    this.listeners.get(eventName)!.push(callback);
  }

  private off(eventName: string, callback: Callback) {
    if (!this.listeners.has(eventName)) return;
    const callbacks = this.listeners.get(eventName)!;
    const index = callbacks.indexOf(callback);
    if (index > -1) {
      callbacks.splice(index, 1);
    }
  }

  private emit(eventName: string, data: any) {
    if (!this.listeners.has(eventName)) return;
    const callbacks = this.listeners.get(eventName)!;
    callbacks.forEach((callback) => callback(data));
  }
}

export default BarcodeService.getInstance();
