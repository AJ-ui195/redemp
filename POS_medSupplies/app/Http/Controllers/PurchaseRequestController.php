<?php

namespace App\Http\Controllers;

use App\Models\WholesaleToRetailRequest;
use App\Models\ItemList;
use App\Support\ItemInventoryLinker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseRequestController extends Controller
{
    /**
     * Store a new wholesale to retail conversion request
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'retail_item_id' => 'required|exists:item_lists,id',
                'wholesale_item_id' => 'required|exists:item_lists,id',
                'request_notes' => 'nullable|string|max:1000',
                'quantity_to_convert' => 'nullable|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Verify retail item is actually retail
        try {
            $retailItem = ItemList::findOrFail($validated['retail_item_id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retail item not found.'
            ], 404);
        }

        if ($retailItem->price_type !== 'retail') {
            return response()->json([
                'success' => false,
                'message' => 'Selected retail item is not a retail product.'
            ], 400);
        }

        // Verify wholesale item is actually wholesale
        try {
            $wholesaleItem = ItemList::findOrFail($validated['wholesale_item_id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wholesale item not found.'
            ], 404);
        }

        if ($wholesaleItem->price_type !== 'wholesale') {
            return response()->json([
                'success' => false,
                'message' => 'Selected wholesale item is not a wholesale product.'
            ], 400);
        }

        // Check if items have the same name (same product, different price type)
        if ($retailItem->item !== $wholesaleItem->item) {
            return response()->json([
                'success' => false,
                'message' => 'Products must have the same name to convert.'
            ], 400);
        }

        // Warn (but don't block) if retail has stock or wholesale is out of stock
        $warnings = [];
        $retailQty = ItemInventoryLinker::liveQuantityForItemList($retailItem);
        $wholesaleQty = ItemInventoryLinker::liveQuantityForItemList($wholesaleItem);
        if ($retailQty > 0) {
            $warnings[] = 'Retail product still has stock available (' . $retailQty . ').';
        }
        if ($wholesaleQty <= 0) {
            $warnings[] = 'Wholesale product is out of stock.';
        }

        // Check for existing pending request
        $existingRequest = WholesaleToRetailRequest::where('retail_item_id', $validated['retail_item_id'])
            ->where('wholesale_item_id', $validated['wholesale_item_id'])
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A pending request already exists for this conversion.'
            ], 400);
        }

        // Create request
        $requestModel = WholesaleToRetailRequest::create([
            'retail_item_id' => $validated['retail_item_id'],
            'wholesale_item_id' => $validated['wholesale_item_id'],
            'requested_by' => Auth::id(),
            'request_notes' => $validated['request_notes'] ?? null,
            'quantity_to_convert' => $validated['quantity_to_convert'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = [
            'success' => true,
            'message' => 'Conversion request submitted successfully. Waiting for admin approval.',
            'request' => $requestModel->load(['retailItem', 'wholesaleItem', 'requestedBy'])->toArray()
        ];
        
        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }
        
        return response()->json($response);
    }

    /**
     * Get pending requests (for admin)
     */
    public function getPendingRequests()
    {
        $requests = WholesaleToRetailRequest::with(['retailItem', 'wholesaleItem', 'requestedBy'])
            ->where('status', 'pending')
            ->orderBy('requested_at', 'desc')
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'retail_item' => $request->retailItem ? [
                        'id' => $request->retailItem->id,
                        'item' => $request->retailItem->item,
                        'unit_of_measure' => $request->retailItem->unit_of_measure,
                    ] : null,
                    'wholesale_item' => $request->wholesaleItem ? [
                        'id' => $request->wholesaleItem->id,
                        'item' => $request->wholesaleItem->item,
                        'quantity_on_hand' => ItemInventoryLinker::liveQuantityForItemList($request->wholesaleItem),
                        'unit_of_measure' => $request->wholesaleItem->unit_of_measure,
                    ] : null,
                    'requested_by_user' => $request->requestedBy ? [
                        'id' => $request->requestedBy->id,
                        'name' => $request->requestedBy->name,
                    ] : null,
                    'quantity_to_convert' => $request->quantity_to_convert,
                    'request_notes' => $request->request_notes,
                    'status' => $request->status,
                    'requested_at' => $request->requested_at ? $request->requested_at->toISOString() : null,
                    'created_at' => $request->created_at ? $request->created_at->toISOString() : null,
                ];
            });

        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }

    /**
     * Get request history (for admin)
     */
    public function getHistory(Request $request)
    {
        $query = WholesaleToRetailRequest::with(['retailItem', 'wholesaleItem', 'requestedBy', 'approvedBy']);
        
        // If user is not admin, only show their own requests
        if (!Auth::user()->is_admin) {
            $query->where('requested_by', Auth::id());
        }
        
        $requests = $query->orderBy('processed_at', 'desc')
            ->orderBy('requested_at', 'desc')
            ->paginate(20);
        
        // Transform the data for frontend
        $transformedRequests = $requests->getCollection()->map(function($request) {
            return [
                'id' => $request->id,
                'retail_item' => $request->retailItem ? [
                    'id' => $request->retailItem->id,
                    'item' => $request->retailItem->item,
                    'unit_of_measure' => $request->retailItem->unit_of_measure,
                ] : null,
                'wholesale_item' => $request->wholesaleItem ? [
                    'id' => $request->wholesaleItem->id,
                    'item' => $request->wholesaleItem->item,
                    'quantity_on_hand' => ItemInventoryLinker::liveQuantityForItemList($request->wholesaleItem),
                    'unit_of_measure' => $request->wholesaleItem->unit_of_measure,
                ] : null,
                'requested_by_user' => $request->requestedBy ? [
                    'id' => $request->requestedBy->id,
                    'name' => $request->requestedBy->name,
                ] : null,
                'approved_by_user' => $request->approvedBy ? [
                    'id' => $request->approvedBy->id,
                    'name' => $request->approvedBy->name,
                ] : null,
                'quantity_to_convert' => $request->quantity_to_convert,
                'request_notes' => $request->request_notes,
                'admin_notes' => $request->admin_notes,
                'status' => $request->status,
                'requested_at' => $request->requested_at ? $request->requested_at->toISOString() : null,
                'processed_at' => $request->processed_at ? $request->processed_at->toISOString() : null,
                'created_at' => $request->created_at ? $request->created_at->toISOString() : null,
            ];
        });
        
        return response()->json([
            'success' => true,
            'requests' => [
                'data' => $transformedRequests,
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ]
        ]);
    }

    /**
     * Get cashier's own request history
     */
    public function getMyHistory()
    {
        $requests = WholesaleToRetailRequest::with(['retailItem', 'wholesaleItem', 'approvedBy'])
            ->where('requested_by', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'retail_item' => $request->retailItem ? [
                        'id' => $request->retailItem->id,
                        'item' => $request->retailItem->item,
                        'unit_of_measure' => $request->retailItem->unit_of_measure,
                    ] : null,
                    'wholesale_item' => $request->wholesaleItem ? [
                        'id' => $request->wholesaleItem->id,
                        'item' => $request->wholesaleItem->item,
                        'quantity_on_hand' => ItemInventoryLinker::liveQuantityForItemList($request->wholesaleItem),
                        'unit_of_measure' => $request->wholesaleItem->unit_of_measure,
                    ] : null,
                    'approved_by_user' => $request->approvedBy ? [
                        'id' => $request->approvedBy->id,
                        'name' => $request->approvedBy->name,
                    ] : null,
                    'quantity_to_convert' => $request->quantity_to_convert,
                    'request_notes' => $request->request_notes,
                    'admin_notes' => $request->admin_notes,
                    'status' => $request->status,
                    'requested_at' => $request->requested_at ? $request->requested_at->toISOString() : null,
                    'processed_at' => $request->processed_at ? $request->processed_at->toISOString() : null,
                    'created_at' => $request->created_at ? $request->created_at->toISOString() : null,
                ];
            });

        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }

    /**
     * Approve a conversion request
     */
    public function approve(Request $request, WholesaleToRetailRequest $wholesaleToRetailRequest)
    {
        if ($wholesaleToRetailRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is not pending.'
            ], 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Get the items
            $retailItem = $wholesaleToRetailRequest->retailItem;
            $wholesaleItem = $wholesaleToRetailRequest->wholesaleItem;

            $retailInventory = ItemInventoryLinker::findInventoryProductForItemList($retailItem);
            $wholesaleInventory = ItemInventoryLinker::findInventoryProductForItemList($wholesaleItem);
            $wholesaleAvailable = $wholesaleInventory
                ? (float) ($wholesaleInventory->quantity_on_hand ?? 0)
                : (float) ($wholesaleItem->quantity_on_hand ?? 0);

            $quantityToConvert = $wholesaleToRetailRequest->quantity_to_convert ?? $wholesaleAvailable;

            if ($quantityToConvert > $wholesaleAvailable) {
                $quantityToConvert = $wholesaleAvailable;
            }

            if ($wholesaleInventory) {
                $wholesaleInventory->update([
                    'quantity_on_hand' => max(0, (float) $wholesaleInventory->quantity_on_hand - $quantityToConvert),
                ]);
            }

            if ($retailInventory) {
                $retailInventory->update([
                    'quantity_on_hand' => (float) ($retailInventory->quantity_on_hand ?? 0) + $quantityToConvert,
                ]);
            }

            // Update request status
            $wholesaleToRetailRequest->status = 'approved';
            $wholesaleToRetailRequest->approved_by = Auth::id();
            $wholesaleToRetailRequest->admin_notes = $validated['admin_notes'] ?? null;
            $wholesaleToRetailRequest->processed_at = now();
            $wholesaleToRetailRequest->save();

            DB::commit();

            Log::info('Wholesale to retail conversion approved', [
                'request_id' => $wholesaleToRetailRequest->id,
                'retail_item_id' => $retailItem->id,
                'wholesale_item_id' => $wholesaleItem->id,
                'quantity' => $quantityToConvert,
                'approved_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversion approved and completed successfully.',
                'request' => $wholesaleToRetailRequest->load(['retailItem', 'wholesaleItem', 'requestedBy', 'approvedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving wholesale to retail conversion', [
                'request_id' => $wholesaleToRetailRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing conversion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a conversion request
     */
    public function reject(Request $request, WholesaleToRetailRequest $wholesaleToRetailRequest)
    {
        if ($wholesaleToRetailRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request is not pending.'
            ], 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $wholesaleToRetailRequest->status = 'rejected';
        $wholesaleToRetailRequest->approved_by = Auth::id();
        $wholesaleToRetailRequest->admin_notes = $validated['admin_notes'];
        $wholesaleToRetailRequest->processed_at = now();
        $wholesaleToRetailRequest->save();

        Log::info('Wholesale to retail conversion rejected', [
            'request_id' => $wholesaleToRetailRequest->id,
            'rejected_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversion request rejected.',
            'request' => $wholesaleToRetailRequest->load(['retailItem', 'wholesaleItem', 'requestedBy', 'approvedBy'])
        ]);
    }

    /**
     * Find corresponding item by name and price type
     */
    public function findCorrespondingItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_name' => 'required|string',
                'price_type' => 'required|in:retail,wholesale',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $itemName = trim($validated['item_name']);
        if (empty($itemName)) {
            return response()->json([
                'success' => false,
                'message' => 'Item name cannot be empty.'
            ], 400);
        }

        $oppositePriceType = $validated['price_type'] === 'retail' ? 'wholesale' : 'retail';

        // Search for corresponding item with same name but different price type
        $correspondingItem = ItemList::where(function($q) {
                $q->where('active_status', 'Active')
                  ->orWhere('active_status', '1')
                  ->orWhere('active_status', 1)
                  ->orWhereNull('active_status');
            })
            ->where('price_type', $oppositePriceType)
            ->where(function($q) use ($itemName) {
                $q->where('item', '=', $itemName)
                  ->orWhere('item', 'LIKE', trim($itemName) . '%')
                  ->orWhereRaw('LOWER(TRIM(item)) = ?', [strtolower(trim($itemName))]);
            })
            ->select('id', 'item', 'price_type', 'quantity_on_hand', 'unit_of_measure')
            ->first();

        if ($correspondingItem) {
            return response()->json([
                'success' => true,
                'item' => [
                    'id' => $correspondingItem->id,
                    'item' => $correspondingItem->item,
                    'price_type' => $correspondingItem->price_type,
                    'quantity_on_hand' => ItemInventoryLinker::liveQuantityForItemList($correspondingItem),
                    'unit_of_measure' => $correspondingItem->unit_of_measure,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No corresponding item found.'
        ], 404);
    }
}
