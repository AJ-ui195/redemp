<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ScannedProduct;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ScannedProductController extends Controller
{
    private const CASHIER_SCAN_CACHE_KEY = 'cashier_scanned_items_global';

    /**
     * Test endpoint to verify connectivity
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'API is working!',
            'timestamp' => now()->toDateTimeString(),
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Store a scanned product (mobile Add Item → cashier queue).
     * Resolves product from products table; records a 3NF scan row.
     */
    public function store(Request $request)
    {
        \Log::info('ScannedProduct API Request:', [
            'method' => $request->method(),
            'body' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'barcode_value' => 'required|string',
            'barcode_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            $barcodeValue = trim($request->input('barcode_value'));
            $barcodeType = $request->input('barcode_type');

            $product = $this->findProductByBarcode($barcodeValue);
            $committedFromPending = false;

            if (! $product) {
                $product = app(\App\Http\Controllers\SalesController::class)
                    ->commitPendingBarcodeToProduct($barcodeValue);
                $committedFromPending = $product !== null;
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found. Generate the barcode in Inventory first, then scan and tap Add.',
                    'barcode_value' => $barcodeValue,
                    'found' => false,
                ], 404)->header('Access-Control-Allow-Origin', '*');
            }

            $scan = $this->recordScan($product, $barcodeType);

            // Pending inventory adds should not auto-queue to cashier cart.
            if (! $committedFromPending) {
                $this->pushToCashierScanCache($product, $barcodeValue);
            }

            return response()->json([
                'success' => true,
                'message' => $committedFromPending
                    ? 'Product added to inventory successfully'
                    : 'Product added to list successfully',
                'found' => true,
                'added_to_inventory' => $committedFromPending,
                'data' => array_merge($this->productApiData($product, $barcodeValue), [
                    'scan_id' => $scan->id,
                    'scanned_at' => $scan->scanned_at,
                ]),
            ], 201)->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        } catch (\Exception $e) {
            \Log::error('Error saving scanned product:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save scanned product',
                'error' => $e->getMessage(),
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Get recent scans joined with products (3NF).
     */
    public function index()
    {
        try {
            $scans = ScannedProduct::with('product.unit')
                ->orderByDesc('scanned_at')
                ->limit(100)
                ->get()
                ->map(function (ScannedProduct $scan) {
                    if (! $scan->product) {
                        return null;
                    }

                    $barcodeValue = $this->productBarcodeValue($scan->product);

                    return array_merge($this->productApiData($scan->product, $barcodeValue), [
                        'scan_id' => $scan->id,
                        'barcode_type' => $scan->barcode_type,
                        'scanned_at' => $scan->scanned_at,
                    ]);
                })
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $scans,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scanned products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lookup product info without adding to session.
     */
    public function lookupProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode_value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            $barcodeValue = trim($request->input('barcode_value'));
            $product = $this->findProductByBarcode($barcodeValue);

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in inventory',
                    'barcode_value' => $barcodeValue,
                    'found' => false,
                ], 404)->header('Access-Control-Allow-Origin', '*');
            }

            return response()->json([
                'success' => true,
                'found' => true,
                'barcode_value' => $barcodeValue,
                'data' => $this->productApiData($product, $barcodeValue),
            ])->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        } catch (\Exception $e) {
            \Log::error('Error looking up product:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to lookup product',
                'error' => $e->getMessage(),
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Store a scanned product for cashier dashboard.
     */
    public function storeForCashier(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode_value' => 'required|string',
            'barcode_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            $barcodeValue = trim($request->input('barcode_value'));
            $barcodeType = $request->input('barcode_type');

            $product = $this->findProductByBarcode($barcodeValue);
            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in inventory',
                    'barcode_value' => $barcodeValue,
                    'found' => false,
                ], 404)->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
            }

            $this->recordScan($product, $barcodeType);
            $this->pushToCashierScanCache($product, $barcodeValue);

            return response()->json([
                'success' => true,
                'message' => 'Scanned item ready for cashier',
                'barcode_value' => $barcodeValue,
                'found' => true,
                'data' => $this->productApiData($product, $barcodeValue),
            ], 201)->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        } catch (\Exception $e) {
            \Log::error('Error saving cashier scanned product:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save scanned product',
                'error' => $e->getMessage(),
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Get latest scanned product for cashier (last 30 seconds).
     */
    public function getLatestForCashier()
    {
        try {
            $scan = ScannedProduct::with('product.unit')
                ->where('scanned_at', '>=', now()->subSeconds(30))
                ->orderByDesc('scanned_at')
                ->first();

            if (! $scan || ! $scan->product) {
                return response()->json([
                    'success' => false,
                    'found' => false,
                    'message' => 'No recent scans',
                ]);
            }

            $barcodeValue = $this->productBarcodeValue($scan->product);

            return response()->json([
                'success' => true,
                'found' => true,
                'barcode_value' => $barcodeValue,
                'data' => array_merge($this->productApiData($scan->product, $barcodeValue), [
                    'scanned_at' => $scan->scanned_at,
                ]),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching latest cashier scan:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scanned product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update quantity for a scanned item in cashier session
     */
    public function updateQuantity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode_value' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            $barcodeValue = trim($request->input('barcode_value'));
            $quantity = (int) $request->input('quantity');

            $scannedItems = Cache::get(self::CASHIER_SCAN_CACHE_KEY, []);
            $itemIndex = array_search($barcodeValue, array_column($scannedItems, 'barcode_value'));

            if ($itemIndex === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found in scanned items',
                ], 404)->header('Access-Control-Allow-Origin', '*');
            }

            $scannedItems[$itemIndex]['quantity'] = $quantity;
            $scannedItems[$itemIndex]['updated_at'] = now()->toDateTimeString();
            Cache::put(self::CASHIER_SCAN_CACHE_KEY, $scannedItems, 3600);

            return response()->json([
                'success' => true,
                'message' => 'Quantity updated successfully',
                'barcode_value' => $barcodeValue,
                'quantity' => $quantity,
            ])->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        } catch (\Exception $e) {
            \Log::error('Error updating quantity:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update quantity',
                'error' => $e->getMessage(),
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Get all scanned items with quantities for cashier dashboard
     */
    public function getScannedItems()
    {
        try {
            $scannedItems = Cache::get(self::CASHIER_SCAN_CACHE_KEY, []);
            $enrichedItems = [];

            foreach ($scannedItems as $item) {
                $product = null;
                $productId = (int) ($item['product_id'] ?? 0);

                if ($productId > 0) {
                    $product = Product::query()->with('unit')->find($productId);
                }

                if (! $product) {
                    $product = $this->findProductByBarcode($item['barcode_value'] ?? '');
                }

                if (! $product) {
                    continue;
                }

                $barcodeValue = $item['barcode_value']
                    ?: $this->productBarcodeValue($product);

                $enrichedItems[] = array_merge($this->productApiData($product, $barcodeValue), [
                    'quantity' => $item['quantity'] ?? 1,
                    'scanned_at' => $item['scanned_at'] ?? now()->toDateTimeString(),
                    'updated_at' => $item['updated_at'] ?? $item['scanned_at'] ?? now()->toDateTimeString(),
                ]);
            }

            return response()->json([
                'success' => true,
                'items' => $enrichedItems,
                'count' => count($enrichedItems),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching scanned items:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scanned items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a scanned item from cashier session
     */
    public function removeItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode_value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            $barcodeValue = trim($request->input('barcode_value'));
            $scannedItems = Cache::get(self::CASHIER_SCAN_CACHE_KEY, []);
            $scannedItems = array_values(array_filter($scannedItems, function ($item) use ($barcodeValue) {
                return ($item['barcode_value'] ?? null) !== $barcodeValue;
            }));
            Cache::put(self::CASHIER_SCAN_CACHE_KEY, $scannedItems, 3600);

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
                'barcode_value' => $barcodeValue,
            ])->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        } catch (\Exception $e) {
            \Log::error('Error removing item:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item',
                'error' => $e->getMessage(),
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Clear all scanned items from the cashier session cache.
     */
    public function clearItems()
    {
        try {
            Cache::forget(self::CASHIER_SCAN_CACHE_KEY);

            return response()->json([
                'success' => true,
                'message' => 'Scanned items cleared',
            ])->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        } catch (\Exception $e) {
            \Log::error('Error clearing scanned items:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear scanned items',
                'error' => $e->getMessage(),
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    private function findProductByBarcode(string $barcodeValue): ?Product
    {
        $barcodeValue = trim($barcodeValue);
        if ($barcodeValue === '') {
            return null;
        }

        $product = Product::query()
            ->with('unit')
            ->where(function ($q) use ($barcodeValue) {
                $q->where('barcode', $barcodeValue)->orWhere('sku', $barcodeValue);
            })
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->first();

        if ($product) {
            return $product;
        }

        $lower = strtolower($barcodeValue);

        return Product::query()
            ->with('unit')
            ->where(function ($q) use ($lower) {
                $q->whereRaw('LOWER(barcode) = ?', [$lower])
                    ->orWhereRaw('LOWER(sku) = ?', [$lower]);
            })
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->first();
    }

    private function recordScan(Product $product, ?string $barcodeType): ScannedProduct
    {
        return ScannedProduct::create([
            'product_id' => $product->id,
            'barcode_type' => $barcodeType,
            'scanned_at' => now(),
        ]);
    }

    private function productBarcodeValue(Product $product): string
    {
        return (string) ($product->barcode ?: $product->sku ?: $product->id);
    }

    private function productUnitName(Product $product): string
    {
        if ($product->relationLoaded('unit') && $product->unit) {
            return $product->unit->abbreviation ?: ($product->unit->name ?: 'pcs');
        }

        if ($product->unit_id) {
            $unit = Unit::find($product->unit_id);
            if ($unit) {
                return $unit->abbreviation ?: ($unit->name ?: 'pcs');
            }
        }

        return is_string($product->getAttributes()['unit'] ?? null)
            && ($product->getAttributes()['unit'] ?? '') !== ''
            ? $product->getAttributes()['unit']
            : 'pcs';
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return is_string($value) ? $value : Carbon::parse($value)->format('Y-m-d');
    }

    private function productApiData(Product $product, string $barcodeValue): array
    {
        return [
            'barcode_value' => $barcodeValue,
            'item_name' => $product->name,
            'description' => $product->description,
            'price' => $product->selling_price ?? $product->price,
            'price_type' => $product->price_type,
            'unit' => $this->productUnitName($product),
            'quantity_on_hand' => $product->quantity ?? $product->stock_quantity ?? 0,
            'expiration_date' => $this->formatDate($product->expiration_date ?? $product->expiry_date),
            'active_status' => ($product->is_active ?? true) ? 'Active' : 'Inactive',
            'product_id' => $product->id,
            'inventory_product_id' => $product->id,
        ];
    }

    private function pushToCashierScanCache(Product $product, string $barcodeValue): void
    {
        $scannedItems = Cache::get(self::CASHIER_SCAN_CACHE_KEY, []);
        $canonicalBarcode = $barcodeValue !== ''
            ? $barcodeValue
            : $this->productBarcodeValue($product);

        $itemIndex = false;
        foreach ($scannedItems as $index => $item) {
            if ((int) ($item['product_id'] ?? 0) === (int) $product->id) {
                $itemIndex = $index;
                break;
            }
            if (($item['barcode_value'] ?? null) === $canonicalBarcode) {
                $itemIndex = $index;
                break;
            }
        }

        if ($itemIndex !== false) {
            $scannedItems[$itemIndex]['quantity'] = ($scannedItems[$itemIndex]['quantity'] ?? 1) + 1;
            $scannedItems[$itemIndex]['product_id'] = $product->id;
            $scannedItems[$itemIndex]['barcode_value'] = $canonicalBarcode;
            $scannedItems[$itemIndex]['updated_at'] = now()->toDateTimeString();
        } else {
            $scannedItems[] = [
                'product_id' => $product->id,
                'barcode_value' => $canonicalBarcode,
                'quantity' => 1,
                'scanned_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        Cache::put(self::CASHIER_SCAN_CACHE_KEY, $scannedItems, 3600);
    }

/**
     * Capture ID image for Senior Citizen/PWD discount
     */
    public function captureId(Request $request)
    {
        // Increase execution time for large image uploads (up to 10 minutes)
        set_time_limit(600); // 10 minutes to handle large images
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M'); // Increase memory for large base64 images
        ini_set('post_max_size', '100M'); // Allow large POST requests
        ini_set('upload_max_filesize', '100M'); // Allow large file uploads
        
        // Reconnect to database to ensure connection is fresh
        // Try to set max_allowed_packet globally if we have permissions
        try {
            DB::reconnect();
            // Try to set max_allowed_packet globally (requires SUPER privilege)
            // If it fails, we'll continue anyway - MySQL config should handle it
            try {
                DB::statement("SET GLOBAL max_allowed_packet = 67108864");
                \Log::info('Successfully set max_allowed_packet globally');
            } catch (\Exception $globalError) {
                // If SET GLOBAL fails (no SUPER privilege), that's okay
                // The MySQL server should already be configured with appropriate max_allowed_packet
                \Log::debug('Could not set max_allowed_packet globally (may require SUPER privilege):', [
                    'error' => $globalError->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Could not reconnect to database:', ['error' => $e->getMessage()]);
        }
        
        $validator = Validator::make($request->all(), [
            'id_image' => 'required|string', // Base64 encoded image
            'discount_type' => 'required|in:senior_citizen,pwd',
            'customer_name' => 'nullable|string|max:255',
            'id_number' => 'nullable|string|max:255',
            'issuing_lgu' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            $idImage = $request->input('id_image');
            $discountType = $request->input('discount_type');
            
            // Remove data URI prefix if present (scanner app may send with or without it)
            $base64Image = $idImage;
            if (strpos($base64Image, 'data:') === 0) {
                // Extract base64 data after comma
                $parts = explode(',', $base64Image, 2);
                $base64Image = $parts[1] ?? $base64Image;
            }
            
            // Log image size for debugging
            $imageSize = strlen($base64Image);
            $imageSizeMB = ($imageSize * 3) / 4 / 1024 / 1024;
            \Log::info('ID capture request received:', [
                'image_size_bytes' => $imageSize,
                'image_size_mb' => round($imageSizeMB, 2),
                'discount_type' => $discountType,
                'has_auth' => Auth::check(),
            ]);
            
            // Get optional manual data
            $customerName = $request->input('customer_name');
            $idNumber = $request->input('id_number');
            $issuingLgu = $request->input('issuing_lgu');

            // Cache immediately so cashier can show the ID/discount even if DB write is slow/fails.
            $cacheKey = 'cashier_id_capture_global';
            $cacheData = [
                'id_image' => $base64Image,
                'discount_type' => $discountType,
                'discount_id' => null,
                'customer_name' => $customerName,
                'id_number' => $idNumber,
                'issuing_lgu' => $issuingLgu,
                'id_type' => $discountType === 'senior_citizen' ? 'OSCA' : 'PWD',
                'captured_at' => now()->toDateTimeString(),
            ];
            Cache::put($cacheKey, $cacheData, 3600);
            
            // Save to database table (store raw base64 without data URI prefix)
            // Use DB transaction to ensure data is committed
            $maxRetries = 3;
            $retryCount = 0;
            $discount = null;

            if (! Schema::hasTable('senior_pwd_discounts')) {
                \Log::warning('senior_pwd_discounts table missing — ID kept in cache only for cashier display');
            }
            
            while (Schema::hasTable('senior_pwd_discounts') && $retryCount < $maxRetries) {
                try {
                    // Reconnect to database before each attempt
                    // max_allowed_packet is configured in database.php connection options
                    DB::reconnect();
                    
                    DB::beginTransaction();
                    
                    $discountData = [
                'discount_type' => $discountType,
                'id_image' => $base64Image, // Store raw base64
                        'customer_name' => $customerName,
                        'id_number' => $idNumber,
                        'issuing_lgu' => $issuingLgu,
                        'id_type' => $discountType === 'senior_citizen' ? 'OSCA' : 'PWD',
                'captured_at' => now(),
                    ];
                    
                    // Handle captured_by - convert to string if user is authenticated
                    if (Auth::check()) {
                        $discountData['captured_by'] = (string)Auth::id();
                    }
                    
                    \Log::info('Attempting to save discount record:', [
                        'discount_type' => $discountType,
                        'has_image' => !empty($base64Image),
                        'image_size' => strlen($base64Image),
                        'image_size_mb' => round(($imageSize * 3) / 4 / 1024 / 1024, 2),
                        'has_customer_name' => !empty($customerName),
                        'has_id_number' => !empty($idNumber),
                        'has_issuing_lgu' => !empty($issuingLgu),
                        'captured_by' => $discountData['captured_by'] ?? null,
                        'retry_count' => $retryCount,
                    ]);
                    
                    $discount = \App\Models\SeniorPwdDiscount::create($discountData);
                    
                    // Commit the transaction
                    DB::commit();
                    
                    // Verify the record was actually saved
                    $savedRecord = \App\Models\SeniorPwdDiscount::find($discount->id);
                    if (!$savedRecord) {
                        throw new \Exception('Record was created but could not be retrieved from database');
                    }
                    
                    \Log::info('Database record saved and verified:', [
                        'discount_id' => $discount->id,
                        'exists_in_db' => $savedRecord !== null,
                        'record_data' => [
                            'discount_type' => $savedRecord->discount_type,
                            'has_image' => !empty($savedRecord->id_image),
                            'customer_name' => $savedRecord->customer_name,
                            'id_number' => $savedRecord->id_number,
                        ],
                    ]);
                    
                    // Success - break out of retry loop
                    break;
                    
                    // Success - exit retry loop
                    break;
                    
                } catch (\Exception $dbError) {
                    DB::rollBack();
                    $retryCount++;
                    
                    \Log::warning('Database save error (attempt ' . $retryCount . ' of ' . $maxRetries . '):', [
                        'error' => $dbError->getMessage(),
                        'error_code' => $dbError->getCode(),
                    ]);
                    
                    // If it's a MySQL "gone away" error and we have retries left, try again
                    if (strpos($dbError->getMessage(), 'MySQL server has gone away') !== false && $retryCount < $maxRetries) {
                        // Wait a bit before retrying
                        usleep(500000); // 0.5 seconds
                        continue;
                    }
                    
                    // If no more retries or different error, throw
                    \Log::error('Database save failed after retries:', [
                        'error' => $dbError->getMessage(),
                        'error_code' => $dbError->getCode(),
                        'trace' => $dbError->getTraceAsString(),
                    ]);
                    throw $dbError; // Re-throw to be caught by outer catch
                }
            }
            
            if (! $discount && Schema::hasTable('senior_pwd_discounts')) {
                throw new \Exception('Failed to save discount record after ' . $maxRetries . ' attempts');
            }
            
            // Refresh cache with discount_id once DB save succeeds
            if ($discount) {
                $cacheData['discount_id'] = $discount->id;
                Cache::put($cacheKey, $cacheData, 3600);
            }
            
            // Verify cache was stored
            $cached = Cache::get($cacheKey);
            \Log::info('ID captured successfully for cashier discount:', [
                'discount_id' => $discount?->id,
                'discount_type' => $discountType,
                'customer_name' => $customerName,
                'id_number' => $idNumber,
                'issuing_lgu' => $issuingLgu,
                'id_type' => $discountType === 'senior_citizen' ? 'OSCA' : 'PWD',
                'captured_at' => now()->toDateTimeString(),
                'captured_by' => Auth::check() ? Auth::id() : null,
                'cache_stored' => $cached !== null,
                'cache_key' => $cacheKey,
                'image_size_bytes' => strlen($base64Image),
                'has_image' => !empty($base64Image),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'ID captured successfully',
                'discount_type' => $discountType,
                'discount_id' => $discount?->id,
                'captured_at' => now()->toDateTimeString(),
            ])->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            \Log::error('Error capturing ID:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to capture ID: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Get latest captured ID
     */
    public function getLatestId()
    {
        try {
            $cacheKey = "cashier_id_capture_global";
            $idData = Cache::get($cacheKey, null);
            
            if ($idData && isset($idData['id_image']) && !empty($idData['id_image'])) {
                \Log::debug('ID latest request - returning cached data', [
                    'discount_id' => $idData['discount_id'] ?? null,
                    'discount_type' => $idData['discount_type'] ?? null,
                    'has_image' => !empty($idData['id_image']),
                    'image_size' => strlen($idData['id_image'] ?? ''),
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => $idData
                ])->header('Access-Control-Allow-Origin', '*');
            }
            
            \Log::debug('ID latest request - no cached data found');
            
            return response()->json([
                'success' => false,
                'message' => 'No ID captured yet'
            ])->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            \Log::error('Error getting latest ID:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ID',
                'error' => $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Clear captured ID from cache
     */
    public function clearId()
    {
        try {
            $cacheKey = "cashier_id_capture_global";
            Cache::forget($cacheKey);
            
            \Log::info('ID cache cleared');
            
            return response()->json([
                'success' => true,
                'message' => 'ID cleared successfully'
            ])->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            \Log::error('Error clearing ID:', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear ID',
                'error' => $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }
}
