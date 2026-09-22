<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use App\Models\InventoryLog;
use App\Models\InventoryProduct;
use App\Models\InventoryProductLog;
use App\Models\ItemList;
use App\Models\Barcode;
use App\Support\BarcodeImageSync;
use App\Support\ItemEditLogger;
use App\Support\ItemInventoryLinker;
use App\Support\ProductCatalog;
use App\Support\ProductImageStorage;
use App\Support\RepairPlaceholderPrices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    /**
     * Clean up invalid image paths from database
     */
    private function cleanupInvalidImagePaths()
    {
        try {
            // Clean up item_lists
            $itemLists = DB::table('item_lists')
                ->whereNotNull('item_image')
                ->get(['id', 'item_image']);
            
            foreach ($itemLists as $item) {
                $imagePath = trim($item->item_image);
                $fullPath = null;
                $fileExists = false;
                
                if (strpos($imagePath, 'http') === 0) {
                    // Skip external URLs
                    continue;
                } elseif (strpos($imagePath, 'images/') === 0) {
                    $fullPath = public_path($imagePath);
                    $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                } elseif (strpos($imagePath, 'storage/') === 0) {
                    $storagePath = str_replace('storage/', '', $imagePath);
                    $fullPath = storage_path('app/public/' . $storagePath);
                    $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                } else {
                    // Try images/inventory/ first (most common location)
                    $fullPath = public_path('images/inventory/' . $imagePath);
                    $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                    if (!$fileExists) {
                        // Try images/ directory
                        $fullPath = public_path('images/' . $imagePath);
                        $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                    }
                    if (!$fileExists) {
                        // Try storage path as last resort
                        $fullPath = storage_path('app/public/' . $imagePath);
                        $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                    }
                }
                
                if (!$fileExists) {
                    DB::table('item_lists')
                        ->where('id', $item->id)
                        ->update(['item_image' => null]);
                    
                    \Log::info('Cleaned up invalid image path from item_lists', [
                        'item_id' => $item->id,
                        'image_path' => $imagePath
                    ]);
                }
            }
            
            // Clean up inventory_products
            $inventoryProducts = DB::table('inventory_products')
                ->whereNotNull('item_image')
                ->get(['id', 'item_image']);
            
            foreach ($inventoryProducts as $item) {
                $imagePath = trim($item->item_image);
                $fullPath = null;
                $fileExists = false;
                
                if (strpos($imagePath, 'http') === 0) {
                    // Skip external URLs
                    continue;
                } elseif (strpos($imagePath, 'images/') === 0) {
                    $fullPath = public_path($imagePath);
                    $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                } elseif (strpos($imagePath, 'storage/') === 0) {
                    $storagePath = str_replace('storage/', '', $imagePath);
                    $fullPath = storage_path('app/public/' . $storagePath);
                    $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                } else {
                    // Try images/inventory/ first (most common location)
                    $fullPath = public_path('images/inventory/' . $imagePath);
                    $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                    if (!$fileExists) {
                        // Try images/ directory
                        $fullPath = public_path('images/' . $imagePath);
                        $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                    }
                    if (!$fileExists) {
                        // Try storage path as last resort
                        $fullPath = storage_path('app/public/' . $imagePath);
                        $fileExists = @file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0;
                    }
                }
                
                if (!$fileExists) {
                    DB::table('inventory_products')
                        ->where('id', $item->id)
                        ->update(['item_image' => null]);
                    
                    \Log::info('Cleaned up invalid image path from inventory_products', [
                        'item_id' => $item->id,
                        'image_path' => $imagePath
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error cleaning up invalid image paths', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function index(Request $request)
    {
        // Note: cleanupInvalidImagePaths is disabled to preserve database paths
        // Files may just need to be uploaded to the server
        // $this->cleanupInvalidImagePaths();
        
        RepairPlaceholderPrices::run();

        $allItems = ProductCatalog::allIncludingInactive();

        // Hide archived/inactive products by default (delete archives rows with sales history).
        // Pass ?active_status=Inactive to review archived items, or Active for active-only.
        if ($request->filled('active_status')) {
            $allItems = $allItems->filter(function ($item) use ($request) {
                return (string) ($item->active_status ?? '') === (string) $request->active_status;
            })->values();
        } else {
            $allItems = $allItems->filter(function ($item) {
                $status = (string) ($item->active_status ?? 'Active');

                return $status === 'Active' || $status === '1';
            })->values();
        }

        if ($request->filled('expiration')) {
            $allItems = $allItems->filter(function ($item) use ($request) {
                $expirationDate = $item->expiration_date ?? null;
                return match ($request->expiration) {
                    'expired' => $expirationDate && $expirationDate < now(),
                    'expiring' => $expirationDate && $expirationDate > now() && $expirationDate <= now()->addDays(30),
                    'normal' => !$expirationDate || $expirationDate > now()->addDays(30),
                    default => true,
                };
            })->values();
        }

        $allItems = $allItems->sortBy('item_name')->values();
        
        // Apply search filter if provided
        if ($request->filled('search')) {
            $allItems = $allItems->filter(
                fn ($item) => ProductCatalog::matchesSearch($item, (string) $request->search)
            )->values();
        }

        if ($request->filled('stock_status')) {
            $allItems = $allItems->filter(function ($item) use ($request) {
                $qty = (float) ($item->quantity_on_hand ?? 0);
                return match ($request->stock_status) {
                    'out' => $qty <= 0,
                    'low' => $qty > 0 && $qty <= 10,
                    'normal' => $qty > 10,
                    default => true,
                };
            })->values();
        }

        // Calculate statistics from the merged list (prevents double counting across tables)
        // Note: counts include inactive items to match what is shown in the table.
        $lowStockProducts = $allItems
            ->where('quantity_on_hand', '>', 0)
            ->where('quantity_on_hand', '<=', 10)
            ->count();

        $expiredProducts = $allItems
            ->whereNotNull('expiration_date')
            ->filter(fn ($item) => $item->expiration_date < now())
            ->count();

        $expiringSoonProducts = $allItems
            ->whereNotNull('expiration_date')
            ->filter(fn ($item) => $item->expiration_date > now() && $item->expiration_date <= now()->addDays(30))
            ->count();

        return view('inventory.index', compact(
            'allItems',
            'lowStockProducts',
            'expiredProducts',
            'expiringSoonProducts'
        ));
    }

    /**
     * Get quantity comparison for inventory products
     */
    public function getQuantityComparison(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $comparisonData = $this->buildQuantityComparisonData($productId, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $comparisonData['rows'],
                'total_products' => $comparisonData['total_products'],
                'products_with_changes' => $comparisonData['products_with_changes'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getQuantityComparison', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching quantity comparison: ' . $e->getMessage(),
                'data' => [],
                'total_products' => 0,
                'products_with_changes' => 0,
            ], 500);
        }
    }

    /**
     * Preview table for admin (products only, no dashboard chrome)
     */
    public function preview(Request $request)
    {
        $query = Product::query()->with('unit');

        // Search filter
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('lot_number', 'like', '%'.$search.'%');
            });
        }

        // Stock filters
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'out':
                    $query->where('quantity', '<=', 0);
                    break;
                case 'low':
                    $query->where('quantity', '<=', 10)
                        ->where('quantity', '>', 0);
                    break;
                case 'normal':
                    $query->where('quantity', '>', 10);
                    break;
            }
        }

        // Expiration filter
        if ($request->filled('expiration')) {
            switch ($request->expiration) {
                case 'expired':
                    $query->whereNotNull('expiration_date')
                        ->where('expiration_date', '<', now());
                    break;
                case 'expiring':
                    $query->whereNotNull('expiration_date')
                        ->where('expiration_date', '>', now())
                        ->where('expiration_date', '<=', now()->addDays(30));
                    break;
                case 'normal':
                    $query->where(function ($q) {
                        $q->whereNull('expiration_date')
                            ->orWhere('expiration_date', '>', now()->addDays(30));
                    });
                    break;
            }
        }

        $products = $query->orderBy('name')->get()->map(function (Product $product) {
            /** @var \Illuminate\Support\Carbon|null $expiration */
            $expiration = $product->expiration_date;

            return [
                'id' => $product->id,
                'item_name' => $product->name,
                'quantity_on_hand' => (float) ($product->quantity ?? 0),
                'unit' => optional($product->unit)->name ?? 'pcs',
                'price' => (float) ($product->selling_price ?? 0),
                'expiration_date' => $expiration?->toDateString(),
                'original_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
            ];
        });

        if ($request->wantsJson() || $request->get('format') === 'json') {
            return response()->json([
                'success' => true,
                'products' => $products->values(),
            ]);
        }

        // Blade preview expects objects with property access
        $products = $products->map(fn (array $row) => (object) $row);

        return view('inventory.preview', compact('products'));
    }

    /**
     * Update original/cost price from admin costing / inventory preview.
     */
    public function updateOriginalPrice(Request $request, Product $inventoryProduct)
    {
        $validated = $request->validate([
            'original_price' => 'required|numeric|min:0',
        ]);

        ItemEditLogger::log(
            'product',
            $inventoryProduct->id,
            (string) ($inventoryProduct->name ?? 'Unknown Item'),
            ['cost_price' => $inventoryProduct->cost_price],
            ['cost_price' => $validated['original_price']]
        );

        $inventoryProduct->update([
            'cost_price' => $validated['original_price'],
        ]);

        return back()->with('success', 'Original price updated.');
    }

    /**
     * Dedicated costing page with inventory-style listing.
     */
    public function costing(Request $request)
    {
        $query = Product::query()->with('unit');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('lot_number', 'like', '%'.$search.'%');
            });
        }

        $products = $query->orderBy('name')->paginate(25)->withQueryString();
        $products->getCollection()->transform(function (Product $product) {
            $unitName = optional($product->unit)->name ?? 'pcs';
            $product->unsetRelation('unit');
            $product->setAttribute('item_name', $product->name);
            $product->setAttribute('quantity_on_hand', (float) ($product->quantity ?? 0));
            $product->setAttribute('original_price', $product->cost_price);
            $product->setAttribute('price', (float) ($product->selling_price ?? 0));
            $product->setAttribute('unit', $unitName);

            return $product;
        });

        return view('costing.index', compact('products'));
    }

    /**
     * Export quantity comparison to CSV (Excel-friendly)
     */
    public function exportQuantityComparison(Request $request)
    {
        $productId = $request->get('product_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $comparisonData = $this->buildQuantityComparisonData($productId, $dateFrom, $dateTo);
        $rows = $comparisonData['rows'];

        $filename = 'quantity_comparison_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Item Name', 'Previous Quantity', 'Current Quantity', 'Change', 'Type', 'Last Updated'];

        $callback = function () use ($rows, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['item_name'],
                    $row['previous_quantity'],
                    $row['current_quantity'],
                    $row['change'],
                    $row['movement_type'] ?? 'Paid',
                    $row['last_updated'],
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /**
     * Build quantity comparison data (shared for API and export)
     */
    private function buildQuantityComparisonData($productId, $dateFrom, $dateTo): array
    {
        $productsQuery = InventoryProduct::query();
        
        if ($productId) {
            $productsQuery->where('id', $productId);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, InventoryProduct> $products */
        $products = $productsQuery->orderBy('item_name')->get();
        
        if ($products->isEmpty()) {
            return [
                'rows' => [],
                'total_products' => 0,
                'products_with_changes' => 0,
            ];
        }
        
        $comparisonData = [];

        foreach ($products as $product) {
            try {
                $currentQuantity = (float) ($product->quantity_on_hand ?? 0);
                
                // Get the most recent log before the date range (if date range is specified)
                $logQuery = $product->logs();
                $baselineLog = null;
                
                if ($dateFrom) {
                    $baselineLog = $product->logs()
                        ->whereDate('created_at', '<', $dateFrom)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $logQuery->whereDate('created_at', '>=', $dateFrom);
                } else {
                    $baselineLog = $product->logs()
                        ->orderBy('created_at', 'asc')
                        ->first();
                }
                
                if ($dateTo) {
                    $logQuery->whereDate('created_at', '<=', $dateTo);
                }
                
                $latestLog = $logQuery->orderBy('created_at', 'desc')->first();
                
                $previousQuantity = null;
                if ($latestLog && $latestLog->quantity_before !== null) {
                    $previousQuantity = (float) $latestLog->quantity_before;
                } elseif ($baselineLog && $baselineLog->quantity_before !== null) {
                    $previousQuantity = (float) $baselineLog->quantity_before;
                } elseif ($latestLog && $latestLog->quantity_after !== null) {
                    $previousLog = $product->logs()
                        ->where('id', '<', $latestLog->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $previousQuantity = $previousLog ? (float) $previousLog->quantity_after : (float) $latestLog->quantity_before;
                }
                
                if ($previousQuantity === null) {
                    $previousQuantity = $currentQuantity;
                }
                
                $change = $currentQuantity - $previousQuantity;
                $hasChange = abs($change) > 0.01;

                // Determine movement type (paid vs free sample)
                $movementType = 'Paid';
                // Use the most recent log overall (not filtered by date) to infer type
                $latestLogOverall = $product->logs()->orderBy('created_at', 'desc')->first();
                if ($latestLogOverall && !empty($latestLogOverall->action) && $latestLogOverall->action === 'free_sample') {
                    $movementType = 'Free Sample';
                } elseif ($latestLog && !empty($latestLog->action) && $latestLog->action === 'free_sample') {
                    $movementType = 'Free Sample';
                }
                
                $comparisonData[] = [
                    'id' => $product->id,
                    'item_name' => $product->item_name ?? 'N/A',
                    'previous_quantity' => $previousQuantity,
                    'current_quantity' => $currentQuantity,
                    'change' => $change,
                    'movement_type' => $movementType,
                    'last_updated' => $latestLog ? $latestLog->created_at?->toDateTimeString() : null,
                    'has_change' => $hasChange,
                ];
            } catch (\Exception $e) {
                \Log::warning('Error processing product for comparison', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        usort($comparisonData, function($a, $b) {
            $changeA = abs($a['change'] ?? 0);
            $changeB = abs($b['change'] ?? 0);
            if ($changeA != $changeB) {
                return $changeB <=> $changeA;
            }
            return strcmp($a['item_name'], $b['item_name']);
        });

        return [
            'rows' => $comparisonData,
            'total_products' => $products->count(),
            'products_with_changes' => count(array_filter($comparisonData, fn($item) => $item['has_change'])),
        ];
    }

    public function store(Request $request)
    {
        $originalPrice = $request->input('original_price', $request->input('cost'));
        $expirationDate = $request->input('expiration_date', $request->input('expiry_date'));

        $request->merge([
            'item_name' => $request->input('item_name', $request->input('name')),
            'barcode_value' => $request->input('barcode_value', $request->input('sku')),
            'quantity_on_hand' => $request->input('quantity_on_hand', $request->input('stock_quantity')),
            'expiration_date' => $expirationDate === '' ? null : $expirationDate,
            'original_price' => $originalPrice === '' ? null : $originalPrice,
        ]);

        $rules = [
            'item_name' => 'required|string|max:255',
            'barcode_value' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'price_type' => 'required|in:retail,wholesale',
            'quantity_on_hand' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
        ];

        if ($request->hasFile('item_image')) {
            $rules['item_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
        }

        $validated = $request->validate($rules);

        if (ProductCatalog::duplicateExists(
            $validated['barcode_value'],
            $validated['price_type'],
            $validated['item_name'],
            'inventory_products',
            null,
            ['inventory_products'],
            $validated['brand'] ?? null
        )) {
            return response()->json([
                'success' => false,
                'message' => 'A product with the same name, brand, and price type already exists.',
            ], 422);
        }

        $unit = strtolower(trim((string) ($validated['unit'] ?? 'pcs')));
        if (in_array($unit, ['piece', 'pieces', 'pc'], true)) {
            $unit = 'pcs';
        }

        $itemImagePath = $this->storeNewItemImageFromRequest($request);

        DB::beginTransaction();
        try {
            $product = InventoryProduct::create([
                'item_name' => $validated['item_name'],
                'barcode_value' => $validated['barcode_value'],
                'description' => $validated['description'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'price' => $validated['price'],
                'original_price' => $validated['original_price'] ?? null,
                'price_type' => $validated['price_type'],
                'unit' => $unit,
                'quantity_on_hand' => ItemInventoryLinker::quantityForNewRow($validated['quantity_on_hand']),
                'expiration_date' => $validated['expiration_date'] ?? null,
                'item_image' => $itemImagePath,
                'active_status' => 'Active',
            ]);

            if (!Barcode::where('barcode_value', $product->barcode_value)->exists()) {
                Barcode::ensureOriginalPriceColumn();
                Barcode::create(Barcode::attributesForExistingColumns([
                    'barcode_value' => $product->barcode_value,
                    'item_name' => $product->item_name,
                    'description' => $product->description,
                    'brand' => $product->brand,
                    'price' => $product->price,
                    'original_price' => $product->original_price,
                    'price_type' => $product->price_type,
                    'unit' => $product->unit,
                    'quantity_on_hand' => $product->quantity_on_hand,
                    'expiration_date' => $product->expiration_date,
                    'item_image' => $itemImagePath,
                    'active_status' => 'Active',
                    'barcode_type' => 'CODE128',
                ]));
            }

            InventoryProductLog::logQuantityChange(
                $product->id,
                Auth::id(),
                'add',
                0,
                (float) $product->quantity_on_hand,
                'Product added to inventory'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product added to inventory',
                'product' => $product
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'This barcode already exists. Use a different barcode, or edit the existing product.',
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewBarcodeImageSync()
    {
        $result = BarcodeImageSync::run(true);

        return view('inventory.sync-barcode-images', compact('result'));
    }

    public function applyBarcodeImageSync()
    {
        $result = BarcodeImageSync::run(false);

        $message = sprintf(
            'Copied barcode photos: %d inventory, %d item lists. Skipped %d that already had a photo. No products were created. Qty and price were not changed.',
            $result['inventory_filled'],
            $result['item_list_filled'],
            $result['skipped_has_image']
        );

        if ($result['missing_files'] > 0) {
            $message .= ' ' . $result['missing_files'] . ' photo file(s) are missing on the server (404). Upload public/images/barcodes/ or those items will stay blank.';
        }

        return redirect()->route('inventory.index')->with('success', $message);
    }

    public function show(Product $product)
    {
        $product->load(['batches', 'inventoryLogs.user']);
        
        return view('inventory.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load(['category', 'unit']);
        $categories = \App\Models\Category::orderBy('name')->get();
        $units = \App\Models\Unit::orderBy('name')->get();

        return view('inventory.edit', compact('product', 'categories', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'price_type' => 'required|in:retail,wholesale',
            'quantity' => 'required|integer|min:0',
            'lot_number' => 'nullable|string|max:255',
            'mfg_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            if ($request->hasFile('image')) {
                ProductImageStorage::delete($product->image);
                $validated['image'] = ProductImageStorage::storeUploadedFile(
                    $request->file('image'),
                    'product'
                );
            }

            $validated['is_active'] = (bool) $validated['is_active'];
            $product->update($validated);

            return redirect()->route('inventory.index')
                ->with('status', 'Product updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update product: '.$e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            if ($this->productHasRestrictedReferences($product)) {
                $product->update([
                    'is_active' => false,
                ]);

                $message = 'Product has sales or stock history, so it was archived instead of permanently deleted.';

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'archived' => true,
                        'message' => $message,
                    ]);
                }

                return redirect()->route('inventory.index')
                    ->with('status', $message);
            }

            DB::transaction(function () use ($product) {
                ProductImageStorage::delete($product->image);
                $product->delete();
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product deleted successfully',
                ]);
            }

            return redirect()->route('inventory.index')
                ->with('status', 'Product deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete product: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('inventory.index')
                ->with('error', 'Failed to delete product: '.$e->getMessage());
        }
    }

    private function productHasRestrictedReferences(Product $product): bool
    {
        $checks = [
            'sale_items' => 'product_id',
            'stock_movements' => 'product_id',
            'damage_request_items' => 'product_id',
            'free_sample_request_items' => 'product_id',
        ];

        foreach ($checks as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (DB::table($table)->where($column, $product->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function archive(Product $product)
    {
        DB::beginTransaction();
        try {
            $product->update(['is_active' => false]);

            // Log the archival
            InventoryLog::logAction(
                $product->id,
                Auth::id(),
                'archive',
                $product->stock_quantity,
                $product->stock_quantity,
                'Product archived'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product archived successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addBatch(Request $request, Product $product)
    {
        $validated = $request->validate([
            'batch_number' => 'required|string|unique:product_batches,batch_number',
            'quantity' => 'required|integer|min:1',
            'expiry_date' => 'nullable|date|after:today',
            'manufactured_date' => 'nullable|date|before_or_equal:today',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'received_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $validated['product_id'] = $product->id;
            $batch = ProductBatch::create($validated);

            // Update product stock
            $oldStock = $product->stock_quantity;
            $product->increment('stock_quantity', $validated['quantity']);

            // Log the batch addition
            InventoryLog::logAction(
                $product->id,
                Auth::id(),
                'stock_in',
                $oldStock,
                $product->stock_quantity,
                "Batch #{$batch->batch_number} added",
                ['batch_id' => $batch->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch added successfully',
                'batch' => $batch
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add batch: ' . $e->getMessage()
            ], 500);
        }
    }

    public function lowStock()
    {
        $products = Product::whereColumn('stock_quantity', '<=', 'min_stock_level')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->get();

        return view('inventory.low-stock', compact('products'));
    }

    public function expiring()
    {
        $products = Product::where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('is_active', true)
            ->orderBy('expiry_date')
            ->get();

        $batches = ProductBatch::where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('status', 'active')
            ->with('product')
            ->orderBy('expiry_date')
            ->get();

        return view('inventory.expiring', compact('products', 'batches'));
    }

    public function expired()
    {
        $products = Product::where('expiry_date', '<', now())
            ->where('is_active', true)
            ->orderBy('expiry_date')
            ->get();

        $batches = ProductBatch::where('expiry_date', '<', now())
            ->where('status', 'active')
            ->with('product')
            ->orderBy('expiry_date')
            ->get();

        return view('inventory.expired', compact('products', 'batches'));
    }

    public function logs(Request $request)
    {
        $logs = InventoryLog::with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('inventory.logs', compact('logs'));
    }

    public function export(Request $request)
    {
        $products = Product::all();

        $filename = 'inventory_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, [
                'ID', 'Name', 'SKU', 'Category', 'Brand', 'Price', 
                'Stock Quantity', 'Min Stock Level', 'Unit', 'Supplier',
                'Expiry Date', 'Requires Prescription', 'Status'
            ]);

            // Add data
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->sku ?? 'N/A',
                    $product->category,
                    $product->brand ?? 'N/A',
                    $product->price,
                    $product->stock_quantity,
                    $product->min_stock_level,
                    $product->unit,
                    $product->supplier ?? 'N/A',
                    $product->expiry_date ? \Carbon\Carbon::parse($product->expiry_date)->format('Y-m-d') : 'N/A',
                    $product->requires_prescription ? 'Yes' : 'No',
                    $product->is_active ? 'Active' : 'Inactive'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function editInventoryProduct(InventoryProduct $inventoryProduct)
    {
        return view('inventory.edit-inventory-product', compact('inventoryProduct'));
    }

    public function updateInventoryProduct(Request $request, InventoryProduct $inventoryProduct)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'price_type' => 'nullable|in:retail,wholesale',
            'unit' => 'nullable|string|max:50',
            'quantity_on_hand' => 'nullable|numeric|min:0',
            'expiration_date' => 'nullable|date',
            'mfg_date' => 'nullable|date',
            'lot_number' => 'nullable|string|max:255',
            'active_status' => 'nullable|in:Active,Inactive',
            'barcode_value' => 'nullable|string|unique:inventory_products,barcode_value,' . $inventoryProduct->id,
            'item_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            // Handle image upload
            if ($request->hasFile('item_image')) {
                if ($inventoryProduct->item_image) {
                    ProductImageStorage::delete($inventoryProduct->item_image);
                }

                $validated['item_image'] = ProductImageStorage::storeUploadedFile(
                    $request->file('item_image'),
                    'product'
                );
            }

            $oldItemName = $inventoryProduct->item_name;
            $newItemName = $validated['item_name'] ?? $oldItemName;
            $oldQty = (float) ($inventoryProduct->quantity_on_hand ?? 0);

            ItemEditLogger::log(
                'inventory_product',
                $inventoryProduct->id,
                (string) $newItemName,
                $inventoryProduct->getAttributes(),
                $validated
            );
            
            $relatedItemList = ItemInventoryLinker::findItemListForInventoryProduct($inventoryProduct, $oldItemName);
            
            // Use direct DB update to bypass Eloquent observers and prevent duplicates
            DB::table('inventory_products')
                ->where('id', $inventoryProduct->id)
                ->update($validated);
            
            // Refresh the model instance to reflect the changes
            $inventoryProduct->refresh();
            
            // If related item_list exists, update it to match the inventory_product (without triggering observer)
            if ($relatedItemList) {
                // Use direct DB update to bypass observers
                $itemListUpdateData = [
                    'item' => $newItemName, // Update name to match inventory_product
                    'description' => $validated['description'] ?? $relatedItemList->description,
                    'brand' => $validated['brand'] ?? $relatedItemList->brand,
                    'price' => $validated['price'] ?? $relatedItemList->price,
                    'price_type' => $validated['price_type'] ?? $relatedItemList->price_type,
                    'unit_of_measure' => $validated['unit'] ?? $relatedItemList->unit_of_measure,
                    'expiry_date' => isset($validated['expiration_date']) ? $validated['expiration_date'] : $relatedItemList->expiry_date,
                    'mfg_date' => isset($validated['mfg_date']) ? $validated['mfg_date'] : $relatedItemList->mfg_date,
                    'active_status' => $validated['active_status'] ?? $relatedItemList->active_status,
                    'lot_number' => isset($validated['lot_number']) ? $validated['lot_number'] : $relatedItemList->lot_number,
                ];
                
                // Only update image if new one was uploaded
                if (isset($validated['item_image'])) {
                    $itemListUpdateData['item_image'] = $validated['item_image'];
                }
                
                // Live qty lives on inventory_products. Do not mirror it onto item_lists.
                
                // Remove null values to avoid overwriting with null
                $itemListUpdateData = array_filter($itemListUpdateData, function($value) {
                    return $value !== null;
                });
                
                DB::table('item_lists')
                    ->where('id', $relatedItemList->id)
                    ->update($itemListUpdateData);
                
                \Log::info('Updated related item_list to match inventory_product', [
                    'inventory_product_id' => $inventoryProduct->id,
                    'item_list_id' => $relatedItemList->id,
                    'updated_fields' => array_keys($itemListUpdateData)
                ]);
            }

            return redirect()->route('inventory.index')
                ->with('success', 'Product updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    public function destroyInventoryProduct(InventoryProduct $inventoryProduct)
    {
        DB::beginTransaction();
        try {
            // Delete the associated image if it exists
            if ($inventoryProduct->item_image) {
                $imagePath = $inventoryProduct->item_image;
                if (strpos($imagePath, 'images/') === 0) {
                    $fullPath = public_path($imagePath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                } elseif (strpos($imagePath, 'storage/') === 0) {
                    $imagePath = str_replace('storage/', '', $imagePath);
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
            }

            // Find the matching ItemList only (same barcode/name AND price_type)
            $itemList = ItemInventoryLinker::findItemListForInventoryProduct($inventoryProduct);

            // Delete the ItemList if found
            if ($itemList) {
                $itemList->delete();
            }

            // Delete the InventoryProduct
            $inventoryProduct->delete();

            DB::commit();

            return redirect()->route('inventory.index')
                ->with('success', 'Product deleted successfully from both inventory and item list');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }

    public function editBarcode(Barcode $barcode)
    {
        try {
            $inventoryProduct = ItemInventoryLinker::ensureInventoryProductFromBarcode($barcode);
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('inventory.index')
                ->with('error', 'This barcode already exists in inventory. Edit that product instead of creating a duplicate.');
        }

        return redirect()->route('inventory-product.edit', $inventoryProduct);
    }

    public function destroyBarcode(Barcode $barcode)
    {
        DB::beginTransaction();
        try {
            $inventoryProduct = ItemInventoryLinker::findInventoryProductByBarcodeAndPriceType(
                $barcode->barcode_value,
                $barcode->price_type
            );

            $barcode->delete();

            if ($inventoryProduct) {
                $inventoryProduct->delete();
            }

            DB::commit();

            return redirect()->route('inventory.index')
                ->with('success', 'Product deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }

    public function editItemList(ItemList $itemList)
    {
        return view('inventory.edit-item-list', compact('itemList'));
    }

    public function updateItemList(Request $request, ItemList $itemList)
    {
        $validated = $request->validate([
            'item' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'price_type' => 'nullable|string|max:50',
            'unit_of_measure' => 'nullable|string|max:50',
            'quantity_on_hand' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'mfg_date' => 'nullable|date',
            'active_status' => 'nullable|string|max:20',
            'mpn' => 'nullable|string|max:100',
            'lot_number' => 'nullable|string|max:255',
            'item_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            // Handle image upload
            if ($request->hasFile('item_image')) {
                try {
                    if ($itemList->item_image) {
                        ProductImageStorage::delete($itemList->item_image);
                    }

                    $validated['item_image'] = ProductImageStorage::storeUploadedFile(
                        $request->file('item_image'),
                        'product'
                    );
                } catch (\Exception $e) {
                    \Log::error('Error moving uploaded image file', [
                        'error' => $e->getMessage(),
                    ]);

                    return back()->withInput()
                        ->with('error', 'Failed to upload image: '.$e->getMessage().'. Please check file size and directory permissions.');
                }
            }

            // Completely disable the observer to prevent duplicate creation
            // When manually editing item_lists, we don't want the observer to sync to inventory_products
            // This prevents duplicates when editing item names or other fields
            // Use direct DB update to bypass ALL Eloquent events and observers
            
            // If an image was uploaded, verify it exists before saving the path
            if (isset($validated['item_image'])) {
                $imagePathToCheck = public_path($validated['item_image']);
                if (!file_exists($imagePathToCheck) || !is_readable($imagePathToCheck)) {
                    \Log::error('Image path to be saved but file does not exist', [
                        'item_list_id' => $itemList->id,
                        'image_path' => $validated['item_image'],
                        'full_path' => $imagePathToCheck
                    ]);
                    // Remove the invalid image path from validated data to prevent saving invalid path
                    unset($validated['item_image']);
                }
            }
            
            $oldItemName = $itemList->item;
            $newItemName = $validated['item'] ?? $oldItemName;
            $oldQty = (float) ($itemList->quantity_on_hand ?? 0);

            ItemEditLogger::log(
                'item_list',
                $itemList->id,
                (string) $newItemName,
                $itemList->getAttributes(),
                $validated
            );
            
            $relatedInventoryProduct = ItemInventoryLinker::findInventoryProductForItemList($itemList);
            
            \Log::info('Updating ItemList - Using direct DB update to bypass observer', [
                'item_list_id' => $itemList->id,
                'old_name' => $oldItemName,
                'new_name' => $newItemName,
                'has_image' => isset($validated['item_image']),
                'found_related_product' => $relatedInventoryProduct ? $relatedInventoryProduct->id : null
            ]);
            
            // Use direct DB update - this completely bypasses Eloquent events and observers
            DB::table('item_lists')
                ->where('id', $itemList->id)
                ->update($validated);
            
            // Refresh the model instance to reflect the changes
            $itemList->refresh();
            
            // If related inventory_product exists, update it to match the item_list (without triggering observer)
            if ($relatedInventoryProduct) {
                // Use direct DB update to avoid triggering any observers on inventory_product side
                $inventoryUpdateData = [
                    'item_name' => $newItemName, // Update name to match item_list
                    'description' => $validated['description'] ?? $relatedInventoryProduct->description,
                    'brand' => $validated['brand'] ?? $relatedInventoryProduct->brand,
                    'price' => $validated['price'] ?? $relatedInventoryProduct->price,
                    'price_type' => $validated['price_type'] ?? $relatedInventoryProduct->price_type,
                    'unit' => $validated['unit_of_measure'] ?? $relatedInventoryProduct->unit,
                    'expiration_date' => isset($validated['expiry_date']) ? $validated['expiry_date'] : $relatedInventoryProduct->expiration_date,
                    'mfg_date' => isset($validated['mfg_date']) ? $validated['mfg_date'] : $relatedInventoryProduct->mfg_date,
                    'active_status' => $validated['active_status'] ?? $relatedInventoryProduct->active_status,
                    'lot_number' => isset($validated['lot_number']) ? $validated['lot_number'] : $relatedInventoryProduct->lot_number,
                ];
                
                // Only update image if new one was uploaded
                if (isset($validated['item_image'])) {
                    $inventoryUpdateData['item_image'] = $validated['item_image'];
                }

                if (isset($validated['quantity_on_hand']) && ItemInventoryLinker::quantitiesDiffer($oldQty, $validated['quantity_on_hand'])) {
                    $inventoryUpdateData['quantity_on_hand'] = $validated['quantity_on_hand'];
                }
                
                // Remove null values to avoid overwriting with null
                $inventoryUpdateData = array_filter($inventoryUpdateData, function($value) {
                    return $value !== null;
                });
                
                DB::table('inventory_products')
                    ->where('id', $relatedInventoryProduct->id)
                    ->update($inventoryUpdateData);
                
                \Log::info('Updated related inventory_product to match item_list', [
                    'item_list_id' => $itemList->id,
                    'inventory_product_id' => $relatedInventoryProduct->id,
                    'updated_fields' => array_keys($inventoryUpdateData)
                ]);
            }
            
            \Log::info('ItemList updated via direct DB query (no events fired)', [
                'item_list_id' => $itemList->id,
                'updated_fields' => array_keys($validated),
                'related_inventory_product_updated' => $relatedInventoryProduct ? true : false
            ]);

            return redirect()->route('inventory.index')
                ->with('success', 'Item updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update item: ' . $e->getMessage());
        }
    }

    public function destroyItemList(ItemList $itemList)
    {
        DB::beginTransaction();
        try {
            // Delete the associated image if it exists
            if ($itemList->item_image) {
                $imagePath = $itemList->item_image;
                if (strpos($imagePath, 'images/') === 0) {
                    $fullPath = public_path($imagePath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                } elseif (strpos($imagePath, 'storage/') === 0) {
                    $imagePath = str_replace('storage/', '', $imagePath);
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
            }

            $inventoryProduct = ItemInventoryLinker::findInventoryProductForItemList($itemList);

            // Delete the InventoryProduct if found
            if ($inventoryProduct) {
                $inventoryProduct->delete();
            }

            // Delete the ItemList
            $itemList->delete();

            DB::commit();

            return redirect()->route('inventory.index')
                ->with('success', 'Item deleted successfully from both item list and inventory');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Failed to delete item: ' . $e->getMessage());
        }
    }

    private function storeNewItemImageFromRequest(Request $request): ?string
    {
        if ($request->hasFile('item_image')) {
            return $this->moveItemImageFile($request->file('item_image'));
        }

        $raw = $request->input('item_image');
        if (!is_string($raw) || !preg_match('/^data:image\/(\w+);base64,/', $raw, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', str_replace(' ', '+', $raw)), true);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Invalid image data.');
        }

        return $this->writeItemImageBytes($binary, $extension);
    }

    private function moveItemImageFile($image): string
    {
        return ProductImageStorage::storeUploadedFile($image, 'product');
    }

    private function writeItemImageBytes(string $binary, string $extension): string
    {
        return ProductImageStorage::storeBinary($binary, $extension, 'product');
    }

    private function assertSavedItemImage(string $relative): string
    {
        $saved = public_path($relative);
        if (! is_file($saved) || ! is_readable($saved) || filesize($saved) === 0) {
            throw new \RuntimeException('Failed to save image file.');
        }

        return $relative;
    }
}
