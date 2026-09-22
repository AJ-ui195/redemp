# Barcode Prefix Mapping Setup

## How It Works

When the scanner reads a numeric-only barcode (like "977000779004"), the system will:

1. **First**: Try exact match with the scanned value ("977000779004")
2. **If not found**: Automatically convert using prefix mapping:
   - "977" → "LGS"
   - Result: "LGS000779004"
3. **Then**: Search for the converted value ("LGS000779004") in the database
4. **Display**: The item if found

## Current Mappings

```typescript
'977': 'LGS',  // 977000779004 -> LGS000779004
'77': 'LGS',   // 77000779004 -> LGS000779004
```

## How to Add More Mappings

Edit `Cashier/cashierScan/constants/barcodePrefixes.ts`:

```typescript
export const BARCODE_PREFIX_MAP: Record<string, string> = {
  '977': 'LGS',   // 977000779004 -> LGS000779004
  '77': 'LGS',    // 77000779004 -> LGS000779004
  '12': 'ABC',    // 12000123456 -> ABC000123456
  '88': 'XYZ',    // 88000987654 -> XYZ000987654
  // Add more as needed
};
```

**Important**: Longer prefixes should be listed first to avoid partial matches.

## Testing

1. Scan a barcode that starts with "977" (e.g., "977000779004")
2. Check the console logs - you should see:
   ```
   Prefix conversion: 977000779004 -> LGS000779004 (977 -> LGS)
   Trying alphanumeric variant: LGS000779004
   Found product with alphanumeric variant: LGS000779004
   ```
3. The item should be found and displayed

## Troubleshooting

If the conversion isn't working:

1. **Check console logs** - Look for "Prefix conversion" messages
2. **Verify mapping** - Make sure the numeric prefix matches what the scanner reads
3. **Check database** - Ensure "LGS000779004" exists in `inventory_products` table
4. **Verify barcode type** - The conversion only works for numeric-only barcodes (EAN13, EAN8, UPC, etc.)

## Example Flow

```
Scanner reads: "977000779004"
↓
Exact match fails (not in DB)
↓
Detected as numeric-only (EAN13)
↓
Prefix conversion: "977" → "LGS"
↓
Try: "LGS000779004"
↓
Found in database! ✅
↓
Display item
```
