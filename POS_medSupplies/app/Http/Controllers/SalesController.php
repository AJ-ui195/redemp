<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleStatus;
use App\Models\PaymentMethod;
use App\Models\CashierShift;
use App\Models\ItemList;
use App\Models\InventoryProduct;
use App\Models\InventoryProductLog;
use App\Models\Product;
use App\Models\RequestStatus;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Barcode;
use App\Models\Category;
use App\Models\Unit;
use App\Models\VoidRequest;
use App\Models\VoidRequestItem;
use App\ItemImageAssetUrl;
use App\Support\ItemInventoryLinker;
use App\Support\PendingBarcodeDraft;
use App\Support\ProductCatalog;
use App\Support\ProductImageStorage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SalesController extends Controller
{
    /**
     * Search items for barcode generation (admin)
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => true,
                'items' => []
            ]);
        }

        $items = ItemList::where(function($q) {
                $q->where('active_status', 'Active')
                  ->orWhere('active_status', '1')
                  ->orWhere('active_status', 1)
                  ->orWhereNull('active_status');
            })
            ->where(function($q) use ($query) {
                $q->where('item', 'LIKE', "%{$query}%")
                  ->orWhere('mpn', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->select('id', 'item', 'mpn', 'price', 'quantity_on_hand', 'unit_of_measure', 'price_type', 'description')
            ->limit(20)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'item' => $item->item,
                    'mpn' => $item->mpn,
                    'price' => $item->price,
                    'quantity_on_hand' => $item->quantity_on_hand,
                ];
            });

        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }

    /**
     * Search products for POS
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');
        $includeZeroStock = $request->get('include_zero_stock', false);

        if ($query === null || trim((string) $query) === '') {
            return response()->json([
                'success' => true,
                'products' => []
            ]);
        }

        $products = ProductCatalog::all()
            ->filter(fn ($row) => ProductCatalog::matchesSearch($row, (string) $query))
            ->map(function ($row) {
                return [
                    'id' => $row->item_list_id ?: ($row->inventory_product_id ?: $row->barcode_id),
                    'product_id' => $row->id,
                    'name' => $row->item_name,
                    'sku' => $row->barcode,
                    'price' => $row->price,
                    'stock_quantity' => (float) ($row->quantity_on_hand ?? 0),
                    'unit' => $row->unit ?? 'piece',
                    'category' => $row->price_type,
                    'price_type' => $row->price_type,
                    'brand' => $row->brand,
                    'requires_prescription' => false,
                    'expiry_date' => $row->expiration_date,
                    'item_id' => $row->item_list_id,
                    'inventory_product_id' => $row->inventory_product_id,
                    'barcode_id' => $row->barcode_id,
                    'cashier_key' => $row->cashier_key,
                    'source' => $row->source,
                ];
            });

        if (!$includeZeroStock) {
            $products = $products->filter(fn ($product) => (float) ($product['stock_quantity'] ?? 0) > 0);
        }

        return response()->json([
            'success' => true,
            'products' => $products->values()->take(200)->all()
        ]);
    }

    /**
     * Store a new sale
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer', // Allow any integer, including negative IDs for scanned items
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,e_wallet,check',
            'amount_tendered' => 'nullable|numeric|min:0',
            'customer_email' => 'nullable|email',
            'payment_details' => 'nullable|array',
            'discount_type' => 'nullable|in:senior,pwd,senior_citizen',
            'discount_id' => 'nullable|integer',
            'discount_id_image' => 'nullable|string',
            'id_number' => 'nullable|string|max:255',
            'id_type' => 'nullable|in:OSCA,PWD',
            'issuing_lgu' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
        ]);

        // Check if user has active shift
        $shift = null;
        if (Schema::hasTable('cashier_shifts')) {
            $shift = CashierShift::query()
                ->where('cashier_user_id', Auth::id())
                ->whereNull('ended_at')
                ->latest('started_at')
                ->first();
        } elseif (Schema::hasTable('shifts')) {
            $shift = Shift::where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();
        }

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift found. Please start a shift first.'
            ], 400);
        }

        // Discount and final amount are calculated on the cashier UI (3% auto, Senior/PWD 20%, etc.).
        // Do not re-apply discounts here — doing so double-discounts and breaks change (amount_tendered - amount).

        DB::beginTransaction();
        try {
            if ($this->usesNewPosSchema()) {
                return $this->storeUsingNewSchema($request, $shift);
            }

            // Verify stock using the same qty cashier/inventory display (inventory_products when matched).
            foreach ($request->items as $item) {
                $qtySold = (float) ($item['quantity'] ?? 0);
                if ($qtySold <= 0) {
                    continue;
                }

                [$itemListProduct, $inventoryProduct, $barcode] = $this->resolveStockTargets($item);

                if (!$itemListProduct && !$inventoryProduct && !$barcode) {
                    continue;
                }

                $available = ItemInventoryLinker::displayedQuantity($itemListProduct, $inventoryProduct);
                if (!$inventoryProduct && !$itemListProduct && $barcode) {
                    $available = (float) ($barcode->quantity_on_hand ?? 0);
                }
                $itemName = $itemListProduct?->item
                    ?? $inventoryProduct?->item_name
                    ?? $barcode?->item_name
                    ?? ($item['name'] ?? 'item');

                if ($available < $qtySold) {
                    throw new \Exception("Insufficient stock for {$itemName}. Available: {$available}");
                }
            }

            // Generate receipt number
            $receiptNumber = Sale::generateReceiptNumber();

            // Calculate change
            $changeAmount = 0;
            if ($request->payment_method === 'cash' && $request->amount_tendered) {
                $changeAmount = $request->amount_tendered - $request->amount;
            }

            // Create sale
            $sale = Sale::create([
                'receipt_number' => $receiptNumber,
                'user_id' => Auth::id(),
                'shift_id' => $shift->id,
                'items' => $request->items,
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'discount' => $request->discount ?? 0,
                'discount_type' => $request->discount_type,
                'id_number' => $request->id_number,
                'id_type' => $request->id_type,
                'issuing_lgu' => $request->issuing_lgu,
                'customer_name' => $request->customer_name,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'amount_tendered' => $request->amount_tendered ?? $request->amount,
                'change_amount' => $changeAmount,
                'customer_email' => $request->customer_email,
                'status' => 'completed',
            ]);

            // Save discount information to database table
            if ($request->discount_type) {
                // Map discount_type from frontend format to database format
                $dbDiscountType = $request->discount_type;
                if ($dbDiscountType === 'senior') {
                    $dbDiscountType = 'senior_citizen';
                }
                
                // Check if there's a recent discount capture (from cache or manual entry)
                $cacheKey = "cashier_id_capture_global";
                $cachedDiscount = \Cache::get($cacheKey);
                
                // Check if there's an existing discount record from scanner capture
                $existingDiscount = null;
                if ($cachedDiscount && isset($cachedDiscount['discount_id'])) {
                    $existingDiscount = \App\Models\SeniorPwdDiscount::find($cachedDiscount['discount_id']);
                }
                
                if ($existingDiscount && !$existingDiscount->sale_id) {
                    // Update existing discount record with sale_id and any additional data
                    $existingDiscount->update([
                        'sale_id' => $sale->id,
                        'customer_name' => $request->customer_name ?? $existingDiscount->customer_name,
                        'id_number' => $request->id_number ?? $existingDiscount->id_number,
                        'id_type' => $request->id_type ?? $existingDiscount->id_type,
                        'issuing_lgu' => $request->issuing_lgu ?? $existingDiscount->issuing_lgu,
                    ]);
                    
                    \Log::info('Updated existing discount record with sale_id:', [
                        'discount_id' => $existingDiscount->id,
                        'sale_id' => $sale->id,
                        'discount_type' => $existingDiscount->discount_type,
                    ]);
                } else {
                    // Create new discount record (for manual entries or if no existing record found)
                $discountData = [
                    'sale_id' => $sale->id,
                        'discount_type' => $dbDiscountType, // Use mapped discount type
                    'customer_name' => $request->customer_name,
                    'id_number' => $request->id_number,
                    'id_type' => $request->id_type,
                    'issuing_lgu' => $request->issuing_lgu,
                    'captured_by' => Auth::id(),
                    'captured_at' => now(),
                ];
                
                // Add ID image if provided (from image capture or manual entry)
                if ($request->discount_id_image) {
                    $discountData['id_image'] = $request->discount_id_image;
                } elseif ($cachedDiscount && isset($cachedDiscount['id_image'])) {
                    $discountData['id_image'] = $cachedDiscount['id_image'];
                }
                
                // Create discount record in database
                \App\Models\SeniorPwdDiscount::create($discountData);
                    
                    \Log::info('Created new discount record:', [
                        'sale_id' => $sale->id,
                        'discount_type' => $dbDiscountType,
                    ]);
                }
                
                // Also store in cache for backward compatibility
                if ($request->discount_id_image) {
                    \Cache::put("sale_discount_{$sale->id}", [
                        'discount_type' => $request->discount_type,
                        'id_image' => $request->discount_id_image,
                    ], 86400); // Store for 24 hours
                }
            }

            // Create payment record
            $paymentDetails = $request->payment_details ?? [];
            $paymentStatus = $paymentDetails['status'] ?? ($request->payment_method === 'e_wallet' ? 'paid' : ($request->payment_method === 'check' ? 'pending' : 'paid'));
            $paidAt = $paymentStatus === 'paid' ? now() : null;
            
            $payment = Payment::create([
                'sale_id' => $sale->id,
                'payment_method' => $request->payment_method,
                'reference_number' => $paymentDetails['reference_number'] ?? null,
                'provider' => $paymentDetails['provider'] ?? null,
                'sender_name' => $paymentDetails['sender_name'] ?? null,
                'amount' => $request->amount,
                'status' => $paymentStatus,
                'paid_at' => $paidAt,
                'cleared_at' => null,
            ]);
            
            // Load payment relationship for response
            $sale->load('payments');

            // Update product stock on the exact sold row (ID, then name + price_type).
            // Never deduct the retail twin when wholesale is sold, or vice versa.
            foreach ($request->items as $item) {
                $qtySold = (float) ($item['quantity'] ?? 0);
                if ($qtySold <= 0) {
                    continue;
                }

                $this->adjustLinkedStock($item, -$qtySold, 'sale', "Sale - Receipt {$receiptNumber}", [
                    'sale_id' => $sale->id ?? null,
                    'quantity_sold' => $qtySold,
                    'price_type' => $item['price_type'] ?? $item['category'] ?? null,
                ]);
            }

            // Create transaction record
            Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber(),
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'type' => 'sale',
                'amount' => $request->amount,
                'description' => "Sale - Receipt: {$receiptNumber}",
                'metadata' => [
                    'payment_method' => $request->payment_method,
                    'items_count' => count($request->items),
                ]
            ]);

            // Update shift totals
            $shift->increment('total_sales', $request->amount);
            $shift->increment('transaction_count');
            
            $paymentMethods = $shift->payment_methods ?? [];
            $paymentMethodKey = $request->payment_method === 'e_wallet' ? 'e_wallet' : $request->payment_method;
            $paymentMethods[$paymentMethodKey] = ($paymentMethods[$paymentMethodKey] ?? 0) + $request->amount;
            $shift->update(['payment_methods' => $paymentMethods]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully',
                'sale' => $sale,
                'receipt_number' => $receiptNumber,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get sale details
     */
    public function show(Sale $sale)
    {
        if ($this->usesNewPosSchema()) {
            $sale->load(['saleItems.product', 'cashier', 'salePayments.paymentMethod']);

            $items = $sale->saleItems->values()->map(function (SaleItem $saleItem, int $index) {
                $remaining = max(0, (int) $saleItem->quantity - (int) ($saleItem->voided_quantity ?? 0));

                return [
                    'index' => $index,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'name' => optional($saleItem->product)->name ?? 'Unknown Product',
                    'product_name' => optional($saleItem->product)->name ?? 'Unknown Product',
                    'product_sku' => optional($saleItem->product)->sku ?? '',
                    'sku' => optional($saleItem->product)->sku ?? '',
                    'quantity' => $remaining,
                    'original_quantity' => (int) $saleItem->quantity,
                    'voided_quantity' => (int) ($saleItem->voided_quantity ?? 0),
                    'price' => (float) $saleItem->unit_price,
                    'total' => $remaining * (float) $saleItem->unit_price,
                ];
            })->filter(fn (array $item) => $item['quantity'] > 0)->values();

            return response()->json([
                'success' => true,
                'sale' => [
                    'id' => $sale->id,
                    'receipt_number' => $sale->sale_number,
                    'sale_number' => $sale->sale_number,
                    'status' => $sale->status,
                    'amount' => $sale->amount,
                    'items' => $items,
                    'voided_items' => [],
                    'sold_at' => $sale->sold_at,
                    'created_at' => $sale->created_at,
                    'cashier' => optional($sale->cashier)->name,
                ],
            ]);
        }

        // Load relationships
        $sale->load(['user', 'shift']);

        // Get product details for items
        $items = collect($sale->items ?? [])->map(function ($item) {
            $product = Schema::hasTable('item_lists')
                ? ItemList::find($item['product_id'] ?? null)
                : null;

            return array_merge($item, [
                'product_name' => $product->item ?? ($item['name'] ?? 'Unknown Product'),
                'product_sku' => $product->mpn ?? ($item['sku'] ?? ''),
            ]);
        });

        $sale->items_details = $items;

        return response()->json([
            'success' => true,
            'sale' => $sale,
        ]);
    }

    /**
     * Process refund
     */
    public function refund(Request $request, Sale $sale)
    {
        $request->validate([
            'reason' => 'required|string',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        if ($sale->getAttribute('status') === 'refunded') { // Use getAttribute for dynamic properties
            return response()->json([
                'success' => false,
                'message' => 'This sale has already been refunded'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $refundAmount = $request->refund_amount ?? $sale->getAttribute('amount');

            // Restore product stock on the exact sold row
            foreach ($sale->getAttribute('items') as $item) {
                $quantityToRestore = (float) ($item['quantity'] ?? 0);
                if ($quantityToRestore <= 0) {
                    continue;
                }

                $this->adjustLinkedStock($item, $quantityToRestore, 'refund', "Refund - Receipt {$sale->getAttribute('receipt_number')}", [
                    'sale_id' => $sale->getKey(),
                    'quantity_restored' => $quantityToRestore,
                ]);
            }

            // Update sale status
            $sale->update([
                'status' => 'refunded'
            ]);

            // Create refund transaction
            Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber(),
                'sale_id' => $sale->getKey(), // Use getKey() for the primary key
                'user_id' => Auth::id(),
                'type' => 'refund',
                'amount' => -$refundAmount,
                'description' => "Refund for Receipt: {$sale->getAttribute('receipt_number')}. Reason: {$request->reason}",
                'metadata' => [
                    'original_payment_method' => $sale->getAttribute('payment_method'),
                    'reason' => $request->reason,
                ]
            ]);

            // Update shift totals if shift is still active
            if ($sale->shift && $sale->shift->status === 'active') {
                $sale->shift->decrement('total_sales', $refundAmount);
                
                $paymentMethods = $sale->shift->payment_methods;
                $paymentMethod = $sale->getAttribute('payment_method');
                $paymentMethods[$paymentMethod] = ($paymentMethods[$paymentMethod] ?? 0) - $refundAmount;
                $sale->shift->update(['payment_methods' => $paymentMethods]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'sale' => $sale
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Save generated barcode into products (barcode / sku columns).
     */
    public function saveBarcode(Request $request)
    {
        try {
            $request->validate([
                'barcode_value' => 'required|string|max:255',
                'item_name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'original_price' => 'nullable|numeric|min:0',
                'price_type' => 'required|in:retail,wholesale',
                'unit' => 'required|string|max:50',
                'expiration_date' => 'nullable|date',
                'mfg_date' => 'nullable|date',
                'barcode_type' => 'nullable|string|max:50',
                'active_status' => 'nullable|in:Active,Inactive',
                'description' => 'nullable|string',
                'quantity_on_hand' => 'nullable|numeric|min:0',
                'item_image' => 'nullable|string',
                'brand' => 'nullable|string|max:255',
                'lot_number' => 'nullable|string|max:255',
                'category_id' => 'nullable|integer|exists:categories,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Barcode save validation failed:', $e->errors());

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', array_map(function ($errors) {
                    return implode(', ', $errors);
                }, $e->errors())),
            ], 422);
        }

        try {
            $imagePath = null;

            if ($request->item_image) {
                if (preg_match('/^data:image\/(\w+);base64,/', $request->item_image, $matches)) {
                    $extension = $matches[1];
                    $imageData = preg_replace('#^data:image/\w+;base64,#', '', str_replace(' ', '+', $request->item_image));
                    $imageBinary = base64_decode($imageData);

                    if ($imageBinary === false || $imageBinary === '') {
                        throw new \Exception('Invalid image data.');
                    }

                    $imagePath = ProductImageStorage::storeBinary($imageBinary, $extension, 'product');
                } else {
                    $imagePath = $request->item_image;
                }
            }

            $price = ItemInventoryLinker::parseMoney($request->price);
            if ($price === null || $price <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid price',
                ], 422);
            }

            $barcodeValue = trim((string) $request->barcode_value);
            $costPrice = $request->exists('original_price')
                ? ItemInventoryLinker::parseMoney($request->input('original_price'))
                : null;

            $unitId = $this->resolveUnitIdFromLabel((string) $request->unit);
            $categoryId = $request->filled('category_id')
                ? (int) $request->category_id
                : (int) (Category::query()->orderBy('id')->value('id') ?: 0);

            if (! $unitId || ! $categoryId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please configure at least one category and unit before saving products.',
                ], 422);
            }

            $existing = ProductCatalog::findByIdentity(
                $request->item_name,
                $request->brand,
                $request->price_type
            );

            $preserveBarcode = $request->boolean('preserve_barcode');

            // Different identity must not reuse another product's barcode/sku.
            // Pending scanner commits pass preserve_barcode so the preview value is saved unchanged.
            if (! $preserveBarcode) {
                if (! $existing) {
                    $barcodeValue = $this->uniqueProductBarcode($barcodeValue);
                } elseif (
                    $barcodeValue !== ''
                    && Product::query()
                        ->where('id', '!=', $existing->id)
                        ->where(function ($q) use ($barcodeValue) {
                            $q->where('barcode', $barcodeValue)->orWhere('sku', $barcodeValue);
                        })
                        ->exists()
                ) {
                    $barcodeValue = $this->uniqueProductBarcode($barcodeValue, $barcodeValue);
                }
            } elseif (
                ! $existing
                && $barcodeValue !== ''
                && Product::query()
                    ->where(function ($q) use ($barcodeValue) {
                        $q->where('barcode', $barcodeValue)->orWhere('sku', $barcodeValue);
                    })
                    ->exists()
            ) {
                // Exact preview barcode already belongs to another product — only then adjust.
                $barcodeValue = $this->uniqueProductBarcode($barcodeValue, $barcodeValue);
            }

            $payload = [
                'name' => $request->item_name,
                'brand' => $request->brand,
                'barcode' => $barcodeValue,
                'selling_price' => $price,
                'price_type' => $request->price_type,
                'cost_price' => $costPrice ?? ($existing->cost_price ?? 0),
                'unit_id' => $unitId,
                'category_id' => $categoryId,
                'lot_number' => $request->lot_number,
                'mfg_date' => $request->mfg_date,
                'expiration_date' => $request->expiration_date,
                'is_active' => ($request->active_status ?? 'Active') === 'Active',
            ];

            if ($imagePath) {
                $payload['image'] = $imagePath;
            }

            if ($request->filled('quantity_on_hand')) {
                $payload['quantity'] = (int) ItemInventoryLinker::quantityForNewRow($request->quantity_on_hand);
            }

            if ($existing) {
                // Do not wipe stock on update unless quantity was explicitly provided
                if (! array_key_exists('quantity', $payload)) {
                    unset($payload['quantity']);
                }
                if (
                    ItemInventoryLinker::isPlaceholderPrice($payload['selling_price'])
                    && ! ItemInventoryLinker::isPlaceholderPrice($existing->selling_price)
                    && (float) $existing->selling_price > 0
                ) {
                    unset($payload['selling_price']);
                }
                $existing->update($payload);
                $product = $existing->fresh();
            } else {
                $payload['sku'] = $this->uniqueProductSku($barcodeValue);
                $payload['quantity'] = $payload['quantity'] ?? (int) ItemInventoryLinker::quantityForNewRow(0);
                $product = Product::create($payload);
            }

            if (Schema::hasTable('product_barcodes')) {
                DB::table('product_barcodes')->updateOrInsert(
                    ['barcode' => $barcodeValue],
                    [
                        'product_id' => $product->id,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Barcode saved to inventory',
                'barcode' => [
                    'id' => $product->id,
                    'barcode_value' => $product->barcode,
                    'item_name' => $product->name,
                    'price' => (float) $product->selling_price,
                    'original_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
                    'price_type' => $product->price_type,
                    'quantity_on_hand' => (float) ($product->quantity ?? 0),
                    'brand' => $product->brand,
                    'lot_number' => $product->lot_number,
                    'item_image' => $product->image,
                ],
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save barcode: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Queue generated barcode details for scanner confirmation.
     * Does NOT create a products row until BarcodeScanner taps Add.
     */
    public function savePendingBarcode(Request $request)
    {
        try {
            $request->validate([
                'barcode_value' => 'required|string|max:255',
                'item_name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'original_price' => 'nullable|numeric|min:0',
                'price_type' => 'required|in:retail,wholesale',
                'unit' => 'required|string|max:50',
                'expiration_date' => 'nullable|date',
                'mfg_date' => 'nullable|date',
                'barcode_type' => 'nullable|string|max:50',
                'active_status' => 'nullable|in:Active,Inactive',
                'description' => 'nullable|string',
                'quantity_on_hand' => 'nullable|numeric|min:0',
                'item_image' => 'nullable|string',
                'brand' => 'nullable|string|max:255',
                'lot_number' => 'nullable|string|max:255',
                'category_id' => 'nullable|integer|exists:categories,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', array_map(function ($errors) {
                    return implode(', ', $errors);
                }, $e->errors())),
            ], 422);
        }

        try {
            $price = ItemInventoryLinker::parseMoney($request->price);
            if ($price === null || $price <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid price',
                ], 422);
            }

            $barcodeValue = trim((string) $request->barcode_value);
            $imagePath = null;

            if ($request->item_image) {
                if (preg_match('/^data:image\/(\w+);base64,/', $request->item_image, $matches)) {
                    $extension = $matches[1];
                    $imageData = preg_replace('#^data:image/\w+;base64,#', '', str_replace(' ', '+', $request->item_image));
                    $imageBinary = base64_decode($imageData);

                    if ($imageBinary === false || $imageBinary === '') {
                        throw new \Exception('Invalid image data.');
                    }

                    $imagePath = ProductImageStorage::storeBinary($imageBinary, $extension, 'product');
                } else {
                    $imagePath = $request->item_image;
                }
            }

            $originalPrice = $request->exists('original_price')
                ? ItemInventoryLinker::parseMoney($request->input('original_price'))
                : null;

            // Free previous draft for this same name+brand+price_type, then ensure barcode is unique
            // against products and other pending drafts (retail vs wholesale must not share a code).
            PendingBarcodeDraft::forgetMatchingIdentity(
                $request->item_name,
                $request->brand,
                $request->price_type
            );

            $barcodeValue = $this->uniqueProductBarcode(
                $this->ensurePriceTypeBarcodeSuffix($barcodeValue, $request->price_type)
            );

            PendingBarcodeDraft::put($barcodeValue, [
                'item_name' => $request->item_name,
                'brand' => $request->brand,
                'price' => $price,
                'original_price' => $originalPrice,
                'price_type' => $request->price_type,
                'unit' => $request->unit,
                'lot_number' => $request->lot_number,
                'expiration_date' => $request->expiration_date,
                'mfg_date' => $request->mfg_date,
                'barcode_type' => $request->barcode_type ?? 'CODE128',
                'active_status' => $request->active_status ?? 'Active',
                'description' => $request->description,
                'quantity_on_hand' => $request->input('quantity_on_hand', 0),
                'item_image_path' => $imagePath,
                'category_id' => $request->category_id,
            ]);

            return response()->json([
                'success' => true,
                'pending' => true,
                'message' => 'Barcode ready. Scan it in the BarcodeScanner app and tap Add to save the product.',
                'barcode' => [
                    'barcode_value' => $barcodeValue,
                    'item_name' => $request->item_name,
                    'brand' => $request->brand,
                    'price' => $price,
                    'price_type' => $request->price_type,
                    'pending' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue barcode: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create/update product from a pending barcode draft (scanner Add).
     */
    public function commitPendingBarcodeToProduct(string $barcodeValue): ?Product
    {
        $draft = PendingBarcodeDraft::get($barcodeValue);
        if (! $draft) {
            return null;
        }

        $reservedBarcode = (string) ($draft['barcode_value'] ?? $barcodeValue);

        // Release the pending reservation BEFORE save so uniqueness logic
        // does not treat this same draft barcode as a collision and rewrite it.
        PendingBarcodeDraft::forget($barcodeValue);
        if ($reservedBarcode !== $barcodeValue) {
            PendingBarcodeDraft::forget($reservedBarcode);
        }

        $request = Request::create('/barcodes/save', 'POST', [
            'barcode_value' => $reservedBarcode,
            'item_name' => $draft['item_name'] ?? null,
            'brand' => $draft['brand'] ?? null,
            'price' => $draft['price'] ?? null,
            'original_price' => $draft['original_price'] ?? null,
            'price_type' => $draft['price_type'] ?? 'retail',
            'unit' => $draft['unit'] ?? 'pcs',
            'lot_number' => $draft['lot_number'] ?? null,
            'expiration_date' => $draft['expiration_date'] ?? null,
            'mfg_date' => $draft['mfg_date'] ?? null,
            'barcode_type' => $draft['barcode_type'] ?? 'CODE128',
            'active_status' => $draft['active_status'] ?? 'Active',
            'description' => $draft['description'] ?? null,
            'quantity_on_hand' => $draft['quantity_on_hand'] ?? 0,
            'item_image' => $draft['item_image_path'] ?? null,
            'category_id' => $draft['category_id'] ?? null,
            // Keep the exact barcode shown in Generate Barcode preview.
            'preserve_barcode' => true,
        ]);

        $response = $this->saveBarcode($request);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            // Re-queue draft so user can retry Add if save failed.
            PendingBarcodeDraft::put($reservedBarcode, $draft);
            throw new \RuntimeException($payload['message'] ?? 'Failed to save pending barcode to products');
        }

        $productId = $payload['barcode']['id'] ?? ($payload['product']['id'] ?? null);

        return $productId ? Product::find($productId) : null;
    }

    private function resolveUnitIdFromLabel(string $unitLabel): ?int
    {
        $label = trim($unitLabel);
        if ($label === '') {
            return Unit::query()->orderBy('id')->value('id');
        }

        $unit = Unit::query()
            ->where(function ($q) use ($label) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($label)])
                    ->orWhereRaw('LOWER(abbreviation) = ?', [mb_strtolower($label)]);
            })
            ->first();

        return $unit?->id ?? Unit::query()->orderBy('id')->value('id');
    }

    private function uniqueProductSku(string $barcodeValue): string
    {
        $base = $barcodeValue !== '' ? $barcodeValue : ('SKU-'.now()->format('YmdHis'));
        $sku = $base;
        $i = 1;
        while (Product::query()->where('sku', $sku)->exists()) {
            $sku = $base.'-'.$i;
            $i++;
        }

        return $sku;
    }

    private function uniqueProductBarcode(string $preferred, ?string $exceptBarcode = null): string
    {
        $base = trim($preferred) !== '' ? trim($preferred) : ('BC-'.now()->format('YmdHis'));
        $barcode = $base;
        $i = 1;

        while ($this->isBarcodeTaken($barcode, $exceptBarcode)) {
            // Append a clear numeric counter without destroying the body/type digits.
            // Example: ...0301 -> ...03012 (still ends with price-type marker when possible)
            if (preg_match('/^(\d{1,18})(01|02)$/', $base, $matches)) {
                $suffix = $matches[2];
                $counter = (string) $i;
                $bodyMax = 20 - strlen($suffix) - strlen($counter);
                $body = substr($matches[1], 0, max(1, $bodyMax));
                $barcode = $body.$counter.$suffix;
            } else {
                $barcode = $base.'-'.$i;
            }
            $i++;
        }

        return $barcode;
    }

    private function isBarcodeTaken(string $barcode, ?string $exceptBarcode = null): bool
    {
        $productTaken = Product::query()
            ->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)->orWhere('sku', $barcode);
            })
            ->exists();

        if ($productTaken) {
            return true;
        }

        return PendingBarcodeDraft::isBarcodeTaken($barcode, $exceptBarcode);
    }

    /**
     * Guarantee retail/wholesale variants do not share the same trailing type marker.
     */
    private function ensurePriceTypeBarcodeSuffix(string $barcode, $priceType): string
    {
        $barcode = preg_replace('/\D+/', '', trim($barcode)) ?: trim($barcode);
        $suffix = ItemInventoryLinker::normalizePriceType($priceType) === 'wholesale' ? '02' : '01';

        if ($barcode === '') {
            return 'BC'.now()->format('YmdHis').$suffix;
        }

        if (preg_match('/^\d+$/', $barcode)) {
            // Already has the correct price-type suffix — do not strip and re-append.
            if (str_ends_with($barcode, $suffix)) {
                return substr($barcode, 0, 20);
            }

            $body = preg_replace('/(01|02)$/', '', $barcode) ?: $barcode;
            $body = substr($body, 0, 18);

            return substr($body.$suffix, 0, 20);
        }

        if (str_ends_with($barcode, '-R') || str_ends_with($barcode, '-W')) {
            return substr($barcode, 0, -2).($suffix === '02' ? '-W' : '-R');
        }

        return $barcode.($suffix === '02' ? '-W' : '-R');
    }

    /**
     * Legacy helper — inventory barcodes now save directly to products.
     */
    private function syncInventoryProductFromBarcode($barcode, Request $request): void
    {
        // no-op (kept for backward compatibility with any callers)
    }

    /**
     * Look up barcode by value
     */
    public function lookupBarcode(Request $request)
    {
        $barcodeValue = $request->get('value', '');

        // Log the lookup request with more details
        \Log::info('=== BARCODE LOOKUP REQUEST ===', [
            'barcode_value' => $barcodeValue,
            'barcode_value_length' => strlen($barcodeValue),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
        ]);

        if (empty($barcodeValue)) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode value is required'
            ], 400)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            // Verify database connection first
            try {
                $dbTest = \DB::connection()->getPdo();
                \Log::info('Database connection: OK');
            } catch (\Exception $dbError) {
                \Log::error('Database connection failed:', ['error' => $dbError->getMessage()]);
                return response()->json([
                    'success' => false,
                    'found' => false,
                    'message' => 'Database connection error: ' . $dbError->getMessage()
                ], 500)->header('Access-Control-Allow-Origin', '*');
            }
            
            // Look up products by barcode or SKU
            $totalBarcodes = Product::whereNotNull('barcode')->count();
            \Log::info('Total product barcodes in database:', ['count' => $totalBarcodes]);

            $product = Product::query()
                ->where(function ($q) use ($barcodeValue) {
                    $q->where('barcode', $barcodeValue)->orWhere('sku', $barcodeValue);
                })
                ->first();

            if (!$product) {
                $trimmedValue = trim($barcodeValue);
                if ($trimmedValue !== $barcodeValue) {
                    $product = Product::query()
                        ->where(function ($q) use ($trimmedValue) {
                            $q->where('barcode', $trimmedValue)->orWhere('sku', $trimmedValue);
                        })
                        ->first();
                }
            }

            if (!$product) {
                $lower = strtolower($barcodeValue);
                $product = Product::query()
                    ->where(function ($q) use ($lower) {
                        $q->whereRaw('LOWER(barcode) = ?', [$lower])
                            ->orWhereRaw('LOWER(sku) = ?', [$lower]);
                    })
                    ->first();
            }

            \Log::info('Product barcode query result:', [
                'searched_value' => $barcodeValue,
                'found' => $product ? 'yes' : 'no',
                'total_barcodes_in_db' => $totalBarcodes,
                'sample_barcodes' => Product::whereNotNull('barcode')->limit(5)->pluck('barcode')->toArray(),
            ]);

            if ($product) {
                $itemImageUrl = ItemImageAssetUrl::resolve($product->image);
                $unitName = 'pcs';
                if ($product->unit_id) {
                    $unit = Unit::find($product->unit_id);
                    $unitName = $unit?->abbreviation ?: ($unit?->name ?: 'pcs');
                }

                return response()->json([
                    'success' => true,
                    'found' => true,
                    'barcode' => [
                        'id' => $product->id,
                        'barcode_value' => $product->barcode ?: $product->sku,
                        'item_name' => $product->name,
                        'price' => $product->selling_price,
                        'original_price' => $product->cost_price,
                        'price_type' => $product->price_type,
                        'unit' => $unitName,
                        'barcode_type' => 'CODE128',
                        'expiration_date' => $product->expiration_date
                            ? Carbon::parse($product->expiration_date)->format('Y-m-d')
                            : null,
                        'active_status' => ($product->is_active ?? true) ? 'Active' : 'Inactive',
                        'description' => null,
                        'quantity_on_hand' => $product->quantity,
                        'item_image' => $itemImageUrl,
                        'brand' => $product->brand,
                        'lot_number' => $product->lot_number,
                        'mfg_date' => $product->mfg_date
                            ? Carbon::parse($product->mfg_date)->format('Y-m-d')
                            : null,
                    ]
                ])->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
            }

            \Log::info('Barcode not found in products:', ['barcode_value' => $barcodeValue]);

            $draft = PendingBarcodeDraft::get($barcodeValue);
            if ($draft) {
                $barcode = PendingBarcodeDraft::toLookupBarcode($draft);
                if (! empty($barcode['item_image'])) {
                    $barcode['item_image'] = ItemImageAssetUrl::resolve($barcode['item_image']);
                }

                return response()->json([
                    'success' => true,
                    'found' => true,
                    'pending' => true,
                    'barcode' => $barcode,
                ])->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
            }

            return response()->json([
                'success' => true,
                'found' => false,
                'message' => 'Barcode not found in database',
                'total_barcodes_in_db' => $totalBarcodes,
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        } catch (\Exception $e) {
            \Log::error('Error in barcode lookup:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'Error querying database: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Simple barcode lookup — flat JSON for mobile apps (reads products table).
     */
    public function lookupBarcodeSimple(Request $request)
    {
        $barcodeValue = trim($request->get('value', ''));

        if ($barcodeValue === '') {
            return response()->json([
                'found' => false,
                'message' => 'Barcode value required',
            ], 400)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        }

        try {
            $product = Product::query()
                ->with('unit')
                ->where(function ($q) use ($barcodeValue) {
                    $q->where('barcode', $barcodeValue)->orWhere('sku', $barcodeValue);
                })
                ->first();

            if (! $product) {
                $trimmed = trim($barcodeValue);
                if ($trimmed !== $barcodeValue) {
                    $product = Product::query()
                        ->with('unit')
                        ->where(function ($q) use ($trimmed) {
                            $q->where('barcode', $trimmed)->orWhere('sku', $trimmed);
                        })
                        ->first();
                }
            }

            if ($product) {
                $unitName = optional($product->unit)->abbreviation
                    ?: (optional($product->unit)->name ?: 'pcs');

                return response()->json([
                    'found' => true,
                    'id' => $product->id,
                    'item_name' => $product->name,
                    'price' => $product->selling_price,
                    'original_price' => $product->cost_price,
                    'price_type' => $product->price_type,
                    'unit' => $unitName,
                    'barcode_value' => $product->barcode ?: $product->sku ?: $barcodeValue,
                    'barcode_type' => 'CODE128',
                    'expiration_date' => $product->expiration_date
                        ? Carbon::parse($product->expiration_date)->format('Y-m-d')
                        : null,
                    'mfg_date' => $product->mfg_date
                        ? Carbon::parse($product->mfg_date)->format('Y-m-d')
                        : null,
                    'active_status' => ($product->is_active ?? true) ? 'Active' : 'Inactive',
                    'description' => null,
                    'quantity_on_hand' => $product->quantity ?? 0,
                    'item_image' => ItemImageAssetUrl::resolve($product->image),
                    'brand' => $product->brand,
                ])
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            }

            $draft = PendingBarcodeDraft::get($barcodeValue);
            if ($draft) {
                $simple = PendingBarcodeDraft::toLookupSimple($draft);
                if (! empty($simple['item_image'])) {
                    $simple['item_image'] = ItemImageAssetUrl::resolve($simple['item_image']);
                }

                return response()->json($simple)
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            }

            return response()->json([
                'found' => false,
                'message' => 'Barcode not found',
            ])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            \Log::error('Simple barcode lookup error:', [
                'error' => $e->getMessage(),
                'barcode_value' => $barcodeValue,
            ]);

            return response()->json([
                'found' => false,
                'message' => 'Server error: '.$e->getMessage(),
            ], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        }
    }

    /**
     * Get today's sales statistics
     */
    public function todayStats(Request $request)
    {
        try {
            $today = Carbon::today();
            
            // Get today's sales - exclude voided and refunded sales
            // Check both status 'completed' and null status (for backward compatibility)
            $todaySales = Sale::whereDate('created_at', $today)
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->where('status', 'completed')
                          ->orWhereNull('status');
                    })
                    ->where('status', '!=', 'voided') // Explicitly exclude voided sales
                    ->where('status', '!=', 'refunded'); // Also exclude refunded sales
                })
                ->get();
            
            $totalSales = $todaySales->sum('amount');
            $transactionCount = $todaySales->count();
            
            // Get transaction data with timestamps for chart
            $transactions = $todaySales->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'total' => (float) $sale->amount,
                    'grand_total' => (float) $sale->amount,
                    'timestamp' => $sale->created_at ? $sale->created_at->toISOString() : null,
                    'created_at' => $sale->created_at ? $sale->created_at->toISOString() : null,
                    'receipt_number' => $sale->receipt_number ?? null,
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'total_sales' => (float) $totalSales,
                'transaction_count' => (int) $transactionCount,
                'transactions' => $transactions,
                'date' => $today->toDateString()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in todayStats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching today stats: ' . $e->getMessage(),
                'total_sales' => 0,
                'transaction_count' => 0
            ], 500);
        }
    }

    /**
     * Get today's sales report with product details
     */
    public function todayReport(Request $request)
    {
        try {
            $today = Carbon::today();
            
            // Get today's sales - exclude voided and refunded sales
            $todaySales = Sale::whereDate('created_at', $today)
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->where('status', 'completed')
                          ->orWhereNull('status');
                    })
                    ->where('status', '!=', 'voided') // Explicitly exclude voided sales
                    ->where('status', '!=', 'refunded'); // Also exclude refunded sales
                })
                ->get();
            
            $totalSales = $todaySales->sum('amount');
            $transactionCount = $todaySales->count();
            
            // Get payment information for all sales
            $saleIds = $todaySales->pluck('id')->toArray();
            $payments = Payment::whereIn('sale_id', $saleIds)->get()->keyBy('sale_id');
            
            // Get void request status for all sales (get all requests, not just most recent)
            $voidRequests = VoidRequest::whereIn('sale_id', $saleIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('sale_id');
            
            // Aggregate products sold today with payment info
            $productSales = [];
            
            foreach ($todaySales as $sale) {
                if (is_string($sale->items)) {
                    $items = json_decode($sale->items, true);
                    $items = is_array($items) ? $items : [];
                } elseif (is_array($sale->items)) {
                    $items = $sale->items;
                } else {
                    $items = [];
                }
                
                // Get voided items for this sale
                $voidedItems = $sale->voided_items ?? [];
                $voidedItems = is_array($voidedItems) ? $voidedItems : [];
                
                // Get payment info for this sale
                $payment = $payments->get($sale->id);
                $paymentMethod = $sale->payment_method ?? ($payment ? $payment->payment_method : null);
                $referenceNumber = $payment ? $payment->reference_number : null;
                
                // Format payment info
                $paymentInfo = '';
                if ($paymentMethod === 'e_wallet' && $referenceNumber) {
                    $paymentInfo = 'E-wallet: ' . $referenceNumber;
                } elseif ($paymentMethod === 'check' && $referenceNumber) {
                    $paymentInfo = 'Check: ' . $referenceNumber;
                } elseif ($paymentMethod) {
                    $paymentInfo = ucfirst(str_replace('_', ' ', $paymentMethod));
                }
                
                // Get discount information from sale
                // If a sale already has approved voided lines, hide discount in this product list view.
                // Business rule requested: once void is approved, remaining lines should not display discount.
                $hasApprovedVoidedLines = !empty($voidedItems);
                $discountType = $sale->discount_type ?? null;
                $saleDiscountPercent = null;
                $saleDiscountAmount = $sale->discount ?? 0;
                $saleSubtotal = $sale->subtotal ?? 0;
                
                // Determine discount percentage based on discount_type (only 20%)
                if ($discountType === 'senior_citizen' || $discountType === 'senior' || $discountType === 'pwd') {
                    $saleDiscountPercent = '20%'; // Both Senior Citizen and PWD get 20% discount
                } elseif ($saleDiscountAmount > 0 && $saleSubtotal > 0) {
                    // Check if this is an automatic 3% discount (when discount_type is null but discount amount exists)
                    $calculatedPercent = ($saleDiscountAmount / $saleSubtotal) * 100;
                    // Allow for small rounding differences (2.9% to 3.1% range)
                    if ($calculatedPercent >= 2.9 && $calculatedPercent <= 3.1) {
                        $saleDiscountPercent = '3%';
                    }
                }
                if ($hasApprovedVoidedLines) {
                    $saleDiscountPercent = null;
                }
                
                if (is_array($items)) {
                    foreach ($items as $index => $item) {
                        // Skip voided items
                        if (in_array($index, $voidedItems)) {
                            continue;
                        }
                        
                        $productName = $item['name'] ?? $item['item'] ?? 'Unknown Product';
                        $quantity = $item['quantity'] ?? 0;
                        $price = $item['price'] ?? 0;
                        $total = $item['total'] ?? ($quantity * $price);
                        
                        // Check if this specific item has a discount (stored as percentage: 3 or 20)
                        $itemDiscount = $item['discount'] ?? null;
                        $itemDiscountPercent = null;
                        
                        if ($itemDiscount && is_numeric($itemDiscount)) {
                            // Item discount is stored as percentage value (3 or 20)
                            if ($itemDiscount == 3) {
                                $itemDiscountPercent = '3%';
                            } elseif ($itemDiscount == 20) {
                                $itemDiscountPercent = '20%';
                            }
                            // Only accept 3% or 20%, ignore any other values
                        }
                        
                        // Use item-level discount if available, otherwise use sale-level discount
                        // Only show 3% or 20%, nothing else
                        $finalDiscountPercent = $hasApprovedVoidedLines
                            ? null
                            : ($itemDiscountPercent ?? $saleDiscountPercent);
                        
                        // Create a unique key that includes sale_id, payment info and discount to separate items by transaction
                        $key = $sale->id . '|' . $productName . '|' . $paymentInfo . '|' . ($finalDiscountPercent ?? 'no-discount');
                        
                        if (isset($productSales[$key])) {
                            $productSales[$key]['quantity'] += $quantity;
                            $productSales[$key]['total'] += $total;
                        } else {
                            // Get void request status for this sale and check if this specific item is being voided
                            $saleVoidRequests = $voidRequests->get($sale->id);
                            $voidStatus = null;
                            if ($saleVoidRequests && $saleVoidRequests->isNotEmpty()) {
                                // Check all void requests for this sale to find if this item is being voided
                                foreach ($saleVoidRequests as $voidRequest) {
                                    $voidedItemIndices = $voidRequest->voided_items ?? [];
                                    if (in_array($index, $voidedItemIndices)) {
                                        // Use the most recent void request status for this item
                                $voidStatus = $voidRequest->status; // pending, approved, rejected
                                        break; // Found the void request for this item
                                    }
                                }
                            }
                            
                            $productSales[$key] = [
                                'name' => $productName,
                                'quantity' => $quantity,
                                'price' => $price,
                                'total' => $total,
                                'payment_method' => $paymentMethod,
                                'payment_info' => $paymentInfo,
                                'reference_number' => $referenceNumber,
                                'discount' => $finalDiscountPercent,
                                'sale_id' => $sale->id,
                                'sale_created_at' => $sale->created_at->toDateTimeString(),
                                'void_status' => $voidStatus,
                                'sale_status' => $sale->status
                            ];
                        }
                    }
                }
            }
            
            // Convert to array and sort by sale creation time, then by total descending within same sale
            $productList = array_values($productSales);
            usort($productList, function($a, $b) {
                // First sort by sale creation time
                $timeCompare = strcmp($a['sale_created_at'] ?? '', $b['sale_created_at'] ?? '');
                if ($timeCompare !== 0) {
                    return $timeCompare;
                }
                // Then sort by total descending within same sale
                return $b['total'] <=> $a['total'];
            });
            
            return response()->json([
                'success' => true,
                'date' => $today->toDateString(),
                'total_sales' => (float) $totalSales,
                'transaction_count' => (int) $transactionCount,
                'products' => $productList,
                'total_items_sold' => array_sum(array_column($productList, 'quantity'))
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in todayReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage(),
                'products' => [],
                'total_sales' => 0,
                'transaction_count' => 0
            ], 500);
        }
    }

    /**
     * Request to void a transaction (requires admin approval)
     */
    public function requestVoid(Request $request, Sale $sale)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'voided_items' => 'nullable|array',
            'voided_items.*' => 'integer|min:0',
        ]);

        if ($this->usesNewPosSchema()) {
            return $this->requestVoidUsingNewSchema($request, $sale);
        }

        // Check if sale is already voided
        if ($sale->status === 'voided') {
            return response()->json([
                'success' => false,
                'message' => 'This transaction has already been voided'
            ], 400);
        }

        // Check if there's already a pending void request for this sale
        $existingRequest = VoidRequest::where('sale_id', $sale->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A void request for this transaction is already pending approval'
            ], 400);
        }

        // Get items from sale
        $saleItems = $sale->items;
        if (is_string($saleItems)) {
            $items = json_decode($saleItems, true);
            $items = is_array($items) ? $items : [];
        } elseif (is_array($saleItems)) {
            $items = $saleItems;
        } else {
            $items = [];
        }
        
        // Ensure items is always an array
        if (!is_array($items)) {
            $items = [];
        }
        
        // If voided_items is provided, validate indices
        $voidedItems = $request->voided_items ?? [];
        if (!empty($voidedItems)) {
            // Validate that all indices are valid
            $maxIndex = count($items) - 1;
            foreach ($voidedItems as $index) {
                if ($index < 0 || $index > $maxIndex) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid item index provided'
                    ], 400);
                }
            }
        } else {
            // If no items selected, void all items (backward compatibility)
            $voidedItems = array_keys($items);
        }

        // Create void request
        $voidRequest = VoidRequest::create([
            'sale_id' => $sale->id,
            'requested_by' => Auth::id(),
            'status' => 'pending',
            'reason' => $request->reason,
            'voided_items' => $voidedItems,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Void request submitted. Waiting for admin approval.',
            'void_request' => $voidRequest
        ]);
    }

    private function requestVoidUsingNewSchema(Request $request, Sale $sale)
    {
        if ($sale->status === 'voided') {
            return response()->json([
                'success' => false,
                'message' => 'This transaction has already been voided',
            ], 400);
        }

        $pendingStatusId = RequestStatus::query()->where('name', 'pending')->value('id');
        if (! $pendingStatusId) {
            return response()->json([
                'success' => false,
                'message' => 'Request status "pending" is not configured.',
            ], 500);
        }

        $existingRequest = VoidRequest::query()
            ->where('sale_id', $sale->id)
            ->where('request_status_id', $pendingStatusId)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A void request for this transaction is already pending approval',
            ], 400);
        }

        $sale->load('saleItems');
        $selectedIds = collect($request->voided_items ?? [])->map(fn ($id) => (int) $id)->filter()->values();

        // Frontend may send sale_item_id values (preferred) or legacy indices.
        $targets = $sale->saleItems->filter(function (SaleItem $saleItem) use ($selectedIds, $sale) {
            $remaining = max(0, (int) $saleItem->quantity - (int) ($saleItem->voided_quantity ?? 0));
            if ($remaining <= 0) {
                return false;
            }

            if ($selectedIds->isEmpty()) {
                return true;
            }

            if ($selectedIds->contains($saleItem->id)) {
                return true;
            }

            // Fallback: treat values as indexes into remaining-visible lines
            $visible = $sale->saleItems->values()->filter(function (SaleItem $row) {
                return max(0, (int) $row->quantity - (int) ($row->voided_quantity ?? 0)) > 0;
            })->values();

            foreach ($selectedIds as $index) {
                $candidate = $visible->get($index);
                if ($candidate && $candidate->id === $saleItem->id) {
                    return true;
                }
            }

            return false;
        });

        if ($targets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No voidable items found for this transaction',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $voidRequest = VoidRequest::create([
                'sale_id' => $sale->id,
                'requested_by_user_id' => Auth::id(),
                'request_status_id' => $pendingStatusId,
                'reason' => $request->reason ?: 'Void request',
            ]);

            foreach ($targets as $saleItem) {
                $qty = max(0, (int) $saleItem->quantity - (int) ($saleItem->voided_quantity ?? 0));
                if ($qty <= 0) {
                    continue;
                }

                VoidRequestItem::create([
                    'void_request_id' => $voidRequest->id,
                    'sale_item_id' => $saleItem->id,
                    'quantity' => $qty,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Void request submitted. Waiting for admin approval.',
                'void_request' => $voidRequest->load('items'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit void request: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve a void request (admin only)
     */
    public function approveVoid(Request $request, VoidRequest $voidRequest)
    {
        if ($this->usesNewPosSchema()) {
            return $this->approveVoidUsingNewSchema($voidRequest);
        }

        if ($voidRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This void request has already been processed'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $sale = $voidRequest->sale;

            // Check if sale is already voided
            if ($sale->status === 'voided') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction has already been voided'
                ], 400);
            }

            // Get items to void from void request
            $voidedItemIndices = $voidRequest->voided_items ?? [];
            if (empty($voidedItemIndices)) {
                // Backward compatibility: if no voided_items specified, void all items
                if (is_string($sale->items)) {
                    $items = json_decode($sale->items, true);
                    $items = is_array($items) ? $items : [];
                } elseif (is_array($sale->items)) {
                    $items = $sale->items;
                } else {
                    $items = [];
                }
                $voidedItemIndices = array_keys($items);
            }

            // Get sale items
            if (is_string($sale->items)) {
                $items = json_decode($sale->items, true);
                $items = is_array($items) ? $items : [];
            } elseif (is_array($sale->items)) {
                $items = $sale->items;
            } else {
                $items = [];
            }
            
            // Get existing voided items from sale
            $existingVoidedItems = $sale->voided_items ?? [];
            $existingVoidedItems = is_array($existingVoidedItems) ? $existingVoidedItems : [];
            
            // Calculate voided amounts
            $voidedSubtotal = 0;
            $voidedAmount = 0;
            $voidedItemsToProcess = [];

            foreach ($voidedItemIndices as $index) {
                if (!isset($items[$index])) {
                    continue; // Skip invalid indices
                }
                
                // Skip if already voided
                if (in_array($index, $existingVoidedItems)) {
                    continue;
                }
                
                $item = $items[$index];
                $itemPrice = (float) ($item['price'] ?? 0);
                $itemQuantity = (float) ($item['quantity'] ?? 0);
                $itemTotal = $itemPrice * $itemQuantity;
                
                $voidedSubtotal += $itemTotal;
                $voidedItemsToProcess[] = $index;
            }

            // Calculate voided amount as line total only (requested behavior)
            $originalSubtotal = (float) ($sale->subtotal ?? 0);
            $originalDiscount = (float) ($sale->discount ?? 0);
            $originalTax = (float) ($sale->tax ?? 0);
            $originalAmount = (float) ($sale->amount ?? 0);
            $voidedAmount = $voidedSubtotal;

            // Restore product stock only for voided items
            foreach ($voidedItemsToProcess as $index) {
                $item = $items[$index];
                
                    $quantityToRestore = (float) ($item['quantity'] ?? 0);
                    if ($quantityToRestore <= 0) {
                        continue;
                    }

                    $this->adjustLinkedStock($item, $quantityToRestore, 'void', "Void items from transaction - Receipt {$sale->receipt_number}", [
                        'sale_id' => $sale->id,
                        'void_request_id' => $voidRequest->id,
                        'quantity_restored' => $quantityToRestore,
                        'voided_item_index' => $index,
                    ]);
            }

            // Update sale: merge voided items and recalculate totals
            $allVoidedItems = array_unique(array_merge($existingVoidedItems, $voidedItemsToProcess));
            $newSubtotal = $originalSubtotal - $voidedSubtotal;
            $newDiscount = $originalDiscount;
            $newTax = $originalTax;
            $newAmount = $originalAmount - $voidedAmount;
            
            // Determine if all items are voided
            $allItemsVoided = count($allVoidedItems) >= count($items);
            
            // Update sale
            $sale->update([
                'voided_items' => $allVoidedItems,
                'subtotal' => max(0, $newSubtotal),
                'discount' => max(0, $newDiscount),
                'tax' => max(0, $newTax),
                'amount' => max(0, $newAmount),
                'status' => $allItemsVoided ? 'voided' : ($sale->status ?? 'completed'),
            ]);

            // Update void request
            $voidRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Create void transaction record
            if (Schema::hasTable('transactions')) {
                Transaction::create([
                    'transaction_number' => Transaction::generateTransactionNumber(),
                    'sale_id' => $sale->id,
                    'user_id' => Auth::id(),
                    'type' => 'void',
                    'amount' => -$voidedAmount,
                    'description' => "Void " . (count($voidedItemsToProcess) === 1 ? 'item' : 'items') . " from transaction - Receipt: {$sale->receipt_number}. Reason: {$voidRequest->reason}",
                    'metadata' => [
                        'void_request_id' => $voidRequest->id,
                        'requested_by' => $voidRequest->requested_by,
                        'approved_by' => Auth::id(),
                        'voided_item_indices' => $voidedItemsToProcess,
                        'voided_amount' => $voidedAmount,
                    ]
                ]);
            }

            // Update shift totals if shift is still active
            if ($sale->shift && $sale->shift->status === 'active') {
                $sale->shift->decrement('total_sales', $voidedAmount);
                // Only decrement transaction count if all items are voided
                if ($allItemsVoided) {
                $sale->shift->decrement('transaction_count');
                }
                
                $paymentMethods = $sale->shift->payment_methods ?? [];
                $paymentMethod = $sale->payment_method ?? 'cash';
                $paymentMethodKey = $paymentMethod === 'e_wallet' ? 'e_wallet' : $paymentMethod;
                if (isset($paymentMethods[$paymentMethodKey])) {
                    $paymentMethods[$paymentMethodKey] = max(0, ($paymentMethods[$paymentMethodKey] ?? 0) - $voidedAmount);
                    $sale->shift->update(['payment_methods' => $paymentMethods]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $allItemsVoided
                    ? 'Void request approved. The sale is fully voided and stock restored where applicable.'
                    : 'Void request approved. Selected line(s) are voided; the sale remains active for the remaining items.',
                'void_request' => $voidRequest->fresh(),
                'sale' => $sale->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error approving void request: ' . $e->getMessage()
            ], 500);
        }
    }

    private function approveVoidUsingNewSchema(VoidRequest $voidRequest)
    {
        $voidRequest->load(['items.saleItem.product', 'sale.saleItems', 'sale.salePayments', 'requestStatus']);

        if ($voidRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This void request has already been processed',
            ], 400);
        }

        $sale = $voidRequest->sale;
        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found for this void request',
            ], 404);
        }

        if ($sale->status === 'voided') {
            return response()->json([
                'success' => false,
                'message' => 'This transaction has already been voided',
            ], 400);
        }

        $approvedStatusId = RequestStatus::query()->where('name', 'approved')->value('id');
        $voidedSaleStatusId = SaleStatus::query()->where('name', 'voided')->value('id');
        $restoreTypeId = DB::table('stock_movement_types')->where('name', 'void_restore')->value('id');

        if (! $approvedStatusId) {
            return response()->json([
                'success' => false,
                'message' => 'Request status "approved" is not configured.',
            ], 500);
        }

        DB::beginTransaction();
        try {
            $voidedAmount = 0.0;
            $processedCount = 0;

            foreach ($voidRequest->items as $voidItem) {
                $saleItem = $voidItem->saleItem;
                if (! $saleItem) {
                    continue;
                }

                $remaining = max(0, (int) $saleItem->quantity - (int) ($saleItem->voided_quantity ?? 0));
                $qtyToVoid = min((int) $voidItem->quantity, $remaining);
                if ($qtyToVoid <= 0) {
                    continue;
                }

                $saleItem->voided_quantity = (int) ($saleItem->voided_quantity ?? 0) + $qtyToVoid;
                $saleItem->save();

                $voidedAmount += $qtyToVoid * (float) $saleItem->unit_price;
                $processedCount++;

                if ($saleItem->product_id) {
                    $product = Product::query()->lockForUpdate()->find($saleItem->product_id);
                    if ($product) {
                        $product->quantity = (int) $product->quantity + $qtyToVoid;
                        $product->save();
                    }

                    if ($restoreTypeId) {
                        DB::table('stock_movements')->insert([
                            'product_id' => $saleItem->product_id,
                            'stock_movement_type_id' => $restoreTypeId,
                            'quantity' => $qtyToVoid,
                            'user_id' => Auth::id(),
                            'sale_id' => $sale->id,
                            'void_request_id' => $voidRequest->id,
                            'damage_request_id' => null,
                            'free_sample_request_id' => null,
                            'occurred_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            if ($processedCount === 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No voidable quantities left on this request',
                ], 400);
            }

            // Reduce recorded payment amount so report totals match remaining lines
            $payment = $sale->salePayments->first();
            if ($payment && $voidedAmount > 0) {
                $payment->update([
                    'amount' => max(0, (float) $payment->amount - $voidedAmount),
                ]);
            }

            $sale->load('saleItems');
            $allItemsVoided = $sale->saleItems->every(function (SaleItem $item) {
                return (int) ($item->voided_quantity ?? 0) >= (int) $item->quantity;
            });

            if ($allItemsVoided && $voidedSaleStatusId) {
                $sale->update(['sale_status_id' => $voidedSaleStatusId]);
            }

            $voidRequest->update([
                'request_status_id' => $approvedStatusId,
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $allItemsVoided
                    ? 'Void request approved. The sale is fully voided and stock restored where applicable.'
                    : 'Void request approved. Selected line(s) are voided; the sale remains active for the remaining items.',
                'void_request' => $voidRequest->fresh(['items', 'requestStatus']),
                'sale' => $sale->fresh(['saleItems', 'salePayments']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error approving void request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a void request (admin only)
     */
    public function rejectVoid(Request $request, VoidRequest $voidRequest)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($this->usesNewPosSchema()) {
            if ($voidRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This void request has already been processed',
                ], 400);
            }

            $rejectedStatusId = RequestStatus::query()->where('name', 'rejected')->value('id');
            if (! $rejectedStatusId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request status "rejected" is not configured.',
                ], 500);
            }

            $voidRequest->update([
                'request_status_id' => $rejectedStatusId,
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $request->rejection_reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Void request rejected',
                'void_request' => $voidRequest->fresh('requestStatus'),
            ]);
        }

        if ($voidRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This void request has already been processed'
            ], 400);
        }

        $voidRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'rejection_reason' => $request->rejection_reason,
            'rejected_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Void request rejected',
            'void_request' => $voidRequest->fresh()
        ]);
    }

    /**
     * Get void statuses for multiple sales
     */
    public function getVoidStatuses(Request $request): JsonResponse
    {
        $saleIds = $this->normalizeVoidStatusSaleIds($request->input('sale_ids', []));

        if ($saleIds === []) {
            return $this->emptyVoidStatusesResponse();
        }

        $payload = $this->usesNewPosSchema()
            ? $this->buildNewSchemaVoidStatuses($saleIds)
            : $this->buildLegacyVoidStatuses($saleIds);

        return response()->json([
            'success' => true,
            'void_statuses' => $payload['void_statuses'],
            'voided_items' => $payload['voided_items'],
        ]);
    }

    /**
     * @param  mixed  $saleIds
     * @return list<int>
     */
    private function normalizeVoidStatusSaleIds($saleIds): array
    {
        if (! is_array($saleIds) || $saleIds === []) {
            return [];
        }

        $saleIds = array_values(array_filter($saleIds, function ($id) {
            return is_numeric($id) && (int) $id > 0;
        }));

        if ($saleIds === []) {
            return [];
        }

        $saleIds = array_map('intval', $saleIds);

        return Sale::query()->whereIn('id', $saleIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function emptyVoidStatusesResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'void_statuses' => [],
            'voided_items' => [],
        ]);
    }

    /**
     * @param  list<int>  $saleIds
     * @return array{void_statuses: array<int, string|null>, voided_items: array<int, list<int>>}
     */
    private function buildNewSchemaVoidStatuses(array $saleIds): array
    {
        $voidRequests = VoidRequest::query()
            ->with(['requestStatus', 'items'])
            ->whereIn('sale_id', $saleIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('sale_id');

        $sales = Sale::query()
            ->with('saleItems')
            ->whereIn('id', $saleIds)
            ->get()
            ->keyBy('id');

        $voidStatuses = [];
        $voidedItemsMap = [];

        foreach ($saleIds as $saleId) {
            $sale = $sales->get($saleId);
            $hasVoidedQty = $sale
                ? $sale->saleItems->contains(fn (SaleItem $item) => (int) ($item->voided_quantity ?? 0) > 0)
                : false;

            $latest = optional($voidRequests->get($saleId))->first();
            $status = $latest?->status;

            $voidStatuses[$saleId] = ($hasVoidedQty && $status !== 'pending') ? 'approved' : $status;
            $voidedItemsMap[$saleId] = $sale
                ? $sale->saleItems->values()
                    ->filter(fn (SaleItem $item) => (int) ($item->voided_quantity ?? 0) >= (int) $item->quantity)
                    ->keys()
                    ->values()
                    ->all()
                : [];
        }

        return [
            'void_statuses' => $voidStatuses,
            'voided_items' => $voidedItemsMap,
        ];
    }

    /**
     * @param  list<int>  $saleIds
     * @return array{void_statuses: array<int, string|null>, voided_items: array<int, mixed>}
     */
    private function buildLegacyVoidStatuses(array $saleIds): array
    {
        $voidRequests = VoidRequest::query()
            ->whereIn('sale_id', $saleIds)
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('sale_id')
            ->map(function ($requests) {
                $request = $requests->first();

                return [
                    'status' => $request->status,
                    'voided_items' => $request->voided_items ?? [],
                ];
            });

        $sales = Sale::query()->whereIn('id', $saleIds)->get()->keyBy('id');

        $voidStatuses = [];
        $voidedItemsMap = [];

        foreach ($saleIds as $saleId) {
            $voidRequestData = $voidRequests->get($saleId);
            $sale = $sales->get($saleId);
            $saleVoidedItems = $sale ? ($sale->voided_items ?? []) : [];
            $saleVoidedItems = is_array($saleVoidedItems) ? $saleVoidedItems : [];

            if ($saleVoidedItems !== []) {
                $voidStatuses[$saleId] = 'approved';
                $voidedItemsMap[$saleId] = $saleVoidedItems;
                continue;
            }

            if ($voidRequestData) {
                $voidStatuses[$saleId] = $voidRequestData['status'];
                $voidedItemsMap[$saleId] = ($voidRequestData['status'] === 'approved' && $sale)
                    ? ($voidRequestData['voided_items'] ?? [])
                    : [];
            } else {
                $voidStatuses[$saleId] = null;
                $voidedItemsMap[$saleId] = [];
            }
        }

        return [
            'void_statuses' => $voidStatuses,
            'voided_items' => $voidedItemsMap,
        ];
    }

    /**
     * Sum line totals for items being voided (matches approveVoid line math).
     */
    private function sumSaleLineAmountForIndices($saleItems, array $voidedItemIndices): float
    {
        $items = is_string($saleItems) ? json_decode($saleItems, true) : $saleItems;
        if (!is_array($items)) {
            return 0.0;
        }

        $voidedItemIndices = array_values(array_unique(array_map('intval', $voidedItemIndices)));

        if ($voidedItemIndices === []) {
            $voidedItemIndices = array_keys($items);
        }

        $sum = 0.0;
        foreach ($voidedItemIndices as $index) {
            if (!isset($items[$index]) || !is_array($items[$index])) {
                continue;
            }
            $item = $items[$index];
            $price = (float) ($item['price'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if (isset($item['total']) && is_numeric($item['total'])) {
                $sum += (float) $item['total'];
            } else {
                $sum += $price * $qty;
            }
        }

        return round($sum, 2);
    }

    /**
     * Get pending void requests (admin only)
     */
    public function getPendingVoidRequests()
    {
        if ($this->usesNewPosSchema()) {
            $pendingStatusId = RequestStatus::query()->where('name', 'pending')->value('id');

            $pendingRequests = VoidRequest::with(['sale.saleItems.product', 'requestedBy', 'items.saleItem.product'])
                ->when($pendingStatusId, fn ($q) => $q->where('request_status_id', $pendingStatusId))
                ->orderByDesc('created_at')
                ->get()
                ->map(function (VoidRequest $request) {
                    $sale = $request->sale;
                    if (! $sale) {
                        return null;
                    }

                    $voidedItemNames = [];
                    $voidAmount = 0.0;

                    foreach ($request->items as $voidItem) {
                        $saleItem = $voidItem->saleItem;
                        if (! $saleItem) {
                            continue;
                        }
                        $name = optional($saleItem->product)->name ?? 'Unknown Product';
                        $qty = (int) $voidItem->quantity;
                        $voidedItemNames[] = "{$name} (x{$qty})";
                        $voidAmount += $qty * (float) $saleItem->unit_price;
                    }

                    $remainingNames = $sale->saleItems
                        ->filter(fn (SaleItem $item) => max(0, (int) $item->quantity - (int) ($item->voided_quantity ?? 0)) > 0)
                        ->reject(fn (SaleItem $item) => $request->items->contains('sale_item_id', $item->id))
                        ->map(function (SaleItem $item) {
                            $qty = max(0, (int) $item->quantity - (int) ($item->voided_quantity ?? 0));
                            $name = optional($item->product)->name ?? 'Unknown Product';

                            return "{$name} (x{$qty})";
                        })
                        ->values()
                        ->all();

                    if (! empty($voidedItemNames)) {
                        $itemsDisplay = '<strong>Voiding:</strong> '.implode(', ', array_slice($voidedItemNames, 0, 3));
                        if (count($voidedItemNames) > 3) {
                            $itemsDisplay .= '...';
                        }
                        if (! empty($remainingNames)) {
                            $itemsDisplay .= '<br><small class="text-muted">Remaining: '.implode(', ', array_slice($remainingNames, 0, 2));
                            if (count($remainingNames) > 2) {
                                $itemsDisplay .= '...';
                            }
                            $itemsDisplay .= '</small>';
                        }
                    } else {
                        $itemsDisplay = 'No items';
                    }

                    return [
                        'id' => $request->id,
                        'sale_id' => $sale->id,
                        'receipt_number' => $sale->sale_number,
                        'amount' => $voidAmount > 0 ? $voidAmount : (float) $sale->amount,
                        'requested_by' => optional($request->requestedBy)->name ?? 'Unknown',
                        'reason' => $request->reason,
                        'items' => $itemsDisplay,
                        'created_at' => $request->created_at->toDateTimeString(),
                        'sale_created_at' => optional($sale->created_at)?->toDateTimeString(),
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'pending_requests' => $pendingRequests,
            ]);
        }

        $pendingRequests = VoidRequest::with(['sale', 'requestedBy'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($request) {
                $sale = $request->sale;
                
                // Get item names from sale items
                $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                $voidedItemIndices = $request->voided_items ?? [];
                
                $itemNames = [];
                $voidedItemNames = [];
                if (is_array($items)) {
                    foreach ($items as $index => $item) {
                        $itemName = $item['name'] ?? $item['item'] ?? 'Unknown Product';
                        $quantity = $item['quantity'] ?? 0;
                        if ($quantity > 0) {
                            if (in_array($index, $voidedItemIndices)) {
                                $voidedItemNames[] = "{$itemName} (x{$quantity})";
                            } else {
                            $itemNames[] = "{$itemName} (x{$quantity})";
                        }
                    }
                }
                }
                
                // Show voided items if specified, otherwise show all items
                if (!empty($voidedItemNames)) {
                    $itemsDisplay = '<strong>Voiding:</strong> ' . implode(', ', array_slice($voidedItemNames, 0, 3));
                    if (count($voidedItemNames) > 3) {
                        $itemsDisplay .= '...';
                    }
                    if (!empty($itemNames)) {
                        $itemsDisplay .= '<br><small class="text-muted">Remaining: ' . implode(', ', array_slice($itemNames, 0, 2));
                        if (count($itemNames) > 2) {
                            $itemsDisplay .= '...';
                        }
                        $itemsDisplay .= '</small>';
                    }
                } else {
                $itemsDisplay = !empty($itemNames) ? implode(', ', array_slice($itemNames, 0, 3)) : 'No items';
                if (count($itemNames) > 3) {
                    $itemsDisplay .= '...';
                    }
                }

                $voidAmount = $this->sumSaleLineAmountForIndices($sale->items, $voidedItemIndices ?? []);
                if ($voidAmount <= 0) {
                    $voidAmount = (float) ($sale->amount ?? 0);
                }
                
                return [
                    'id' => $request->id,
                    'sale_id' => $sale->id,
                    'receipt_number' => $sale->receipt_number,
                    'amount' => $voidAmount,
                    'requested_by' => $request->requestedBy->name ?? 'Unknown',
                    'reason' => $request->reason,
                    'items' => $itemsDisplay,
                    'created_at' => $request->created_at->toDateTimeString(),
                    'sale_created_at' => $sale->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'pending_requests' => $pendingRequests
        ]);
    }

    /**
     * Get void transaction history (admin only)
     */
    public function getVoidTransactionHistory()
    {
        try {
            if ($this->usesNewPosSchema()) {
                $voidRequests = VoidRequest::with([
                    'sale.saleItems.product',
                    'requestedBy',
                    'approvedBy',
                    'items.saleItem.product',
                    'requestStatus',
                ])
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(function (VoidRequest $request) {
                        $sale = $request->sale;
                        if (! $sale) {
                            return [
                                'id' => $request->id,
                                'sale_id' => null,
                                'receipt_number' => 'N/A',
                                'amount' => 0,
                                'status' => $request->status,
                                'requested_by' => optional($request->requestedBy)->name ?? 'Unknown',
                                'approved_by' => optional($request->approvedBy)->name,
                                'reason' => $request->reason,
                                'rejection_reason' => $request->review_notes,
                                'items' => 'No items (sale not found)',
                                'created_at' => optional($request->created_at)?->toDateTimeString(),
                                'approved_at' => $request->status === 'approved' ? optional($request->reviewed_at)?->toDateTimeString() : null,
                                'rejected_at' => $request->status === 'rejected' ? optional($request->reviewed_at)?->toDateTimeString() : null,
                                'sale_created_at' => null,
                            ];
                        }

                        $itemNames = [];
                        $voidAmount = 0.0;
                        foreach ($request->items as $voidItem) {
                            $saleItem = $voidItem->saleItem;
                            if (! $saleItem) {
                                continue;
                            }
                            $name = optional($saleItem->product)->name ?? 'Unknown Product';
                            $qty = (int) $voidItem->quantity;
                            $itemNames[] = "{$name} (x{$qty})";
                            $voidAmount += $qty * (float) $saleItem->unit_price;
                        }

                        $itemsDisplay = ! empty($itemNames)
                            ? implode(', ', array_slice($itemNames, 0, 3))
                            : 'No items';
                        if (count($itemNames) > 3) {
                            $itemsDisplay .= '...';
                        }

                        return [
                            'id' => $request->id,
                            'sale_id' => $sale->id,
                            'receipt_number' => $sale->sale_number ?? 'N/A',
                            'amount' => $voidAmount > 0 ? $voidAmount : (float) $sale->amount,
                            'status' => $request->status,
                            'requested_by' => optional($request->requestedBy)->name ?? 'Unknown',
                            'approved_by' => optional($request->approvedBy)->name,
                            'reason' => $request->reason,
                            'rejection_reason' => $request->review_notes,
                            'items' => $itemsDisplay,
                            'created_at' => optional($request->created_at)?->toDateTimeString(),
                            'approved_at' => $request->status === 'approved' ? optional($request->reviewed_at)?->toDateTimeString() : null,
                            'rejected_at' => $request->status === 'rejected' ? optional($request->reviewed_at)?->toDateTimeString() : null,
                            'sale_created_at' => optional($sale->created_at)?->toDateTimeString(),
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'void_requests' => $voidRequests,
                ]);
            }

            $voidRequests = VoidRequest::with(['sale', 'requestedBy', 'approvedBy'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($request) {
                    $sale = $request->sale;
                    
                    // Handle case where sale might be null
                    if (!$sale) {
                        return [
                            'id' => $request->id,
                            'sale_id' => null,
                            'receipt_number' => 'N/A',
                            'amount' => 0,
                            'status' => $request->status,
                            'requested_by' => ($request->requestedBy && $request->requestedBy->name) ? $request->requestedBy->name : 'Unknown',
                            'approved_by' => ($request->approvedBy && $request->approvedBy->name) ? $request->approvedBy->name : null,
                            'reason' => $request->reason,
                            'rejection_reason' => $request->rejection_reason,
                            'items' => 'No items (sale not found)',
                            'created_at' => $request->created_at ? $request->created_at->toDateTimeString() : null,
                            'approved_at' => $request->approved_at ? $request->approved_at->toDateTimeString() : null,
                            'rejected_at' => $request->rejected_at ? $request->rejected_at->toDateTimeString() : null,
                            'sale_created_at' => null,
                        ];
                    }
                    
                    // Get item names from sale items
                    $saleItems = $sale->items;
                    if (is_string($saleItems)) {
                        $items = json_decode($saleItems, true);
                        $items = is_array($items) ? $items : [];
                    } elseif (is_array($saleItems)) {
                        $items = $saleItems;
                    } else {
                        $items = [];
                    }
                    $itemNames = [];
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $itemName = $item['name'] ?? $item['item'] ?? 'Unknown Product';
                            $quantity = $item['quantity'] ?? 0;
                            if ($quantity > 0) {
                                $itemNames[] = "{$itemName} (x{$quantity})";
                            }
                        }
                    }
                    $itemsDisplay = !empty($itemNames) ? implode(', ', array_slice($itemNames, 0, 3)) : 'No items';
                    if (count($itemNames) > 3) {
                        $itemsDisplay .= '...';
                    }

                    $voidIndices = $request->voided_items ?? [];
                    $voidIndices = is_array($voidIndices) ? $voidIndices : [];
                    $voidAmount = $this->sumSaleLineAmountForIndices($sale->items, $voidIndices);
                    if ($voidAmount <= 0) {
                        $voidAmount = (float) ($sale->amount ?? 0);
                    }
                    
                    return [
                        'id' => $request->id,
                        'sale_id' => $sale->id,
                        'receipt_number' => $sale->receipt_number ?? 'N/A',
                        'amount' => $voidAmount,
                        'status' => $request->status,
                        'requested_by' => ($request->requestedBy && $request->requestedBy->name) ? $request->requestedBy->name : 'Unknown',
                        'approved_by' => ($request->approvedBy && $request->approvedBy->name) ? $request->approvedBy->name : null,
                        'reason' => $request->reason,
                        'rejection_reason' => $request->rejection_reason,
                        'items' => $itemsDisplay,
                        'created_at' => $request->created_at ? $request->created_at->toDateTimeString() : null,
                        'approved_at' => $request->approved_at ? $request->approved_at->toDateTimeString() : null,
                        'rejected_at' => $request->rejected_at ? $request->rejected_at->toDateTimeString() : null,
                        'sale_created_at' => $sale->created_at ? $sale->created_at->toDateTimeString() : null,
                    ];
                })
                ->filter(); // Remove any null entries

            return response()->json([
                'success' => true,
                'void_requests' => $voidRequests
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getVoidTransactionHistory: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading void transaction history: ' . $e->getMessage(),
                'void_requests' => []
            ], 500);
        }
    }

    /**
     * Resolve product for a cart line against the products table.
     */
    private function resolveProduct(array $item): ?Product
    {
        $productId = (int) ($item['product_id'] ?? 0);
        $itemListId = (int) ($item['item_id'] ?? 0);
        $inventoryProductId = (int) ($item['inventory_product_id'] ?? 0);
        $cashierKey = (string) ($item['cashier_key'] ?? $item['uniqueKey'] ?? '');

        if ($productId <= 0 && preg_match('/^(?:p|il|ip)-(\d+)$/', $cashierKey, $matches)) {
            $productId = (int) $matches[1];
        }

        if ($productId <= 0 && $itemListId > 0) {
            $productId = $itemListId;
        }

        if ($productId <= 0 && $inventoryProductId > 0) {
            $productId = $inventoryProductId;
        }

        if ($productId > 0) {
            $product = Product::find($productId);
            if ($product) {
                return $product;
            }
        }

        $sku = trim((string) ($item['sku'] ?? $item['mpn'] ?? $item['barcode'] ?? $item['barcode_value'] ?? ''));
        if ($sku !== '' && strtoupper($sku) !== 'N/A') {
            $product = Product::query()
                ->where(function ($q) use ($sku) {
                    $q->where('barcode', $sku)->orWhere('sku', $sku);
                })
                ->first();
            if ($product) {
                return $product;
            }
        }

        $name = trim((string) ($item['name'] ?? ''));
        $brand = $item['brand'] ?? null;
        $priceType = ItemInventoryLinker::normalizePriceType($item['price_type'] ?? $item['category'] ?? null);
        if ($name !== '') {
            return ProductCatalog::findByIdentity($name, $brand, $priceType)
                ?: Product::query()
                    ->where('name', $name)
                    ->when($priceType, fn ($q) => $q->where('price_type', $priceType))
                    ->when($brand !== null && $brand !== '', fn ($q) => $q->whereRaw('LOWER(TRIM(brand)) = ?', [mb_strtolower(trim((string) $brand))]))
                    ->first();
        }

        return null;
    }

    private function usesNewPosSchema(): bool
    {
        return Schema::hasTable('products')
            && Schema::hasColumn('products', 'quantity')
            && Schema::hasTable('sale_items')
            && Schema::hasColumn('sales', 'sale_number');
    }

    private function storeUsingNewSchema(Request $request, $shift)
    {
        foreach ($request->items as $item) {
            $qtySold = (int) ($item['quantity'] ?? 0);
            if ($qtySold <= 0) {
                continue;
            }

            $product = $this->resolveProduct($item);
            if (! $product) {
                throw new \Exception('Product not found for item: '.($item['name'] ?? 'unknown'));
            }

            if ((int) $product->quantity < $qtySold) {
                throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->quantity}");
            }
        }

        $completedStatusId = SaleStatus::query()->where('name', 'completed')->value('id');
        if (! $completedStatusId) {
            throw new \Exception('Sale status "completed" is not configured.');
        }

        $paymentMethodName = match ($request->payment_method) {
            'cash' => 'cash',
            'e_wallet', 'gcash', 'maya' => 'e_wallet',
            'check' => 'check',
            default => 'cash',
        };
        $paymentMethodId = PaymentMethod::query()->where('name', $paymentMethodName)->value('id')
            ?? PaymentMethod::query()->where('name', 'cash')->value('id');

        if (! $paymentMethodId) {
            throw new \Exception('Payment method is not configured.');
        }

        $saleNumber = Sale::generateReceiptNumber();
        $cashierShiftId = $shift instanceof CashierShift ? $shift->id : null;

        $sale = Sale::create([
            'cashier_user_id' => Auth::id(),
            'cashier_shift_id' => $cashierShiftId,
            'sale_number' => $saleNumber,
            'sale_status_id' => $completedStatusId,
            'sold_at' => now(),
        ]);

        $saleTypeId = DB::table('stock_movement_types')->where('name', 'sale')->value('id');

        foreach ($request->items as $item) {
            $qtySold = (int) ($item['quantity'] ?? 0);
            if ($qtySold <= 0) {
                continue;
            }

            $product = $this->resolveProduct($item);
            if (! $product) {
                continue;
            }

            $unitPrice = (float) ($item['price'] ?? $product->selling_price);

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $qtySold,
                'voided_quantity' => 0,
                'unit_price' => $unitPrice,
            ]);

            $before = (int) $product->quantity;
            $after = max(0, $before - $qtySold);
            $product->update(['quantity' => $after]);

            if ($saleTypeId) {
                DB::table('stock_movements')->insert([
                    'product_id' => $product->id,
                    'stock_movement_type_id' => $saleTypeId,
                    'quantity' => -1 * $qtySold,
                    'user_id' => Auth::id(),
                    'sale_id' => $sale->id,
                    'void_request_id' => null,
                    'damage_request_id' => null,
                    'free_sample_request_id' => null,
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method_id' => $paymentMethodId,
            'amount' => $request->amount,
            ...$this->salePaymentDiscountAttributes($request),
        ]);

        $this->linkSeniorPwdDiscountToSale($sale, $request);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Sale completed successfully',
            'sale' => $sale->load(['saleItems', 'salePayments']),
            'receipt_number' => $saleNumber,
        ]);
    }

    /**
     * Map cashier Senior/PWD discount payload onto sale_payments columns.
     */
    private function salePaymentDiscountAttributes(Request $request): array
    {
        $rawType = strtolower(trim((string) $request->input('discount_type', '')));
        if ($rawType === '') {
            return [
                'discount_type' => null,
                'is_senior_citizen' => false,
                'is_pwd' => false,
                'discount_amount' => 0,
                'customer_name' => null,
                'id_number' => null,
                'id_type' => null,
                'issuing_lgu' => null,
                'senior_pwd_discount_id' => null,
            ];
        }

        $isSenior = in_array($rawType, ['senior', 'senior_citizen'], true);
        $isPwd = $rawType === 'pwd';
        $normalized = $isSenior ? 'senior_citizen' : ($isPwd ? 'pwd' : $rawType);

        $discountId = $request->filled('discount_id') ? (int) $request->input('discount_id') : null;
        if (! $discountId && Schema::hasTable('senior_pwd_discounts')) {
            $types = $normalized === 'senior_citizen'
                ? ['senior_citizen', 'senior']
                : [$normalized];

            $latest = \App\Models\SeniorPwdDiscount::query()
                ->whereNull('sale_id')
                ->whereIn('discount_type', $types)
                ->orderByDesc('captured_at')
                ->orderByDesc('id')
                ->first();
            $discountId = $latest?->id;
        }

        return [
            'discount_type' => $normalized,
            'is_senior_citizen' => $isSenior,
            'is_pwd' => $isPwd,
            'discount_amount' => (float) ($request->input('discount') ?? 0),
            'customer_name' => $request->input('customer_name'),
            'id_number' => $request->input('id_number'),
            'id_type' => $request->input('id_type') ?: ($isSenior ? 'OSCA' : ($isPwd ? 'PWD' : null)),
            'issuing_lgu' => $request->input('issuing_lgu'),
            'senior_pwd_discount_id' => $discountId,
        ];
    }

    private function linkSeniorPwdDiscountToSale(Sale $sale, Request $request): void
    {
        if (! Schema::hasTable('senior_pwd_discounts')) {
            return;
        }

        $rawType = strtolower(trim((string) $request->input('discount_type', '')));
        if ($rawType === '') {
            return;
        }

        $normalized = in_array($rawType, ['senior', 'senior_citizen'], true)
            ? 'senior_citizen'
            : ($rawType === 'pwd' ? 'pwd' : $rawType);

        $discount = null;
        if ($request->filled('discount_id')) {
            $discount = \App\Models\SeniorPwdDiscount::find((int) $request->input('discount_id'));
        }

        if (! $discount) {
            $discount = \App\Models\SeniorPwdDiscount::query()
                ->whereNull('sale_id')
                ->where(function ($q) use ($normalized) {
                    $q->where('discount_type', $normalized);
                    if ($normalized === 'senior_citizen') {
                        $q->orWhere('discount_type', 'senior');
                    }
                })
                ->orderByDesc('captured_at')
                ->orderByDesc('id')
                ->first();
        }

        if ($discount) {
            $discount->update([
                'sale_id' => $sale->id,
                'customer_name' => $request->input('customer_name', $discount->customer_name),
                'id_number' => $request->input('id_number', $discount->id_number),
                'id_type' => $request->input('id_type', $discount->id_type),
                'issuing_lgu' => $request->input('issuing_lgu', $discount->issuing_lgu),
            ]);

            if (Schema::hasColumn('sale_payments', 'senior_pwd_discount_id')) {
                SalePayment::query()
                    ->where('sale_id', $sale->id)
                    ->update(['senior_pwd_discount_id' => $discount->id]);
            }
        }
    }

    /**
     * Resolve the exact item_lists, inventory_products, and leftover barcode rows
     * for a cart/sale line.
     *
     * @return array{0: ?ItemList, 1: ?InventoryProduct, 2: ?Barcode}
     */
    private function resolveStockTargets(array $item): array
    {
        if ($this->usesNewPosSchema()) {
            return [null, null, null];
        }

        if (! Schema::hasTable('item_lists') && ! Schema::hasTable('inventory_products') && ! Schema::hasTable('barcodes')) {
            return [null, null, null];
        }

        $name = $item['name'] ?? null;
        $rawPriceType = $item['price_type'] ?? $item['category'] ?? null;
        $normalizedPriceType = ItemInventoryLinker::normalizePriceType($rawPriceType);
        $priceType = in_array($normalizedPriceType, ['retail', 'wholesale'], true)
            ? $rawPriceType
            : null;
        $productId = (int) ($item['product_id'] ?? 0);
        $itemListId = (int) ($item['item_id'] ?? 0);
        $inventoryProductId = (int) ($item['inventory_product_id'] ?? 0);
        $barcodeId = (int) ($item['barcode_id'] ?? 0);
        $sku = trim((string) ($item['sku'] ?? $item['barcode'] ?? $item['barcode_value'] ?? ''));
        $cashierKey = (string) ($item['cashier_key'] ?? $item['uniqueKey'] ?? '');

        if ($itemListId <= 0 && preg_match('/^il-(\d+)$/', $cashierKey, $matches)) {
            $itemListId = (int) $matches[1];
        }
        if ($inventoryProductId <= 0 && preg_match('/^ip-(\d+)$/', $cashierKey, $matches)) {
            $inventoryProductId = (int) $matches[1];
        }
        if ($barcodeId <= 0 && preg_match('/^bc-(\d+)$/', $cashierKey, $matches)) {
            $barcodeId = (int) $matches[1];
        }

        $itemList = ($itemListId > 0 && Schema::hasTable('item_lists')) ? ItemList::find($itemListId) : null;
        $inventoryProduct = ($inventoryProductId > 0 && Schema::hasTable('inventory_products')) ? InventoryProduct::find($inventoryProductId) : null;
        $barcode = ($barcodeId > 0 && Schema::hasTable('barcodes')) ? Barcode::find($barcodeId) : null;

        if (! $itemList && $productId > 0 && Schema::hasTable('item_lists')) {
            $candidate = ItemList::find($productId);
            if ($candidate && (! $name || strcasecmp(trim((string) $candidate->item), trim((string) $name)) === 0)) {
                $itemList = $candidate;
            } elseif (! $inventoryProduct && Schema::hasTable('inventory_products')) {
                $invCandidate = InventoryProduct::find($productId);
                if ($invCandidate && (! $name || strcasecmp(trim((string) $invCandidate->item_name), trim((string) $name)) === 0)) {
                    $inventoryProduct = $invCandidate;
                }
            }
        }

        if (! $itemList && $inventoryProduct && Schema::hasTable('item_lists')) {
            $itemList = ItemInventoryLinker::findItemListForInventoryProduct($inventoryProduct);
        }

        if (! $itemList && $name && Schema::hasTable('item_lists')) {
            $itemList = ItemInventoryLinker::findItemListByNameAndPriceType($name, $priceType);
        }

        if (! $inventoryProduct && $itemList && Schema::hasTable('inventory_products')) {
            $inventoryProduct = ItemInventoryLinker::findInventoryProductForItemList($itemList);
        }

        if (! $inventoryProduct && $name && Schema::hasTable('inventory_products')) {
            $inventoryProduct = ItemInventoryLinker::findInventoryProductByNameAndPriceType($name, $priceType);
        }

        if (! $barcode && $sku !== '' && Schema::hasTable('barcodes')) {
            $barcode = ItemInventoryLinker::findBarcodeByValueAndPriceType($sku, $priceType ?? $rawPriceType);
        }

        if (! $barcode && $name && Schema::hasTable('barcodes')) {
            $barcode = ItemInventoryLinker::findBarcodeByNameAndPriceType($name, $priceType);
            if (! $barcode && $priceType === null) {
                $barcode = ItemInventoryLinker::findBarcodeByNameAndPriceType($name, null);
            }
        }

        return [$itemList, $inventoryProduct, $barcode];
    }

    /**
     * Change stock on inventory_products, item_lists, and leftover barcode rows
     * so cashier and inventory qty both move after a sale, void, or refund.
     */
    private function adjustLinkedStock(array $item, float $delta, string $action, string $description, array $metadata = []): void
    {
        if ($delta == 0.0) {
            return;
        }

        if ($this->usesNewPosSchema()) {
            $product = $this->resolveProduct($item);
            if (! $product) {
                Log::warning('Sale stock target not found; quantity was not changed', [
                    'item' => $item,
                    'action' => $action,
                ]);

                return;
            }

            $before = (float) ($product->quantity ?? 0);
            $after = max(0, $before + $delta);
            $product->update(['quantity' => (int) $after]);

            return;
        }

        if (! Schema::hasTable('item_lists') && ! Schema::hasTable('inventory_products') && ! Schema::hasTable('barcodes')) {
            return;
        }

        [$itemList, $inventoryProduct, $barcode] = $this->resolveStockTargets($item);
        $updatedLive = false;

        if ($inventoryProduct && Schema::hasTable('inventory_products')) {
            $inventoryProduct->refresh();
            $before = (float) ($inventoryProduct->quantity_on_hand ?? 0);
            $after = max(0, $before + $delta);

            if (Schema::hasTable('inventory_product_logs')) {
                InventoryProductLog::logQuantityChange(
                    $inventoryProduct->id,
                    Auth::id(),
                    $action,
                    $before,
                    $after,
                    $description,
                    array_merge($metadata, [
                        'item_list_id' => $itemList?->id,
                        'barcode_id' => $barcode?->id,
                        'price_type' => $item['price_type'] ?? $item['category'] ?? $itemList?->price_type ?? $inventoryProduct->price_type,
                    ])
                );
            }

            DB::table('inventory_products')
                ->where('id', $inventoryProduct->id)
                ->update([
                    'quantity_on_hand' => $after,
                    'updated_at' => now(),
                ]);
            $updatedLive = true;
        }

        if ($itemList && Schema::hasTable('item_lists')) {
            $before = (float) ($itemList->quantity_on_hand ?? 0);
            $after = max(0, $before + $delta);

            DB::table('item_lists')
                ->where('id', $itemList->id)
                ->update([
                    'quantity_on_hand' => $after,
                    'updated_at' => now(),
                ]);
            $updatedLive = true;
        }

        $soldBarcodeRow = str_starts_with((string) ($item['cashier_key'] ?? $item['uniqueKey'] ?? ''), 'bc-')
            || (($item['source'] ?? null) === 'barcodes');

        if ($barcode && Schema::hasTable('barcodes') && (! $updatedLive || $soldBarcodeRow)) {
            $before = (float) ($barcode->quantity_on_hand ?? 0);
            $after = max(0, $before + $delta);

            DB::table('barcodes')
                ->where('id', $barcode->id)
                ->update([
                    'quantity_on_hand' => $after,
                    'updated_at' => now(),
                ]);
        }

        if (! $inventoryProduct && ! $itemList && ! $barcode) {
            Log::warning('Sale stock target not found; quantity was not changed', [
                'item' => $item,
                'action' => $action,
            ]);
        }
    }
}
