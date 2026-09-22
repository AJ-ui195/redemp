<?php

namespace App\Http\Controllers;

use App\Models\InventoryProduct;
use App\Support\ProductCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = InventoryProduct::query();

        // Filter by active status
        if ($request->has('active_status')) {
            $query->where('active_status', $request->active_status);
        }

        // Filter by low stock
        if ($request->has('low_stock') && $request->low_stock == 'true') {
            $query->lowStock();
        }

        // Filter by out of stock
        if ($request->has('out_of_stock') && $request->out_of_stock == 'true') {
            $query->outOfStock();
        }

        // Filter by expiring soon
        if ($request->has('expiring_soon') && $request->expiring_soon == 'true') {
            $query->expiringSoon();
        }

        // Filter by expired
        if ($request->has('expired') && $request->expired == 'true') {
            $query->expired();
        }

        // Search by item name or barcode
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('barcode_value', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('item_name')->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode_value' => 'required|string|unique:inventory_products,barcode_value',
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'price_type' => 'nullable|in:retail,wholesale',
            'unit' => 'nullable|string|max:50',
            'quantity_on_hand' => 'nullable|numeric|min:0',
            'expiration_date' => 'nullable|date',
            'active_status' => 'nullable|in:Active,Inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (ProductCatalog::duplicateExists(
            $request->barcode_value,
            $request->price_type,
            $request->item_name,
            'inventory_products',
            null,
            ['inventory_products'],
            $request->brand
        )) {
            return response()->json([
                'success' => false,
                'message' => 'A product with the same name, brand, and price type already exists.',
                'errors' => [
                    'item_name' => ['A product with the same name, brand, and price type already exists.'],
                ],
            ], 422);
        }

        try {
            $product = InventoryProduct::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Inventory product created successfully',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $product = InventoryProduct::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory product not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'barcode_value' => 'sometimes|string|unique:inventory_products,barcode_value,' . $id,
            'item_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'price_type' => 'nullable|in:retail,wholesale',
            'unit' => 'nullable|string|max:50',
            'quantity_on_hand' => 'nullable|numeric|min:0',
            'expiration_date' => 'nullable|date',
            'active_status' => 'nullable|in:Active,Inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = InventoryProduct::findOrFail($id);
            
            // If quantity_on_hand is being updated, ensure we have accurate values
            if ($request->has('quantity_on_hand')) {
                // Refresh to get current quantity from database
                $product->refresh();
                $currentQty = (float) ($product->quantity_on_hand ?? 0);
                $newQty = (float) ($request->quantity_on_hand ?? 0);
                
                // Log the change manually for accuracy
                if (abs($currentQty - $newQty) > 0.0001) {
                    \App\Models\InventoryProductLog::logQuantityChange(
                        $product->id,
                        auth()->id(),
                        'manual_update',
                        $currentQty,
                        $newQty,
                        'Manual quantity update via API',
                        ['updated_fields' => array_keys($request->all())]
                    );
                    
                    // Set flag to skip automatic logging
                    $product->skipLogging = true;
                }
            }
            
            $product->update($request->all());
            
            // Verify the update was successful
            if ($request->has('quantity_on_hand')) {
                $product->refresh();
                $actualQty = (float) ($product->quantity_on_hand ?? 0);
                $expectedQty = (float) ($request->quantity_on_hand ?? 0);
                
                if (abs($actualQty - $expectedQty) > 0.0001) {
                    // Update the log if there's a discrepancy
                    $latestLog = \App\Models\InventoryProductLog::where('inventory_product_id', $product->id)
                        ->latest()
                        ->first();
                    if ($latestLog) {
                        $latestLog->update(['quantity_after' => $actualQty]);
                    }
                    \Log::warning('Quantity mismatch after manual update', [
                        'inventory_product_id' => $product->id,
                        'expected' => $expectedQty,
                        'actual' => $actualQty
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Inventory product updated successfully',
                'data' => $product->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = InventoryProduct::findOrFail($id);
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inventory product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product by barcode value
     */
    public function getByBarcode(Request $request)
    {
        $barcodeValue = $request->get('barcode_value');

        if (!$barcodeValue) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode value is required'
            ], 400);
        }

        try {
            $product = InventoryProduct::where('barcode_value', $barcodeValue)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'found' => false
                ], 404);
            }

            return response()->json([
                'success' => true,
                'found' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
