/**
 * Barcode Prefix Mapping
 * 
 * Maps numeric barcode prefixes to alphanumeric prefixes
 * Used when scanner reads numeric-only barcodes but database has alphanumeric versions
 * 
 * Format: { numericPrefix: 'alphanumericPrefix' }
 * Example: When scanner reads "77000779004", it will try "LGS000779004"
 */
export const BARCODE_PREFIX_MAP: Record<string, string> = {
  // Add your prefix mappings here
  // Format: 'numeric_prefix': 'alphanumeric_prefix'
  // Example: '77': 'LGS',  // If barcode starts with 77, replace with LGS
  // Example: '12': 'ABC',  // If barcode starts with 12, replace with ABC
  
  // Common mappings (customize based on your barcode system)
  // IMPORTANT: Longer prefixes must come first to avoid partial matches
  '977': 'LGS', // 977000779004 -> LGS000779004 (checked first - longer prefix)
  '77': 'LGS',  // 77000779004 -> LGS000779004 (checked second - shorter prefix)
};

/**
 * Convert numeric barcode to alphanumeric using prefix mapping
 * IMPORTANT: This is ONE-WAY conversion only (numeric → alphanumeric)
 * NEVER converts alphanumeric barcodes like "LGS000779004" to numeric
 * 
 * @param numericBarcode - The numeric barcode read by scanner (e.g., "977000779004")
 * @returns Array of possible alphanumeric barcodes to try (e.g., ["LGS000779004"])
 */
export function convertNumericToAlphanumeric(numericBarcode: string): string[] {
  const results: string[] = [];
  
  // Safety check: Only convert if input is purely numeric
  // If barcode already has letters (like "LGS000779004"), return empty array (no conversion)
  if (/[A-Za-z]/.test(numericBarcode)) {
    console.warn(`⚠️ Barcode "${numericBarcode}" already contains letters - skipping conversion (one-way only)`);
    return results;
  }
  
  // Try each prefix mapping (longest prefix first to avoid partial matches)
  const sortedPrefixes = Object.entries(BARCODE_PREFIX_MAP).sort((a, b) => b[0].length - a[0].length);
  
  for (const [numericPrefix, alphanumericPrefix] of sortedPrefixes) {
    if (numericBarcode.startsWith(numericPrefix)) {
      // Replace numeric prefix with alphanumeric prefix
      // Example: "977000779004" → "LGS000779004"
      const suffix = numericBarcode.substring(numericPrefix.length);
      const alphanumericBarcode = alphanumericPrefix + suffix;
      results.push(alphanumericBarcode);
      console.log(`🔄 Prefix conversion: ${numericBarcode} -> ${alphanumericBarcode} (${numericPrefix} -> ${alphanumericPrefix})`);
    }
  }
  
  if (results.length === 0) {
    console.warn(`⚠️ No prefix mapping found for: ${numericBarcode}`);
  }
  
  return results;
}

/**
 * Check if barcode should be converted
 * @param barcode - The scanned barcode
 * @param barcodeType - The detected barcode type
 * @returns True if barcode is numeric-only and might need conversion
 */
export function shouldTryConversion(barcode: string, barcodeType: string): boolean {
  // Only try conversion for numeric-only barcodes
  const isNumericOnly = /^[0-9]+$/.test(barcode);
  const isNumericFormat = ['ean13', 'ean8', 'upc_a', 'upc_e', 'itf14'].includes(barcodeType.toLowerCase());
  
  return isNumericOnly && isNumericFormat;
}
