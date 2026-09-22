# Barcode Scanning Issue: Letters Not Being Read

## Problem
When scanning a barcode like "LGS000779004", the scanner reads it as "977000779004" (numbers only, missing the letters).

## Why This Happens

1. **Barcode Encoding**: The actual barcode pattern might be encoded as EAN13/UPC (numeric-only format) even though the human-readable text shows letters. The "LGS" prefix might just be a label, not part of the actual barcode encoding.

2. **Scanner Priority**: The scanner might be detecting it as EAN13 (numeric-only) before trying alphanumeric formats like Code128 or Code39.

## Solutions

### Solution 1: Use Manual Entry (Recommended for Alphanumeric Barcodes)
If the scanner keeps reading only numbers:
1. Tap the manual entry field
2. Type the full barcode: `LGS000779004`
3. Press Search or Enter

The manual entry accepts letters and numbers, so this will work correctly.

### Solution 2: Check Barcode Format
The barcode might actually be encoded as numeric-only. Check:
- Is the barcode pattern itself numeric? (EAN13/UPC are numeric-only)
- Is "LGS" just a prefix label, not part of the encoded barcode?

### Solution 3: Verify Database
Make sure your database has the barcode stored correctly:
- Check if `LGS000779004` exists in your `inventory_products` table
- Check if `977000779004` exists (the numeric version)

### Solution 4: Update Scanner Configuration
I've already updated the scanner to prioritize alphanumeric formats (Code128, Code39, Code93) over numeric-only formats (EAN13, EAN8).

## What I Changed

1. **Reordered barcode types** to prioritize alphanumeric formats
2. **Added logging** to show which barcode type is detected
3. **Added warnings** when numeric-only formats are detected
4. **Updated manual entry placeholder** to indicate it supports letters

## Testing

After the changes:
1. Try scanning the barcode again
2. Check the console logs to see what barcode type is detected
3. If it still reads as numeric-only, use manual entry
4. Verify the barcode format - it might actually be numeric-only in the encoding

## Important Note

Some barcodes are **physically encoded** as numeric-only (like EAN13/UPC), even if the label shows letters. The letters might be:
- A product prefix/code that's not part of the barcode
- A human-readable identifier separate from the barcode encoding

In this case, you'll need to either:
- Use manual entry for the full code
- Store both versions in your database (numeric and alphanumeric)
- Check if your inventory uses the numeric version (977000779004) instead
